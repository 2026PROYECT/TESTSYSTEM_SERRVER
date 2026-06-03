<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Models\PendingUser; 
use App\Models\AuditLog; // ← Agregar
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\LoginRequest;
use App\Notifications\NewStudentRegistered; 
use Illuminate\Support\Facades\Notification;
use App\Notifications\CustomNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyPendingEmail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    /**
     * Helper para registrar logs de auditoría
     */
    private function logAudit($action, $data = [])
    {
        if (!auth()->check()) {
            return;
        }
        
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'old_data' => $data['old_data'] ?? null,
            'new_data' => $data['new_data'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'severity' => $data['severity'] ?? 'info'
        ]);
    }

    public function register(Request $request)
    {
        // 0. Obtener la restricción de dominio desde la base de datos
        $domainSetting = \App\Models\Setting::where('key', 'allowed_email_domain')->first();
        $allowedDomain = $domainSetting ? $domainSetting->value : 'adm.emi.edu.bo';

        // 1. Configuración dinámica de reglas de email
        $emailRules = [
            'required',
            'string',
            'email',
            'max:255',
            'unique:users',
            'unique:pending_users'
        ];

        if ($allowedDomain !== '*') {
            $emailRules[] = 'ends_with:' . $allowedDomain;
        }

        // 2. Validación de campos
        $request->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'surname' => 'nullable|string|max:255',
            'email' => $emailRules,
            'password' => 'required|string|min:8|confirmed',
            'id_number' => 'required|string|unique:students,id_number',
            'saga_code' => 'required|string|unique:students,saga_code',
            'career_id' => 'required|exists:careers,id',
            'ci_front' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'ci_back' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'user_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'g-recaptcha-response' => 'required', 
        ], [
            'email.ends_with' => $allowedDomain !== '*' 
                ? "Debes utilizar tu correo electrónico institucional (@$allowedDomain)." 
                : "El formato del correo es inválido.",
        ]);

        // 3. Verificación ante Google (v3)
        $captchaResponse = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => env('RECAPTCHA_SECRET_KEY'), 
            'response' => $request->input('g-recaptcha-response'),
            'remoteip' => $request->ip(),
        ]);

        $data = $captchaResponse->json();

        if (!$data['success'] || (isset($data['score']) && $data['score'] < 0.5)) {
            return response()->json([
                'message' => 'Actividad sospechosa detectada. Por favor, intente nuevamente.'
            ], 422);
        }

        // 5. Guardar archivos
        $ciFrontPath = $request->file('ci_front')->store('pending_docs', 'public');
        $ciBackPath = $request->file('ci_back')->store('pending_docs', 'public');
        $userPhotoPath = $request->file('user_photo')->store('pending_docs', 'public');

        // 6. Crear el registro
        $token = Str::random(64);

        $pendingUser = PendingUser::create([
            'name' => $request->name,
            'lastname' => $request->lastname,
            'surname' => $request->surname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'id_number' => $request->id_number,
            'saga_code' => $request->saga_code,
            'career_id' => $request->career_id,
            'ci_front' => $ciFrontPath,
            'ci_back' => $ciBackPath,
            'user_photo' => $userPhotoPath,
            'verification_token' => $token,
            'email_verified' => false,
        ]);

        // 7. Enviar correo
        try {
           Mail::to($pendingUser->email)->send(new VerifyPendingEmail($pendingUser));
        } catch (\Exception $e) {
            \Log::error("Error enviando correo de verificación: " . $e->getMessage());
        }
// ✅ NOTIFICACIÓN AL ADMIN - Después de crear el pendingUser
$admins = \App\Models\User::where('role', 'admin')->get();
if ($admins->count() > 0) {
    foreach ($admins as $admin) {
        $admin->notify(new \App\Notifications\CustomNotification(
            'new_registration',
            '👤 Nuevo Registro Pendiente',
            "El estudiante {$request->name} {$request->lastname} ha solicitado registro y está esperando aprobación.",
            [
                'pending_user_id' => $pendingUser->id,
                'student_name' => $request->name . ' ' . $request->lastname,
                'student_email' => $request->email
            ]
        ));
    }
    \Illuminate\Support\Facades\Log::info("Notificación de nuevo registro enviada a administradores");
}
        return response()->json([
            'message' => 'Registro recibido. Por favor, verifica tu correo electrónico para continuar.'
        ], 201);
    }
    
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            // ✅ LOG DE AUDITORÍA: Intento de login fallido
            AuditLog::create([
                'user_id' => null,
                'action' => 'Intento de inicio de sesión fallido',
                'entity_type' => 'auth',
                'old_data' => ['email' => $request->email],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'severity' => 'warning'
            ]);
            
            return response()->json([
                'errors' => ['email' => ['Las credenciales son incorrectas.']]
            ], 422);
        }

        // ✅ LOG DE AUDITORÍA: Login exitoso
        $this->logAudit('Inicio de sesión exitoso', [
            'entity_type' => 'auth',
            'entity_id' => $user->id,
            'severity' => 'info'
        ]);

        return response()->json([
            'user' => $user, 
            'token' => $user->createToken('auth_token')->plainTextToken,
        ]);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        
        // ✅ LOG DE AUDITORÍA: Cierre de sesión
        $this->logAudit('Cierre de sesión', [
            'entity_type' => 'auth',
            'entity_id' => $user->id,
            'severity' => 'info'
        ]);
        
        $result = $request->user()->currentAccessToken()->delete();

        if ($result) {
            return response()->json([
                'code' => 200, 
                'message' => 'Logout success'
            ]);
        }

        return response()->json([
            'code' => 400, 
            'message' => 'Logout Failed'
        ], 400);
    }
    
    public function verifyEmail($token)
    {
        $pending = PendingUser::where('verification_token', $token)->first();

        if (!$pending) {
            return response()->json(['message' => 'El enlace de verificación ha expirado o es inválido.'], 404);
        }

        $pending->update([
            'email_verified' => true,
            'verification_token' => null
        ]);

        return "¡Correo verificado con éxito! Tu solicitud ahora será revisada por la administración de la EMI.";
    }
}