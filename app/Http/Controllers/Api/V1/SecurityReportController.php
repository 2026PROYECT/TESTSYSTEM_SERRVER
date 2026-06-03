<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SecurityLog;
use App\Models\User;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class SecurityReportController extends Controller
{
    /**
     * Obtener reporte general de violaciones de seguridad
     */
    public function index(Request $request)
    {
        // Verificar permisos
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $studentId = $request->query('student_id');
        $eventType = $request->query('event_type');
        
        $query = SecurityLog::with(['user', 'examAttempt'])
            ->orderBy('created_at', 'desc');
        
        // Filtros
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        if ($studentId) {
            $query->where('user_id', $studentId);
        }
        if ($eventType) {
            $query->where('event_type', $eventType);
        }
        
        $logs = $query->paginate(50);
        
        // Estadísticas
        $stats = [
            'total_violations' => SecurityLog::count(),
            'today' => SecurityLog::whereDate('created_at', today())->count(),
            'this_week' => SecurityLog::whereDate('created_at', '>=', now()->startOfWeek())->count(),
            'this_month' => SecurityLog::whereDate('created_at', '>=', now()->startOfMonth())->count(),
            'by_type' => SecurityLog::select('event_type', DB::raw('count(*) as total'))
                ->groupBy('event_type')
                ->get()
                ->pluck('total', 'event_type')
                ->toArray(),
            'by_student' => SecurityLog::select('user_id', DB::raw('count(*) as total'))
                ->with('user')
                ->groupBy('user_id')
                ->orderBy('total', 'desc')
                ->limit(10)
                ->get()
                ->map(function($item) {
                    return [
                        'user_id' => $item->user_id,
                        'user_name' => $item->user ? trim($item->user->name . ' ' . ($item->user->lastname ?? '')) : 'Desconocido',
                        'total' => $item->total
                    ];
                }),
            'by_date' => SecurityLog::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(30)
                ->get()
        ];
        
        return response()->json([
            'logs' => $logs,
            'stats' => $stats,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'student_id' => $studentId,
                'event_type' => $eventType
            ]
        ]);
    }
    
    /**
     * Obtener violaciones por estudiante específico
     */
    public function getByStudent($studentId)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        
        $student = User::where('id', $studentId)->where('role', 'student')->first();
        
        if (!$student) {
            return response()->json(['error' => 'Estudiante no encontrado'], 404);
        }
        
        $violations = SecurityLog::where('user_id', $studentId)
            ->with('examAttempt')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $stats = [
            'total_violations' => $violations->count(),
            'by_type' => $violations->groupBy('event_type')->map->count(),
            'by_exam' => $violations->groupBy('exam_attempt_id')->map->count()
        ];
        
        return response()->json([
            'student' => $student,
            'violations' => $violations,
            'stats' => $stats
        ]);
    }
    
    /**
     * Obtener violaciones por examen específico
     */
    public function getByExam($examAttemptId)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        
        $exam = ExamAttempt::with('user')->find($examAttemptId);
        
        if (!$exam) {
            return response()->json(['error' => 'Examen no encontrado'], 404);
        }
        
        $violations = SecurityLog::where('exam_attempt_id', $examAttemptId)
            ->orderBy('created_at', 'asc')
            ->get();
        
        $stats = [
            'total_violations' => $violations->count(),
            'by_type' => $violations->groupBy('event_type')->map->count(),
            'timeline' => $violations->map(function($log) {
                return [
                    'time' => $log->created_at->format('H:i:s'),
                    'event' => $log->event_type,
                    'details' => $log->details
                ];
            })
        ];
        
        // Determinar si el examen fue invalidado
        $wasInvalidated = $violations->contains(function($log) {
            return $log->event_type === 'exam_invalidated';
        });
        
        return response()->json([
            'exam' => $exam,
            'student' => $exam->user,
            'violations' => $violations,
            'stats' => $stats,
            'was_invalidated' => $wasInvalidated
        ]);
    }
    
    /**
     * Exportar reporte a PDF
     */
    public function exportPdf(Request $request)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $studentId = $request->query('student_id');
        
        $query = SecurityLog::with(['user', 'examAttempt'])
            ->orderBy('created_at', 'desc');
        
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        if ($studentId) {
            $query->where('user_id', $studentId);
        }
        
        $logs = $query->get();
        
        // Estadísticas para el PDF
        $stats = [
            'total' => $logs->count(),
            'by_type' => $logs->groupBy('event_type')->map->count(),
            'top_students' => $logs->groupBy('user_id')->map(function($items, $userId) {
                $user = User::find($userId);
                $fullName = $user ? trim($user->name . ' ' . ($user->lastname ?? '')) : 'Desconocido';
                return [
                    'name' => $fullName,
                    'count' => $items->count()
                ];
            })->sortByDesc('count')->take(10)
        ];
        
        $pdf = Pdf::loadView('pdf.security_report', [
            'logs' => $logs,
            'stats' => $stats,
            'generated_at' => now(),
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'student_id' => $studentId
            ]
        ]);
        
        $pdf->setPaper('a4', 'landscape');
        
        return $pdf->download('reporte_seguridad_' . date('Y-m-d_His') . '.pdf');
    }
    
    /**
     * Obtener resumen rápido de violaciones (para dashboard)
     */
    public function summary()
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();
        
        return response()->json([
            'summary' => [
                'today' => SecurityLog::whereDate('created_at', $today)->count(),
                'this_week' => SecurityLog::whereDate('created_at', '>=', $thisWeek)->count(),
                'this_month' => SecurityLog::whereDate('created_at', '>=', $thisMonth)->count(),
                'total' => SecurityLog::count(),
            ],
            'recent_violations' => SecurityLog::with(['user', 'examAttempt'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function($log) {
                    $fullName = $log->user ? trim($log->user->name . ' ' . ($log->user->lastname ?? '')) : 'Sistema';
                    $log->user_full_name = $fullName;
                    return $log;
                }),
            'top_event_types' => SecurityLog::select('event_type', DB::raw('count(*) as total'))
                ->groupBy('event_type')
                ->orderBy('total', 'desc')
                ->get(),
            'students_with_most_violations' => SecurityLog::select('user_id', DB::raw('count(*) as total'))
                ->with('user')
                ->groupBy('user_id')
                ->orderBy('total', 'desc')
                ->limit(5)
                ->get()
                ->map(function($item) {
                    $fullName = $item->user ? trim($item->user->name . ' ' . ($item->user->lastname ?? '')) : 'Desconocido';
                    return [
                        'user_id' => $item->user_id,
                        'user_name' => $fullName,
                        'total' => $item->total
                    ];
                })
        ]);
    }
}