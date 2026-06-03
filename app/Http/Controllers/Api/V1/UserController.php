<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\AuditLog; // ← Agregar
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordChanged;

class UserController extends Controller
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

    /**
     * Display a listing of users based on role (Admin or Student).
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $role = $request->query('role', 'student');

        $users = User::where('role', $role)
            ->when($role === 'student', function ($query) {
                $query->with(['student.career']);
            })
            ->when($search, function ($query, $search) use ($role) {
                $query->where(function ($q) use ($search, $role) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('lastname', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");

                    if ($role === 'student') {
                        $q->orWhereHas('student', function ($subQuery) use ($search) {
                            $subQuery->where('saga_code', 'like', "%{$search}%")
                                     ->orWhereHas('career', function ($careerQuery) use ($search) {
                                         $careerQuery->where('name', 'like', "%{$search}%");
                                     });
                        });
                    }
                });
            })
            ->latest()
            ->paginate(10);

        return response()->json($users);
    }

    /**
     * Store a newly created user (and student profile if applicable).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'lastname'  => 'required|string|max:255',
            'surname'   => 'nullable|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:8',
            'role'      => 'required|in:admin,student',
            'career_id' => 'required_if:role,student|exists:careers,id',
            'semester'  => 'required_if:role,student|integer|between:1,10',
            'picture'   => 'nullable|image|max:2048',
            'idcard_picture' => 'nullable|image|max:2048',
            'saga_code' => 'nullable|string',
            'id_number' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $picturePath = null;
            if ($request->hasFile('picture')) {
                $picturePath = $request->file('picture')->store('profiles', 'public');
            }

            $user = User::create([
                'name'     => $validated['name'],
                'lastname' => $validated['lastname'],
                'surname'  => $validated['surname'] ?? null,
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role'     => $validated['role'],
                'picture'  => $picturePath,
            ]);

            $idcardPath = null;
            if ($request->hasFile('idcard_picture')) {
                $idcardPath = $request->file('idcard_picture')->store('idcards', 'public');
            }

            if ($user->role === 'student') {
                $user->student()->create([
                    'career_id'      => $request->career_id,
                    'semester'       => $request->semester,
                    'saga_code'      => $request->saga_code,
                    'id_number'      => $request->id_number,
                    'idcard_picture' => $idcardPath,
                ]);
            }

            DB::commit();
            
            // ✅ LOG DE AUDITORÍA: Creación de usuario
            $this->logAudit('Creación de ' . ($user->role === 'student' ? 'estudiante' : 'administrador'), [
                'entity_type' => $user->role,
                'entity_id' => $user->id,
                'new_data' => ['name' => $user->name, 'email' => $user->email, 'role' => $user->role],
                'severity' => $user->role === 'admin' ? 'danger' : 'info'
            ]);
            
            return response()->json([
                'message' => 'User created successfully',
                'user' => $user->load('student'),
                'profile_picture_url' => $user->picture ? Storage::url($user->picture) : null,
                'idcard_picture_url' => $user->student?->idcard_picture ? Storage::url($user->student->idcard_picture) : null,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            if (!empty($picturePath)) Storage::disk('public')->delete($picturePath);
            return response()->json([
                'message' => 'Error creating user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified user with their profile.
     */
    public function show(User $student)
    {
        return response()->json($student->load('student.career'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $student)
    {
        $oldData = [
            'name' => $student->name,
            'lastname' => $student->lastname,
            'surname' => $student->surname,
            'email' => $student->email,
        ];
        
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'lastname'  => 'required|string|max:255',
            'surname'   => 'nullable|string|max:255',
            'email'     => ['required', 'email', Rule::unique('users')->ignore($student->id)],
            'career_id' => 'required_if:role,student|exists:careers,id',
            'semester'  => 'required_if:role,student|integer|between:1,10',
            'password'  => 'nullable|min:8',
            'picture'   => 'nullable|image|max:2048',
            'idcard_picture' => 'nullable|image|max:2048',
        ]);

        DB::beginTransaction();
        try {
            $student->update([
                'name'     => $validated['name'],
                'lastname' => $validated['lastname'],
                'surname'  => $validated['surname'],
                'email'    => $validated['email'],
            ]);

            if ($request->filled('password')) {
                $student->update(['password' => Hash::make($request->password)]);
            }

            if ($student->role === 'student') {
                $student->student()->updateOrCreate(
                    ['user_id' => $student->id],
                    [
                        'career_id' => $request->career_id,
                        'semester'  => $request->semester,
                        'saga_code' => $request->saga_code,
                        'id_number' => $request->id_number,
                    ]
                );
            }

            // Update profile picture
            if ($request->hasFile('picture')) {
                if ($student->picture) {
                    Storage::disk('public')->delete($student->picture);
                }
                $picturePath = $request->file('picture')->store('profiles', 'public');
                $student->update(['picture' => $picturePath]);
            }

            // Update ID card picture
            if ($student->role === 'student' && $request->hasFile('idcard_picture')) {
                if ($student->student->idcard_picture) {
                    Storage::disk('public')->delete($student->student->idcard_picture);
                }
                $idcardPath = $request->file('idcard_picture')->store('idcards', 'public');
                $student->student()->update(['idcard_picture' => $idcardPath]);
            }

            DB::commit();
            
            // ✅ LOG DE AUDITORÍA: Actualización de usuario
            $newData = [
                'name' => $student->name,
                'lastname' => $student->lastname,
                'surname' => $student->surname,
                'email' => $student->email,
            ];
            
            // Solo registrar si hubo cambios
            if ($oldData != $newData) {
                $this->logAudit('Actualización de ' . ($student->role === 'student' ? 'estudiante' : 'administrador'), [
                    'entity_type' => $student->role,
                    'entity_id' => $student->id,
                    'old_data' => $oldData,
                    'new_data' => $newData,
                    'severity' => 'warning'
                ]);
            }
            
            return response()->json([
                'message' => 'User updated successfully',
                'user' => $student->load('student'),
                'profile_picture_url' => $student->picture ? Storage::url($student->picture) : null,
                'idcard_picture_url' => $student->student?->idcard_picture ? Storage::url($student->student->idcard_picture) : null,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Update failed', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $student)
    {
        $userName = $student->name . ' ' . $student->lastname;
        $userRole = $student->role;
        $userId = $student->id;
        
        $student->delete();
        
        // ✅ LOG DE AUDITORÍA: Eliminación de usuario
        $this->logAudit('Eliminación de ' . ($userRole === 'student' ? 'estudiante' : 'administrador'), [
            'entity_type' => $userRole,
            'entity_id' => $userId,
            'old_data' => ['name' => $userName, 'email' => $student->email],
            'severity' => 'danger'
        ]);
        
        return response()->json(null, 204);
    }
    
    public function exportPdf(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user || $user->role !== 'admin') {
                return response()->json(['error' => 'No autorizado'], 403);
            }
            
            // ✅ LOG DE AUDITORÍA: Exportación de PDF
            $this->logAudit('Exportación de reporte de estudiantes', [
                'entity_type' => 'report',
                'severity' => 'info'
            ]);
            
            $students = User::where('role', 'student')
                ->with(['student.career'])
                ->orderBy('lastname', 'asc')
                ->get();
            
            if ($students->isEmpty()) {
                return response()->json(['message' => 'No hay estudiantes para exportar'], 404);
            }
            
            $pdf = Pdf::loadView('pdf.students_report', [
                'students' => $students,
                'admin'    => $user->full_name,
            ]);
            
            $pdf->setPaper('letter', 'portrait');
            $pdf->setOptions([
                'defaultFont' => 'sans-serif',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ]);
            
            return $pdf->download('reporte_estudiantes_' . date('Y-m-d') . '.pdf');
            
        } catch (\Exception $e) {
            \Log::error('PDF Error: ' . $e->getMessage());
            \Log::error('Line: ' . $e->getLine());
            
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }
    
    public function updateProfile(Request $request)
    {
        $oldData = [
            'name' => $request->user()->name,
            'lastname' => $request->user()->lastname,
            'surname' => $request->user()->surname,
        ];
        
        $request->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'surname' => 'nullable|string|max:255',
            'picture' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user = $request->user();
        
        DB::transaction(function () use ($request, $user) {
            $user->name = $request->name;
            $user->lastname = $request->lastname;
            $user->surname = $request->surname;

            if ($request->hasFile('picture')) {
                if ($user->picture) {
                    Storage::disk('public')->delete($user->picture);
                }
                $user->picture = $request->file('picture')->store('profiles', 'public');
            }

            $user->save();
        });

        // ✅ LOG DE AUDITORÍA: Actualización de perfil propio
        $newData = [
            'name' => $user->name,
            'lastname' => $user->lastname,
            'surname' => $user->surname,
        ];
        
        if ($oldData != $newData) {
            $this->logAudit('Actualización de perfil propio', [
                'entity_type' => 'profile',
                'entity_id' => $user->id,
                'old_data' => $oldData,
                'new_data' => $newData,
                'severity' => 'warning'
            ]);
        }

        $userUpdated = $user->fresh($user->role === 'student' ? ['student.career'] : []);

        return response()->json([
            'message' => 'Perfil actualizado',
            'user' => $userUpdated
        ]);
    }
    
    // CAMBIAR CONTRASEÑA
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            // ✅ LOG DE AUDITORÍA: Intento fallido de cambio de contraseña
            $this->logAudit('Intento fallido de cambio de contraseña', [
                'entity_type' => 'security',
                'entity_id' => $user->id,
                'severity' => 'warning'
            ]);
            
            return response()->json([
                'message' => 'La contraseña actual es incorrecta.'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // ✅ LOG DE AUDITORÍA: Cambio de contraseña exitoso
        $this->logAudit('Cambio de contraseña exitoso', [
            'entity_type' => 'security',
            'entity_id' => $user->id,
            'severity' => 'danger'
        ]);

        // Registrar en ActivityLog (existente)
        \App\Models\ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'Cambio de contraseña exitoso',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
        ]);

        try {
            Mail::to($user->email)->send(new \App\Mail\PasswordChanged($user));
        } catch (\Exception $e) {
            \Log::error("Error enviando correo de seguridad: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Contraseña actualizada con éxito y notificación enviada.'
        ]);
    }
}