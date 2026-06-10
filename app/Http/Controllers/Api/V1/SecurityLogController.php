<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SecurityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class SecurityLogController extends Controller
{
    /**
     * Registrar un evento de seguridad (desde el frontend)
     * POST /api/v1/student/log-security-event
     */
    public function store(Request $request)
{
    try {
        Log::info('🔐 Security Log Store called', $request->all());
        
        $request->validate([
            'exam_attempt_id' => 'nullable|integer',
            'event_type' => 'required|string|max:255',
            'details' => 'nullable|string|max:1000'
        ]);
        
        $user = auth()->user();
        
        if (!$user) {
            Log::error('No authenticated user');
            return response()->json(['error' => 'No autenticado'], 401);
        }
        
        // 🔥 1. Verificar si el usuario ya está bloqueado
        if ($user->is_blocked) {
            Log::warning("Usuario bloqueado intenta registrar evento: {$user->id}");
            return response()->json([
                'success' => false,
                'error' => 'Tu cuenta está bloqueada. No puedes continuar.',
                'status' => 'user_blocked'
            ], 403);
        }
        
        // 🔥 2. Verificar si el examen ya fue invalidado
        if ($request->exam_attempt_id) {
            $attempt = DB::table('modular_exam_attempts')
                ->where('id', $request->exam_attempt_id)
                ->first();
            
            if ($attempt && $attempt->status === 'invalidated') {
                Log::warning("Intento de registrar evento en examen invalidado: {$request->exam_attempt_id}");
                return response()->json([
                    'success' => false,
                    'error' => 'Este examen ya fue invalidado. No se pueden registrar más eventos.',
                    'status' => 'exam_invalidated'
                ], 403);
            }
        }
        
        // Obtener límite desde el modelo
        $limit = SecurityLog::EVENT_LIMITS[$request->event_type] ?? 3;
        
        // Contar violaciones recientes del mismo tipo (últimos 30 minutos)
        $violationCount = SecurityLog::where('user_id', $user->id)
            ->where('exam_attempt_id', $request->exam_attempt_id)
            ->where('event_type', $request->event_type)
            ->where('created_at', '>', now()->subMinutes(30))
            ->count();
        
        $currentViolation = $violationCount + 1;
        $shouldInvalidate = $currentViolation >= $limit;
        
        Log::info("Evento: {$request->event_type}, Violación: {$currentViolation}/{$limit}, Invalidar: " . ($shouldInvalidate ? 'SI' : 'NO'));
        
        // Crear el log
        $log = SecurityLog::create([
            'user_id' => $user->id,
            'exam_attempt_id' => $request->exam_attempt_id,
            'event_type' => $request->event_type,
            'details' => $request->details,
            'violation_count' => $currentViolation,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        Log::info('Security log creado ID: ' . $log->id);
        
        // 🔥 3. Si se alcanzó el límite, invalidar el examen y bloquear al usuario
        if ($shouldInvalidate && $request->exam_attempt_id) {
            Log::info('Invalidando examen y bloqueando usuario: ' . $request->exam_attempt_id);
            
            // Registrar evento de invalidación
            SecurityLog::create([
                'user_id' => $user->id,
                'exam_attempt_id' => $request->exam_attempt_id,
                'event_type' => 'exam_invalidated',
                'details' => "Examen invalidado por múltiples violaciones: {$request->event_type} (total: {$currentViolation})",
                'violation_count' => $currentViolation,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            // Invalidar en modular_exam_attempts
            DB::table('modular_exam_attempts')
                ->where('id', $request->exam_attempt_id)
                ->update([
                    'status' => 'invalidated',
                    'completed_at' => now(),
                    'updated_at' => now()
                ]);
            
            // 🔥 Bloquear al usuario
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'is_blocked' => true,
                    'blocked_at' => now(),
                    'blocked_reason' => "Examen modular invalidado por múltiples violaciones: {$request->event_type}",
                    'updated_at' => now()
                ]);
            
            // 🔥 Invalidar todos los tokens del usuario
            DB::table('personal_access_tokens')
                ->where('tokenable_id', $user->id)
                ->delete();
            
            return response()->json([
                'success' => true,
                'status' => 'exam_invalidated',
                'message' => 'El examen ha sido invalidado y tu cuenta ha sido bloqueada por violaciones de seguridad.',
                'violation_count' => $currentViolation,
                'limit' => $limit,
                'user_blocked' => true
            ]);
        }
        
        // Calcular advertencias restantes
        $remainingWarnings = max(0, $limit - $currentViolation);
        $warningMessage = null;
        
        if ($remainingWarnings <= 2 && $remainingWarnings > 0) {
            $warningMessages = [
                1 => "⚠️ ADVERTENCIA: Último intento permitido. Cualquier otra violación invalidará tu examen y bloqueará tu cuenta.",
                2 => "⚠️ ADVERTENCIA: Te quedan 2 intentos antes de que tu examen sea invalidado y tu cuenta bloqueada."
            ];
            $warningMessage = $warningMessages[$remainingWarnings] ?? "⚠️ ADVERTENCIA: {$remainingWarnings} intento(s) restante(s)";
        }
        
        return response()->json([
            'success' => true,
            'status' => 'logged',
            'message' => 'Evento registrado',
            'log_id' => $log->id,
            'violation_count' => $currentViolation,
            'limit' => $limit,
            'remaining_warnings' => $remainingWarnings,
            'warning' => $warningMessage,
            'should_invalidate' => false
        ], 201);
        
    } catch (\Exception $e) {
        Log::error('Error en SecurityLogController@store: ' . $e->getMessage());
        Log::error($e->getTraceAsString());
        return response()->json([
            'success' => false,
            'error' => 'Error al registrar el evento',
            'message' => $e->getMessage()
        ], 500);
    }
}
    
    /**
     * Obtener resumen de violaciones para un estudiante
     * GET /api/v1/student/my-violations
     */
    public function myViolations(Request $request)
    {
        try {
            $violations = SecurityLog::where('user_id', auth()->id())
                ->whereIn('event_type', array_keys(SecurityLog::EVENT_TYPES))
                ->select('event_type', 'details', 'created_at', 'exam_attempt_id', 'violation_count')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                ->map(function($log) {
                    return [
                        'type' => $log->event_type,
                        'type_name' => SecurityLog::EVENT_TYPES[$log->event_type] ?? $log->event_type,
                        'details' => $log->details,
                        'date' => $log->created_at->format('d/m/Y H:i:s'),
                        'violation_number' => $log->violation_count,
                        'exam_id' => $log->exam_attempt_id
                    ];
                });
            
            $totalViolations = SecurityLog::where('user_id', auth()->id())
                ->whereIn('event_type', array_keys(SecurityLog::EVENT_TYPES))
                ->count();
            
            return response()->json([
                'success' => true,
                'total_violations' => $totalViolations,
                'violations' => $violations
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en myViolations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener exámenes con violaciones (para admin)
     * GET /api/v1/admin/security/exams-with-violations
     */
    public function getExamsWithViolations()
    {
        try {
            if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
            
            $exams = SecurityLog::select('exam_attempt_id', 
                    DB::raw('count(*) as violations_count'),
                    DB::raw('MAX(created_at) as last_violation'),
                    DB::raw('GROUP_CONCAT(DISTINCT event_type) as event_types')
                )
                ->whereNotNull('exam_attempt_id')
                ->groupBy('exam_attempt_id')
                ->orderBy('last_violation', 'desc')
                ->get()
                ->map(function($log) {
                    $student = DB::table('users')
                        ->join('modular_exam_attempts', 'users.id', '=', 'modular_exam_attempts.student_id')
                        ->where('modular_exam_attempts.id', $log->exam_attempt_id)
                        ->select('users.name', 'users.lastname', 'users.email')
                        ->first();
                    
                    $violationsByType = SecurityLog::where('exam_attempt_id', $log->exam_attempt_id)
                        ->select('event_type', DB::raw('count(*) as total'))
                        ->groupBy('event_type')
                        ->get()
                        ->pluck('total', 'event_type')
                        ->toArray();
                    
                    return [
                        'exam_attempt_id' => $log->exam_attempt_id,
                        'student_name' => trim(($student->name ?? '') . ' ' . ($student->lastname ?? '')),
                        'email' => $student->email ?? '-',
                        'violations_count' => $log->violations_count,
                        'last_violation' => $log->last_violation,
                        'violations_by_type' => $violationsByType
                    ];
                });
            
            return response()->json([
                'success' => true,
                'exams' => $exams
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en getExamsWithViolations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener logs de seguridad por examen
     * GET /api/v1/admin/security/logs/{examAttemptId}
     */
    public function getLogsByExam($examAttemptId)
    {
        try {
            if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
            
            $logs = SecurityLog::where('exam_attempt_id', $examAttemptId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($log) {
                    return [
                        'id' => $log->id,
                        'event_type' => $log->event_type,
                        'event_name' => SecurityLog::EVENT_TYPES[$log->event_type] ?? $log->event_type,
                        'details' => $log->details,
                        'violation_count' => $log->violation_count,
                        'created_at' => $log->created_at->format('d/m/Y H:i:s'),
                        'ip_address' => $log->ip_address,
                        'user_agent' => $log->user_agent
                    ];
                });
            
            $stats = [
                'total_violations' => $logs->count(),
                'by_type' => $logs->groupBy('event_type')->map->count(),
                'max_violations_per_type' => $logs->groupBy('event_type')->map(function($group) {
                    return $group->max('violation_count');
                })
            ];
            
            $student = DB::table('modular_exam_attempts')
                ->join('users', 'modular_exam_attempts.student_id', '=', 'users.id')
                ->where('modular_exam_attempts.id', $examAttemptId)
                ->select('users.name', 'users.lastname', 'users.email')
                ->first();
            
            return response()->json([
                'success' => true,
                'exam_attempt_id' => $examAttemptId,
                'student' => [
                    'name' => trim(($student->name ?? '') . ' ' . ($student->lastname ?? '')),
                    'email' => $student->email ?? '-'
                ],
                'stats' => $stats,
                'logs' => $logs
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en getLogsByExam: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Exportar logs a PDF
     * GET /api/v1/admin/security/export/{examAttemptId}
     */
    public function exportPdf($examAttemptId)
    {
        try {
            if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
            
            $logs = SecurityLog::where('exam_attempt_id', $examAttemptId)
                ->orderBy('created_at', 'desc')
                ->get();
            
            $student = DB::table('modular_exam_attempts')
                ->join('users', 'modular_exam_attempts.student_id', '=', 'users.id')
                ->where('modular_exam_attempts.id', $examAttemptId)
                ->select('users.name', 'users.lastname', 'users.email')
                ->first();
            
            $studentName = trim(($student->name ?? '') . ' ' . ($student->lastname ?? ''));
            
            $html = $this->generateSecurityReportHTML($logs, $examAttemptId, $studentName, $student->email ?? '-');
            
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('a4', 'portrait');
            
            return $pdf->download("seguridad_examen_{$examAttemptId}.pdf");
            
        } catch (\Exception $e) {
            Log::error('Error en exportPdf: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al exportar PDF'
            ], 500);
        }
    }
    
    /**
     * Generar HTML para reporte de seguridad
     */
    private function generateSecurityReportHTML($logs, $examAttemptId, $studentName, $studentEmail)
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Reporte de Seguridad - Examen Modular</title>
            <style>
                @page { margin: 2cm; }
                body { font-family: Arial, sans-serif; font-size: 10px; }
                h1 { text-align: center; color: #1e293b; font-size: 16px; margin-bottom: 5px; }
                h2 { font-size: 14px; color: #334155; margin-top: 20px; }
                .header { text-align: center; margin-bottom: 20px; }
                .subtitle { color: #64748b; font-size: 10px; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                th { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px; text-align: center; font-weight: bold; }
                td { border: 1px solid #cbd5e1; padding: 8px; }
                .text-center { text-align: center; }
                .text-left { text-align: left; }
                .violation-critical { color: #dc2626; font-weight: bold; }
                .violation-warning { color: #f59e0b; }
                .footer { margin-top: 30px; text-align: center; font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 15px; }
                .info-box { background: #f8fafc; padding: 10px; border-radius: 8px; margin-bottom: 15px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>REPORTE DE SEGURIDAD</h1>
                <div class="subtitle">Examen Modular - Sistema de Evaluación de Idiomas</div>
            </div>
            
            <div class="info-box">
                <table style="border: none; width: 100%;">
                    <tr style="border: none;">
                        <td style="border: none; width: 30%;"><strong>Estudiante:</strong></td>
                        <td style="border: none;">' . htmlspecialchars($studentName) . '</td>
                    </tr>
                    <tr style="border: none;">
                        <td style="border: none;"><strong>Email:</strong></td>
                        <td style="border: none;">' . htmlspecialchars($studentEmail) . '</td>
                    </tr>
                    <tr style="border: none;">
                        <td style="border: none;"><strong>ID Examen:</strong></td>
                        <td style="border: none;">' . $examAttemptId . '</td>
                    </tr>
                    <tr style="border: none;">
                        <td style="border: none;"><strong>Fecha del reporte:</strong></td>
                        <td style="border: none;">' . now()->format('d/m/Y H:i:s') . '</td>
                    </tr>
                    <tr style="border: none;">
                        <td style="border: none;"><strong>Total violaciones:</strong></td>
                        <td style="border: none;">' . $logs->count() . '</td>
                    </tr>
                </table>
            </div>
            
            <h2>Registro de Violaciones</h2>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha/Hora</th>
                        <th>Tipo de Evento</th>
                        <th>Detalles</th>
                        <th># Violación</th>
                    </tr>
                </thead>
                <tbody>';
        
        $counter = 1;
        foreach ($logs as $log) {
            $rowClass = $log->event_type === 'exam_invalidated' ? 'violation-critical' : '';
            $rowClass = in_array($log->event_type, ['tab_switch', 'devtools_opened', 'screenshot_attempt', 'f12_blocked', 'devtools_blocked']) ? 'violation-warning' : $rowClass;
            
            $eventName = SecurityLog::EVENT_TYPES[$log->event_type] ?? $log->event_type;
            
            $html .= '
                <tr class="' . $rowClass . '">
                    <td class="text-center">' . $counter++ . '</td>
                    <td class="text-center">' . $log->created_at->format('d/m/Y H:i:s') . '</td>
                    <td class="text-left">' . htmlspecialchars($eventName) . '</td>
                    <td class="text-left">' . htmlspecialchars($log->details ?? '-') . '</td>
                    <td class="text-center">' . ($log->violation_count ?? 1) . '</td>
                </tr>';
        }
        
        if ($logs->isEmpty()) {
            $html .= '
                <tr>
                    <td colspan="5" class="text-center">No se registraron violaciones de seguridad</td>
                </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
            
            <div class="footer">
                <p>Documento generado automáticamente por EmiSystem - Reporte de Seguridad</p>
                <p>Este documento es una evidencia de las violaciones de seguridad durante el examen modular.</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Limpiar logs antiguos (admin)
     * DELETE /api/v1/admin/security/clean-logs
     */
    public function cleanOldLogs(Request $request)
    {
        try {
            if (auth()->user()->role !== 'admin') {
                return response()->json(['error' => 'No autorizado'], 403);
            }
            
            $days = $request->input('days', 30);
            $deleted = SecurityLog::where('created_at', '<', now()->subDays($days))->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Se eliminaron {$deleted} logs antiguos (mayores a {$days} días)",
                'deleted_count' => $deleted
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en cleanOldLogs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}