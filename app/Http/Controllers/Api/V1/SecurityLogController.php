<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SecurityLog;
use App\Models\ExamAttempt;
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
        $request->validate([
            'exam_attempt_id' => 'nullable|integer', // 🔥 CAMBIAR A nullable
            'event_type' => 'required|string|max:255',
            'details' => 'nullable|string|max:1000'
        ]);
        
        $user = auth()->user();
        
        // 🔥 ELIMINAR o COMENTAR esta validación
        // $examAttempt = ExamAttempt::where('id', $request->exam_attempt_id)
        //     ->where('student_id', $user->id)
        //     ->first();
        //     
        // if (!$examAttempt) {
        //     return response()->json(['error' => 'No autorizado'], 403);
        // }
        
        // Contar violaciones en los últimos 30 minutos
        $violationCount = SecurityLog::where('user_id', $user->id)
            ->where('exam_attempt_id', $request->exam_attempt_id)
            ->where('event_type', $request->event_type)
            ->where('created_at', '>', now()->subMinutes(30))
            ->count();
        
        // Configurar límites por tipo de violación
        $limits = [
            'tab_switch' => 3,
            'mouse_leave' => 5,
            'copy_attempt' => 3,
            'devtools_opened' => 2,
            'screenshot_attempt' => 3,
            'reload_attempt' => 1,
            'tab_close_attempt' => 1,
            'view_source_attempt' => 2,
            'drag_attempt' => 5,
            'drop_attempt' => 3,
            'window_resize' => 10,
            'fullscreen_exit' => 3
        ];
        
        $limit = $limits[$request->event_type] ?? 3;
        $currentViolation = $violationCount + 1;
        $shouldInvalidate = $currentViolation >= $limit;
        
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
        
        // Si se alcanzó el límite, invalidar el examen modular
        if ($shouldInvalidate && $request->exam_attempt_id) {
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
            
            // 🔥 Invalidar en modular_exam_attempts (no en exam_attempts)
            DB::table('modular_exam_attempts')
                ->where('id', $request->exam_attempt_id)
                ->update([
                    'status' => 'invalidated',
                    'updated_at' => now()
                ]);
            
            return response()->json([
                'success' => true,
                'status' => 'exam_invalidated',
                'message' => 'El examen ha sido invalidado por múltiples intentos de violar las normas de seguridad.',
                'violation_count' => $currentViolation,
                'limit' => $limit
            ]);
        }
        
        // Calcular advertencias restantes
        $remainingWarnings = $limit - $currentViolation;
        $warningMessage = null;
        
        if ($remainingWarnings <= 2 && $remainingWarnings > 0) {
            $warningMessages = [
                1 => "⚠️ ADVERTENCIA: Último intento permitido. Cualquier otra violación invalidará tu examen.",
                2 => "⚠️ ADVERTENCIA: Te quedan 2 intentos antes de que tu examen sea invalidado."
            ];
            $warningMessage = $warningMessages[$remainingWarnings] ?? "⚠️ ADVERTENCIA: {$remainingWarnings} intento(s) restante(s)";
        }
        
        return response()->json([
            'success' => true,
            'status' => 'logged',
            'message' => 'Evento registrado',
            'log' => $log,
            'violation_count' => $currentViolation,
            'remaining_warnings' => $remainingWarnings,
            'warning' => $warningMessage,
            'should_invalidate' => false
        ], 201);
        
    } catch (\Exception $e) {
        Log::error('Error en SecurityLogController@store: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error' => 'Error al registrar el evento',
            'message' => $e->getMessage()
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
            // Verificar permisos
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
                    // Obtener datos del estudiante
                    $examAttempt = ExamAttempt::with('user')->find($log->exam_attempt_id);
                    
                    // Contar violaciones por tipo
                    $violationsByType = SecurityLog::where('exam_attempt_id', $log->exam_attempt_id)
                        ->select('event_type', DB::raw('count(*) as total'))
                        ->groupBy('event_type')
                        ->get()
                        ->pluck('total', 'event_type')
                        ->toArray();
                    
                    return [
                        'exam_attempt_id' => $log->exam_attempt_id,
                        'student_name' => $examAttempt?->user?->full_name ?? $examAttempt?->user?->name ?? 'Desconocido',
                        'email' => $examAttempt?->user?->email ?? '-',
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
     * Obtener logs de seguridad por examen (con detalles completos)
     * GET /api/v1/admin/security/logs/{examAttemptId}
     */
    public function getLogsByExam($examAttemptId)
    {
        try {
            // Verificar permisos
            if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
            
            // Obtener información del examen y estudiante
            $examAttempt = DB::table('exam_attempts')
                ->leftJoin('users', 'exam_attempts.student_id', '=', 'users.id')
                ->leftJoin('quizzes', 'exam_attempts.quiz_id', '=', 'quizzes.id')
                ->where('exam_attempts.id', $examAttemptId)
                ->select(
                    'exam_attempts.*',
                    'users.name as student_name',
                    'users.lastname as student_lastname',
                    'users.email as student_email',
                    'quizzes.title as quiz_title'
                )
                ->first();
            
            if (!$examAttempt) {
                return response()->json(['error' => 'Examen no encontrado'], 404);
            }
            
            // Obtener logs de seguridad
            $logs = DB::table('security_logs')
                ->where('exam_attempt_id', $examAttemptId)
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Estadísticas
            $stats = [
                'total_violations' => $logs->count(),
                'by_type' => $logs->groupBy('event_type')->map->count(),
                'tab_switches' => $logs->where('event_type', 'tab_switch')->count(),
                'devtools_attempts' => $logs->where('event_type', 'devtools_opened')->count(),
                'screenshot_attempts' => $logs->where('event_type', 'screenshot_attempt')->count(),
                'mouse_leaves' => $logs->where('event_type', 'mouse_leave')->count(),
            ];
            
            // Verificar si fue invalidado
            $wasInvalidated = $logs->contains(function($log) {
                return $log->event_type === 'exam_invalidated';
            });
            
            $fullName = trim(($examAttempt->student_name ?? '') . ' ' . ($examAttempt->student_lastname ?? ''));
            
            return response()->json([
                'success' => true,
                'exam' => $examAttempt,
                'student' => [
                    'name' => $fullName ?: 'Desconocido',
                    'email' => $examAttempt->student_email ?? '-'
                ],
                'violations' => $logs,
                'stats' => $stats,
                'was_invalidated' => $wasInvalidated
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en getLogsByExam: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener los logs del examen',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener logs de un examen (para admin) - Método alternativo
     * GET /api/v1/admin/security/index/{examAttemptId}
     */
    public function index($examAttemptId)
    {
        try {
            // Verificar permisos de admin
            if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
            
            $logs = SecurityLog::where('exam_attempt_id', $examAttemptId)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Estadísticas
            $stats = [
                'total' => $logs->count(),
                'by_type' => $logs->groupBy('event_type')->map->count(),
                'tab_switches' => $logs->where('event_type', 'tab_switch')->count(),
                'devtools_attempts' => $logs->where('event_type', 'devtools_opened')->count(),
                'screenshot_attempts' => $logs->where('event_type', 'screenshot_attempt')->count(),
                'mouse_leaves' => $logs->where('event_type', 'mouse_leave')->count(),
            ];
            
            return response()->json([
                'success' => true,
                'logs' => $logs,
                'stats' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar logs de un examen a PDF
     * GET /api/v1/admin/security/export/{examAttemptId}
     */
    public function exportPdf($examAttemptId)
    {
        try {
            // Verificar permisos
            if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
            
            $logs = DB::table('security_logs')
                ->where('exam_attempt_id', $examAttemptId)
                ->orderBy('created_at', 'desc')
                ->get();
            
            $examAttempt = DB::table('exam_attempts')
                ->leftJoin('users', 'exam_attempts.student_id', '=', 'users.id')
                ->where('exam_attempts.id', $examAttemptId)
                ->select('exam_attempts.*', 'users.name', 'users.lastname', 'users.email')
                ->first();
            
            $fullName = trim(($examAttempt->name ?? '') . ' ' . ($examAttempt->lastname ?? ''));
            
            $html = $this->generateSecurityReportHTML($logs, $examAttempt, $fullName);
            
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('a4', 'portrait');
            
            return $pdf->download("seguridad_examen_{$examAttemptId}.pdf");
            
        } catch (\Exception $e) {
            Log::error('Error en exportPdf: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al exportar PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generar HTML para reporte de seguridad
     */
    private function generateSecurityReportHTML($logs, $examAttempt, $studentName)
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Reporte de Seguridad - Examen</title>
            <style>
                @page { margin: 2cm; }
                body { font-family: Arial, sans-serif; font-size: 10px; }
                h1 { text-align: center; color: #1e293b; font-size: 16px; }
                .header { text-align: center; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px; text-align: center; }
                td { border: 1px solid #cbd5e1; padding: 8px; }
                .text-center { text-align: center; }
                .violation-critical { color: #e11d48; font-weight: bold; }
                .violation-warning { color: #f59e0b; }
                .footer { margin-top: 30px; text-align: center; font-size: 8px; color: #94a3b8; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>REPORTE DE SEGURIDAD DEL EXAMEN</h1>
                <p>Sistema de Evaluación de Idiomas - EmiSystem</p>
                <p>Generado por: ' . auth()->user()->name . ' | Fecha: ' . now()->format('d/m/Y H:i') . '</p>
            </div>
            
            <h3>Información del Estudiante</h3>
            <table>
                <tr><th width="30%">Nombre</th><td>' . $studentName . '</td></tr>
                <tr><th>Email</th><td>' . ($examAttempt->email ?? '-') . '</td></tr>
                <tr><th>Examen</th><td>' . ($examAttempt->quiz_title ?? '-') . '</td></tr>
                <tr><th>Fecha</th><td>' . now()->format('d/m/Y H:i:s') . '</td></tr>
            </table>
            
            <h3>Registro de Violaciones</h3>
            <tr>
                <thead>
                    <tr><th>Fecha/Hora</th><th>Tipo de Evento</th><th>Detalles</th><th># Violación</th></tr>
                </thead>
                <tbody>';
        
        foreach ($logs as $log) {
            $rowClass = $log->event_type === 'exam_invalidated' ? 'violation-critical' : '';
            $rowClass = in_array($log->event_type, ['tab_switch', 'devtools_opened', 'screenshot_attempt']) ? 'violation-warning' : $rowClass;
            
            $html .= '
                <tr class="' . $rowClass . '">
                    <td class="text-center">' . date('d/m/Y H:i:s', strtotime($log->created_at)) . '</td>
                    <td>' . strtoupper(str_replace('_', ' ', $log->event_type)) . '</td>
                    <td>' . ($log->details ?? '-') . '</td>
                    <td class="text-center">' . ($log->violation_count ?? 1) . '</td>
                </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
            
            <div class="footer">
                <p>Documento generado automáticamente por EmiSystem - Reporte de Seguridad</p>
                <p>ID de verificación: ' . uniqid() . '</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Obtener resumen de violaciones para un estudiante (para el propio estudiante)
     * GET /api/v1/student/my-violations
     */
    public function myViolations(Request $request)
    {
        try {
            $violations = SecurityLog::where('user_id', auth()->id())
                ->whereIn('event_type', ['tab_switch', 'devtools_opened', 'screenshot_attempt', 'mouse_leave'])
                ->select('event_type', 'created_at', 'exam_attempt_id')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();
            
            return response()->json([
                'success' => true,
                'total_violations' => $violations->count(),
                'violations' => $violations
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Limpiar logs antiguos (para admin)
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
                'message' => "Se eliminaron {$deleted} logs antiguos",
                'deleted_count' => $deleted
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}