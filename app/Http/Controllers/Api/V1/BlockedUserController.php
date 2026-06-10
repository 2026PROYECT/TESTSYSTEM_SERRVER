<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;

class BlockedUserController extends Controller
{
    /**
     * Obtener lista de usuarios bloqueados (con exámenes invalidados)
     */
    public function index()
    {
        try {
            $blockedUsers = DB::table('modular_exam_attempts')
                ->join('users', 'modular_exam_attempts.student_id', '=', 'users.id')
                ->where('modular_exam_attempts.status', '=', 'invalidated')
                ->select(
                    'users.id',
                    'users.name',
                    'users.lastname',
                    'users.email',
                    'modular_exam_attempts.id as attempt_id',
                    'modular_exam_attempts.status as exam_status',
                    'modular_exam_attempts.created_at as blocked_at'
                )
                ->orderBy('modular_exam_attempts.created_at', 'desc')
                ->get()
                ->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => trim($user->name . ' ' . ($user->lastname ?? '')),
                        'email' => $user->email,
                        'blocked_at' => $user->blocked_at,
                        'blocked_reason' => 'Violaciones de seguridad en examen modular',
                        'attempt_id' => $user->attempt_id,
                        'exam_status' => $user->exam_status
                    ];
                });
            
            $totalBlocked = DB::table('modular_exam_attempts')
                ->where('status', 'invalidated')
                ->distinct('student_id')
                ->count('student_id');
            
            $thisWeek = DB::table('modular_exam_attempts')
                ->where('status', 'invalidated')
                ->where('created_at', '>=', now()->startOfWeek())
                ->distinct('student_id')
                ->count('student_id');
            
            $thisMonth = DB::table('modular_exam_attempts')
                ->where('status', 'invalidated')
                ->where('created_at', '>=', now()->startOfMonth())
                ->distinct('student_id')
                ->count('student_id');
            
            return response()->json([
                'success' => true,
                'users' => $blockedUsers,
                'stats' => [
                    'total_blocked' => $totalBlocked,
                    'this_week' => $thisWeek,
                    'this_month' => $thisMonth,
                    'rehabilitated' => 0
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo usuarios bloqueados: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Obtener detalle de un usuario bloqueado
     */
    public function show($userId)
    {
        try {
            $invalidatedExams = DB::table('modular_exam_attempts')
                ->where('student_id', $userId)
                ->where('status', 'invalidated')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($exam) {
                    return [
                        'id' => $exam->id,
                        'status' => $exam->status,
                        'started_at' => $exam->started_at,
                        'completed_at' => $exam->completed_at,
                        'violations_count' => DB::table('security_logs')
                            ->where('exam_attempt_id', $exam->id)
                            ->count()
                    ];
                });
            
            if ($invalidatedExams->isEmpty()) {
                return response()->json(['error' => 'Usuario no tiene exámenes invalidados'], 404);
            }
            
            $user = DB::table('users')->where('id', $userId)->first();
            
            $violations = DB::table('security_logs')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function($log) {
                    return [
                        'id' => $log->id,
                        'event_type' => $log->event_type,
                        'details' => $log->details,
                        'violation_count' => $log->violation_count,
                        'created_at' => $log->created_at,
                        'exam_attempt_id' => $log->exam_attempt_id
                    ];
                });
            
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => trim($user->name . ' ' . ($user->lastname ?? '')),
                    'email' => $user->email,
                    'blocked_at' => $invalidatedExams->first()['started_at'],
                    'blocked_reason' => 'Violaciones de seguridad en examen modular'
                ],
                'invalidated_exams' => $invalidatedExams,
                'violations' => $violations,
                'total_violations' => $violations->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en show: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Rehabilitar un usuario (cambiar examen invalidado a expired)
     */
    public function rehabilitate($userId)
    {
        try {
            $updated = DB::table('modular_exam_attempts')
                ->where('student_id', $userId)
                ->where('status', 'invalidated')
                ->update([
                    'status' => 'expired',
                    'updated_at' => now()
                ]);
            
            if ($updated === 0) {
                return response()->json(['error' => 'No hay exámenes invalidados para este usuario'], 404);
            }
            
            Log::info("Usuario {$userId} rehabilitado por admin " . auth()->id());
            
            return response()->json([
                'success' => true,
                'message' => 'Usuario rehabilitado exitosamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error rehabilitando usuario: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Rehabilitar un examen específico
     */
    public function rehabilitateExam($attemptId)
    {
        try {
            $updated = DB::table('modular_exam_attempts')
                ->where('id', $attemptId)
                ->where('status', 'invalidated')
                ->update([
                    'status' => 'expired',
                    'updated_at' => now()
                ]);
            
            if ($updated === 0) {
                return response()->json(['error' => 'Examen no encontrado o no está invalidado'], 404);
            }
            
            Log::info("Examen {$attemptId} rehabilitado por admin " . auth()->id());
            
            return response()->json([
                'success' => true,
                'message' => 'Examen rehabilitado exitosamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error rehabilitando examen: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Eliminar un usuario
     */
    public function destroy($userId)
    {
        try {
            DB::table('users')->where('id', $userId)->delete();
            Log::warning("Usuario {$userId} eliminado por admin " . auth()->id());
            return response()->json(['success' => true, 'message' => 'Usuario eliminado exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Obtener estadísticas
     */
    public function stats()
    {
        try {
            $totalBlocked = DB::table('modular_exam_attempts')
                ->where('status', 'invalidated')
                ->distinct('student_id')
                ->count('student_id');
            
            $blockedByPeriod = DB::table('modular_exam_attempts')
                ->where('status', 'invalidated')
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(distinct student_id) as total'))
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(7)
                ->get();
            
            return response()->json([
                'success' => true,
                'stats' => [
                    'total_blocked' => $totalBlocked,
                    'by_period' => $blockedByPeriod
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Generar reporte PDF de un usuario bloqueado
     * GET /api/v1/admin/blocked-users/report/{userId}
     */
    public function generateUserReport($userId)
    {
        try {
            ini_set('memory_limit', '256M');
            
            $user = DB::table('users')->where('id', $userId)->first();
            
            if (!$user) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }
            
            $invalidatedExams = DB::table('modular_exam_attempts')
                ->where('student_id', $userId)
                ->where('status', 'invalidated')
                ->orderBy('created_at', 'desc')
                ->get();
            
            foreach ($invalidatedExams as $exam) {
                $exam->violations_count = DB::table('security_logs')
                    ->where('exam_attempt_id', $exam->id)
                    ->count();
            }
            
            $violations = DB::table('security_logs')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();
            
            // Generar UUID y QR (mismo patrón que funciona)
            $uuid = (string) Str::uuid();
            
            // Guardar en verifications
            DB::table('verifications')->insert([
                'uuid' => $uuid,
                'verifiable_id' => $userId,
                'verifiable_type' => 'App\Models\User',
                'type' => 'MODULAR_USER_REPORT',
                'metadata' => json_encode([
                    'user_id' => $userId,
                    'user_name' => $user->name . ' ' . ($user->lastname ?? ''),
                    'user_email' => $user->email,
                    'total_violations' => $violations->count(),
                    'invalidated_exams' => $invalidatedExams->map(function($e) {
                        return [
                            'attempt_id' => $e->id,
                            'started_at' => $e->started_at,
                            'violations_count' => $e->violations_count
                        ];
                    })->toArray(),
                    'generated_by' => auth()->id()
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Generar QR con URL de verificación (exactamente como en oral report)
            $qrCode = base64_encode(QrCode::format('svg')->size(100)->margin(0)->generate(url("/verify/{$uuid}")));
            
            $pdf = Pdf::loadView('pdf.blocked_user_report', [
                'user' => $user,
                'invalidatedExams' => $invalidatedExams,
                'violations' => $violations,
                'qrCode' => $qrCode,
                'verificationId' => $uuid,
                'generated_at' => now(),
                'generatedBy' => auth()->user()->name . ' ' . auth()->user()->lastname
            ]);
            
            $pdf->setPaper('letter', 'portrait')
                ->setOptions([
                    'tempDir' => storage_path('app/public'),
                    'fontDir' => storage_path('fonts'),
                    'isRemoteEnabled' => true
                ]);
            
            return $pdf->download("reporte_usuario_bloqueado_{$userId}.pdf");
            
        } catch (\Exception $e) {
            Log::error('Error generando reporte de usuario: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Generar reporte PDF de un examen específico
     * GET /api/v1/admin/blocked-users/exam-report/{attemptId}
     */
    public function generateExamReport($attemptId)
    {
        try {
            ini_set('memory_limit', '256M');
            
            $exam = DB::table('modular_exam_attempts')
                ->where('id', $attemptId)
                ->first();
            
            if (!$exam) {
                return response()->json(['error' => 'Examen no encontrado'], 404);
            }
            
            $user = DB::table('users')->where('id', $exam->student_id)->first();
            
            $violations = DB::table('security_logs')
                ->where('exam_attempt_id', $attemptId)
                ->orderBy('created_at', 'asc')
                ->get();
            
            // Generar UUID y QR
            $uuid = (string) Str::uuid();
            
            // Guardar en verifications
            DB::table('verifications')->insert([
                'uuid' => $uuid,
                'verifiable_id' => $attemptId,
                'verifiable_type' => 'App\Models\ModularExamAttempt',
                'type' => 'MODULAR_EXAM_REPORT',
                'metadata' => json_encode([
                    'attempt_id' => $attemptId,
                    'student_id' => $user->id,
                    'student_name' => $user->name . ' ' . ($user->lastname ?? ''),
                    'violations_count' => $violations->count(),
                    'status' => $exam->status,
                    'generated_by' => auth()->id()
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // QR con URL de verificación
            $qrCode = base64_encode(QrCode::format('svg')->size(100)->margin(0)->generate(url("/verify/{$uuid}")));
            
            $pdf = Pdf::loadView('pdf.exam_violation_report', [
                'exam' => $exam,
                'user' => $user,
                'violations' => $violations,
                'qrCode' => $qrCode,
                'verificationId' => $uuid,
                'generated_at' => now(),
                'generatedBy' => auth()->user()->name . ' ' . auth()->user()->lastname
            ]);
            
            $pdf->setPaper('letter', 'portrait')
                ->setOptions([
                    'tempDir' => storage_path('app/public'),
                    'fontDir' => storage_path('fonts'),
                    'isRemoteEnabled' => true
                ]);
            
            return $pdf->download("reporte_examen_invalidado_{$attemptId}.pdf");
            
        } catch (\Exception $e) {
            Log::error('Error generando reporte de examen: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}