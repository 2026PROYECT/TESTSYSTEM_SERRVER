<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PendingUser;
use App\Models\User;
use App\Models\Career;
use App\Models\AuditLog; // ← Agregar
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class PendingUserController extends Controller
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

    public function index()
    {
        $pendingUsers = PendingUser::with('career')->get();
        
        return response()->json($pendingUsers);
    }

    public function approve($id)
    {
        try {
            $pendingUser = PendingUser::findOrFail($id);
            
            DB::beginTransaction();
            
            // Verificar si el email ya existe en users
            $existingUser = User::where('email', $pendingUser->email)->first();
            if ($existingUser) {
                return response()->json([
                    'error' => 'El email ya está registrado en el sistema'
                ], 422);
            }
            
            // Guardar datos del pendiente antes de aprobar
            $pendingData = [
                'name' => $pendingUser->name,
                'lastname' => $pendingUser->lastname,
                'surname' => $pendingUser->surname,
                'email' => $pendingUser->email,
                'saga_code' => $pendingUser->saga_code,
                'id_number' => $pendingUser->id_number,
            ];
            
            // Crear usuario en la tabla users con role 'student'
            $user = User::create([
                'name' => $pendingUser->name,
                'lastname' => $pendingUser->lastname,
                'surname' => $pendingUser->surname,
                'email' => $pendingUser->email,
                'password' => $pendingUser->password,
                'role' => 'student',
            ]);
            
            // Crear el perfil de estudiante
            $user->student()->create([
                'career_id' => $pendingUser->career_id,
                'semester' => 1,
                'saga_code' => $pendingUser->saga_code,
                'id_number' => $pendingUser->id_number,
                'idcard_picture' => $pendingUser->ci_front,
            ]);
            
            // Eliminar el registro pendiente
            $pendingUser->delete();
            
            DB::commit();
            
            // ✅ LOG DE AUDITORÍA: Aprobación de estudiante
            $this->logAudit('Aprobación de registro de estudiante', [
                'entity_type' => 'pending_user',
                'entity_id' => (int)$id,
                'new_data' => $pendingData,
                'severity' => 'info'
            ]);
            
            return response()->json([
                'message' => 'Usuario aprobado correctamente',
                'user' => $user
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al aprobar: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $pendingUser = PendingUser::findOrFail($id);
            
            // Guardar datos antes de eliminar
            $pendingData = [
                'name' => $pendingUser->name,
                'lastname' => $pendingUser->lastname,
                'email' => $pendingUser->email,
                'saga_code' => $pendingUser->saga_code,
                'id_number' => $pendingUser->id_number,
            ];
            
            // Eliminar archivos si existen
            if ($pendingUser->ci_front) {
                Storage::disk('public')->delete($pendingUser->ci_front);
            }
            if ($pendingUser->ci_back) {
                Storage::disk('public')->delete($pendingUser->ci_back);
            }
            if ($pendingUser->user_photo) {
                Storage::disk('public')->delete($pendingUser->user_photo);
            }
            
            $pendingUser->delete();
            
            // ✅ LOG DE AUDITORÍA: Rechazo de registro de estudiante
            $this->logAudit('Rechazo de registro de estudiante', [
                'entity_type' => 'pending_user',
                'entity_id' => (int)$id,
                'old_data' => $pendingData,
                'severity' => 'warning'
            ]);
            
            return response()->json([
                'message' => 'Solicitud rechazada y eliminada'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al rechazar: ' . $e->getMessage()
            ], 500);
        }
    }
}