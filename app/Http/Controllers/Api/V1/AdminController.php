<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog; // ← Agregar
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
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

    public function index(Request $request)
    {
        return User::whereIn('role', ['admin', 'teacher'])
            ->when($request->search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('lastname', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role' => 'required|in:admin,teacher',
            'picture' => 'nullable|image|max:2048',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        if ($request->hasFile('picture')) {
            $validated['picture'] = $request->file('picture')->store('admins', 'public');
        }
        
        $user = User::create($validated);
        
        // ✅ LOG DE AUDITORÍA: Creación de administrador/docente
        $this->logAudit('Creación de ' . ($user->role === 'admin' ? 'administrador' : 'docente'), [
            'entity_type' => $user->role,
            'entity_id' => $user->id,
            'new_data' => [
                'name' => $user->name,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'role' => $user->role
            ],
            'severity' => $user->role === 'admin' ? 'danger' : 'warning'
        ]);

        return response()->json($user, 201);
    }

    public function show($id)
    {
        try {
            $user = \App\Models\User::findOrFail($id);
            return response()->json($user);
        } catch (\Exception $e) {
            \Log::error("Error en show admin: " . $e->getMessage());
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }
    }

    public function update(Request $request, User $admin)
    {
        // Obtener datos antiguos
        $oldData = [
            'name' => $admin->name,
            'lastname' => $admin->lastname,
            'email' => $admin->email,
            'role' => $admin->role,
        ];
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $admin->id,
            'role' => 'required|in:admin,teacher',
            'picture' => 'nullable|image|max:2048',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('picture')) {
            if ($admin->picture) {
                Storage::disk('public')->delete($admin->picture);
            }
            $validated['picture'] = $request->file('picture')->store('admins', 'public');
        }

        $admin->update($validated);
        
        // ✅ LOG DE AUDITORÍA: Actualización de administrador/docente
        $newData = [
            'name' => $admin->name,
            'lastname' => $admin->lastname,
            'email' => $admin->email,
            'role' => $admin->role,
        ];
        
        if ($oldData != $newData) {
            $this->logAudit('Actualización de ' . ($admin->role === 'admin' ? 'administrador' : 'docente'), [
                'entity_type' => $admin->role,
                'entity_id' => $admin->id,
                'old_data' => $oldData,
                'new_data' => $newData,
                'severity' => 'warning'
            ]);
        }

        return response()->json($admin);
    }

    public function destroy(User $admin)
    {
        $adminName = $admin->name . ' ' . $admin->lastname;
        $adminRole = $admin->role;
        $adminId = $admin->id;
        $adminEmail = $admin->email;
        
        if ($admin->picture) {
            Storage::disk('public')->delete($admin->picture);
        }
        
        $admin->delete();
        
        // ✅ LOG DE AUDITORÍA: Eliminación de administrador/docente
        $this->logAudit('Eliminación de ' . ($adminRole === 'admin' ? 'administrador' : 'docente'), [
            'entity_type' => $adminRole,
            'entity_id' => $adminId,
            'old_data' => [
                'name' => $adminName,
                'email' => $adminEmail,
                'role' => $adminRole
            ],
            'severity' => 'danger'
        ]);

        return response()->json(null, 204);
    }
}