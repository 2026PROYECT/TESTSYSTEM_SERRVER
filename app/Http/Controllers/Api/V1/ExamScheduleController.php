<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\QuizAssignment;
use App\Models\Language;
use App\Models\Quiz;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\TimeSlotService;
use App\Notifications\CustomNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;  

class ExamScheduleController extends Controller
{
    protected $timeSlotService;

    public function __construct(TimeSlotService $timeSlotService)
    {
        $this->timeSlotService = $timeSlotService;
    }

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
// app/Http/Controllers/Api/ExamScheduleController.php

public function checkSancion(Request $request)
{
    try {
        $request->validate([
            'language_id' => 'required|integer|exists:languages,id'
        ]);

        $student = $request->user();
        
        // Verificar si existe la tabla de sanciones
        if (!\Schema::hasTable('sanciones')) {
            return response()->json([
                'status' => 'success',
                'data' => ['sancion_until' => null],
                'message' => 'Sistema de sanciones no implementado'
            ]);
        }
        
        // Buscar sanción activa
        $sancion = \DB::table('sanciones')
            ->where('student_id', $student->id)
            ->where('language_id', $request->language_id)
            ->where('fecha_fin', '>', now())
            ->first();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'sancion_until' => $sancion ? \Carbon\Carbon::parse($sancion->fecha_fin)->toISOString() : null
            ]
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Error en checkSancion: ' . $e->getMessage());
        
        // En caso de error, retornar sin sanción para no bloquear al usuario
        return response()->json([
            'status' => 'success',
            'data' => ['sancion_until' => null],
            'error' => $e->getMessage()
        ]);
    }
}
    /**
     * Carga inicial: Mis citas, idiomas y elegibilidad inicial.
     */
    public function index()
    {
        return response()->json([
            'appointments' => QuizAssignment::where('student_id', auth()->id())
                ->with('language')
                ->orderBy('start_at', 'desc')
                ->get(),
            'languages' => Language::all()
        ]);
    }

    /**
     * Verifica elegibilidad para programar un nuevo examen
     */
    public function checkEligibility(Request $request)
    {
        $user = auth()->user();
        $langId = $request->query('language_id');
        $testType = $request->query('test_type');

        // ========== 1. Verificar cita activa ==========
        $activeAssignment = QuizAssignment::where('student_id', $user->id)
            ->where('active', true)
            ->first();

        // ========== 2. Verificar si ya completó el nivel ==========
        $levelCompleted = QuizAssignment::where('student_id', $user->id)
            ->where('language_id', $langId)
            ->where(function($q) {
                $q->where('test_type', 'CompTest')
                  ->orWhere('test_type', 'ModularTest');
            })
            ->where('passed', true)
            ->exists();

        if ($levelCompleted && $langId) {
            return response()->json([
                'eligible' => false,
                'reason' => 'Ya has completado este nivel. ¡Felicidades!'
            ]);
        }

        // ========== 3. Prerrequisito Oral para CompTest y ModularTest ==========
        $oralPassed = false;
        if ($langId && in_array($testType, ['CompTest', 'ModularTest'])) {
            $oralPassed = QuizAssignment::where('student_id', $user->id)
                ->where('language_id', $langId)
                ->where('test_type', 'OralTest')
                ->where('passed', true)
                ->exists();
            
            if (!$oralPassed) {
                return response()->json([
                    'eligible' => false,
                    'reason' => 'Debes aprobar el examen Oral antes de programar este tipo de examen.'
                ]);
            }
        }

        // ========== 4. Verificar sanciones ==========
        $sancionUntil = null;
        
        $lastFailed = QuizAssignment::where('student_id', $user->id)
            ->where('test_type', $testType)
            ->where('active', false)
            ->where(function($query) {
                $query->where('passed', false)
                      ->orWhere('attended', false);
            })
            ->latest('updated_at')
            ->first();

        if ($lastFailed && $langId) {
            $daysToWait = 0;
            
            if (!$lastFailed->attended) {
                $daysToWait = 14;
            } elseif (!$lastFailed->passed) {
                $daysToWait = 7;
            }
            
            if ($daysToWait > 0) {
                $unlockDate = Carbon::parse($lastFailed->updated_at)->addDays($daysToWait);
                
                if (now()->lessThan($unlockDate)) {
                    $sancionUntil = $unlockDate->format('Y-m-d');
                }
            }
        }

        return response()->json([
            'eligible' => true,
            'has_active' => !!$activeAssignment,
            'oral_passed' => $oralPassed,
            'sancion_until' => $sancionUntil
        ]);
    }

    /**
     * Obtener slots disponibles para estudiantes
     */
    public function getAvailableSlots(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'test_type' => 'required|in:OralTest,CompTest,ModularTest',
            'language_id' => 'required|exists:languages,id'
        ]);

        $slots = $this->timeSlotService->getAvailableSlots(
            $request->date,
            $request->test_type,
            $request->language_id
        );

        return response()->json([
            'status' => 'success',
            'slots' => $slots
        ]);
    }

    /**
     * Guarda la cita con el idioma elegido.
     */
   public function store(Request $request)
{
    try {
        $user = auth()->user();
        
        // ========== LOG PARA DEPURACIÓN ==========
        Log::info('=== INICIO RESERVA ===');
        Log::info('Usuario ID: ' . $user->id);
        Log::info('Datos recibidos:', $request->all());
        
        $startAt = Carbon::parse($request->start_at);
        $type = $request->test_type;
        $langId = $request->language_id;
        $quizId = $request->quiz_id;
        
        Log::info('Tipo: ' . $type);
        Log::info('Idioma: ' . $langId);
        Log::info('Fecha/Hora: ' . $startAt);
        
        // ========== VALIDACIÓN 0: Tipo válido ==========
        if (!in_array($type, ['OralTest', 'CompTest', 'ModularTest'])) {
            Log::error('Tipo no válido: ' . $type);
            return response()->json(['error' => 'Tipo de examen no válido'], 422);
        }
        
        // ========== VALIDACIÓN 1: Nivel ya completado ==========
        $levelCompleted = QuizAssignment::where('student_id', $user->id)
            ->where('language_id', $langId)
            ->whereIn('test_type', ['CompTest', 'ModularTest'])
            ->where('passed', true)
            ->exists();
        
        Log::info('Nivel completado? ' . ($levelCompleted ? 'SI' : 'NO'));
        
        if ($levelCompleted) {
            Log::error('Nivel ya completado');
            return response()->json(['error' => 'Ya has completado este nivel. ¡Felicidades!'], 422);
        }
        
        // ========== VALIDACIÓN 2: Prerrequisito Oral ==========
        if (in_array($type, ['CompTest', 'ModularTest'])) {
            $oralPassed = QuizAssignment::where('student_id', $user->id)
                ->where('language_id', $langId)
                ->where('test_type', 'OralTest')
                ->where('passed', true)
                ->exists();
            
            Log::info('Oral aprobado? ' . ($oralPassed ? 'SI' : 'NO'));
            
            if (!$oralPassed) {
                Log::error('Oral no aprobado');
                return response()->json(['error' => 'Debes aprobar el examen Oral de este idioma antes de programar este examen.'], 403);
            }
        }
        
        // ========== VALIDACIÓN 3: Verificar que el quiz modular existe ==========
        if ($type === 'ModularTest') {
            if (!$quizId) {
                Log::info('Quiz ID no proporcionado - se asignará aleatoriamente después');
            } else {
                $quiz = Quiz::where('id', $quizId)->where('type', 'modular')->first();
                if (!$quiz) {
                    Log::error('Quiz modular no encontrado: ' . $quizId);
                    return response()->json(['error' => 'Examen modular no encontrado'], 422);
                }
                Log::info('Quiz encontrado: ' . $quiz->id);
            }
        }
        
        // ========== VALIDACIÓN 4: Cita Activa Pendiente ==========
        $hasActive = QuizAssignment::where('student_id', $user->id)
            ->where('active', true)
            ->exists();
        
        Log::info('Tiene cita activa? ' . ($hasActive ? 'SI' : 'NO'));
        
        if ($hasActive) {
            // Mostrar qué cita está activa
            $activeBooking = QuizAssignment::where('student_id', $user->id)
                ->where('active', true)
                ->first();
            Log::error('Cita activa encontrada:', $activeBooking->toArray());
            return response()->json(['error' => 'Ya tienes una cita programada pendiente.'], 422);
        }
        
        // ========== VALIDACIÓN 5: Sanciones específicas por tipo ==========
        $lastFailed = QuizAssignment::where('student_id', $user->id)
            ->where('test_type', $type)
            ->where('active', false)
            ->where(function($query) {
                $query->where('passed', false)
                      ->orWhere('attended', false);
            })
            ->latest('updated_at')
            ->first();
        
        if ($lastFailed) {
            Log::info('Último examen fallido:', [
                'id' => $lastFailed->id,
                'passed' => $lastFailed->passed,
                'attended' => $lastFailed->attended,
                'updated_at' => $lastFailed->updated_at
            ]);
            
            $daysToWait = 0;
            $reason = '';
            
            if (!$lastFailed->attended) {
                $daysToWait = 14;
                $reason = 'inasistencia';
            } elseif (!$lastFailed->passed) {
                $daysToWait = 7;
                $reason = 'reprobación';
            }
            
            $unlockDate = Carbon::parse($lastFailed->updated_at)->addDays($daysToWait);
            Log::info("Días a esperar: $daysToWait, Fecha desbloqueo: $unlockDate, Hoy: " . now());
            
            if (now()->lessThan($unlockDate)) {
                Log::error("Sanción activa hasta: $unlockDate");
                return response()->json([
                    'error' => "No puedes programar un examen {$type} debido a una {$reason} previa. Podrás intentar nuevamente a partir del " . $unlockDate->format('d/m/Y') . "."
                ], 403);
            } else {
                Log::info("Sanción ya expirada, puede continuar");
            }
        } else {
            Log::info("No hay exámenes fallidos previos");
        }
        
        // ========== VALIDACIÓN 6: Verificar disponibilidad ==========
// Para ModularTest, buscar disponibilidad como CompTest (mismos horarios)
$searchType = $type;
if ($type === 'ModularTest') {
    $searchType = 'CompTest';  // Usar CompTest para buscar horarios
    Log::info('Mapeando ModularTest a CompTest para disponibilidad');
}

$availableSlots = $this->timeSlotService->getAvailableSlots(
    $startAt->format('Y-m-d'),
    $searchType,  // Usar el tipo mapeado
    $langId
);

Log::info('Slots disponibles para ' . $searchType . ':', array_column($availableSlots, 'time'));

$selectedSlot = collect($availableSlots)->first(function($slot) use ($startAt) {
    return $slot['time'] === $startAt->format('H:i:s');
});

Log::info('Slot seleccionado:', $selectedSlot ?? ['no_encontrado' => true]);

if (!$selectedSlot || !$selectedSlot['is_available']) {
    Log::error('Horario no disponible. Slot encontrado: ' . ($selectedSlot ? 'SÍ' : 'NO') . ', Disponible: ' . ($selectedSlot ? ($selectedSlot['is_available'] ? 'SÍ' : 'NO') : 'N/A'));
    return response()->json(['error' => 'El horario seleccionado ya no está disponible.'], 422);
}
        
        // ========== CREAR LA CITA ==========
        Log::info('Todas las validaciones pasaron, creando cita...');
        
        $assignmentData = [
            'student_id'  => $user->id,
            'language_id' => $langId,
            'test_type'   => $type,
            'start_at'    => $startAt,
            'active'      => true,
            'attended'    => false,
            'passed'      => false,
            'is_unlocked' => false
        ];
        
        if ($type === 'ModularTest' && $quizId) {
            $assignmentData['quiz_id'] = $quizId;
        }
        
        $assignment = QuizAssignment::create($assignmentData);
        
        Log::info('Cita creada exitosamente ID: ' . $assignment->id);
        
        return response()->json([
            'success' => true,
            'message' => 'Cita programada exitosamente.',
            'data' => $assignment
        ], 201);
        
    } catch (\Exception $e) {
        Log::error('Error excepción: ' . $e->getMessage());
        Log::error($e->getTraceAsString());
        
        return response()->json([
            'error' => 'Error interno: ' . $e->getMessage()
        ], 500);
    }
}
    /**
     * Habilitar examen (Admin/Staff)
     */
    public function unlockExam(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin' && auth()->user()->role !== 'staff') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $assignment = QuizAssignment::findOrFail($id);
        
        $oldStatus = $assignment->is_unlocked;
        
        $assignment->update(['is_unlocked' => true]);

        // ✅ LOG DE AUDITORÍA: Habilitación de examen
        $this->logAudit('Habilitación de examen para estudiante', [
            'entity_type' => 'quiz_assignment',
            'entity_id' => (int)$id,
            'old_data' => ['is_unlocked' => $oldStatus],
            'new_data' => ['is_unlocked' => true],
            'severity' => 'warning'
        ]);

        return response()->json(['message' => 'Examen habilitado. El estudiante ya puede comenzar.']);
    }
    /**
 * Verificar el estado actual del estudiante (para el Dashboard)
 * GET /api/v1/student/check-status
 */
public function checkStatus(Request $request)
{
    try {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'authorized' => false,
                'is_unlocked' => false,
                'has_active_appointment' => false,
                'active_appointment' => null,
                'oral_assignment' => ['status' => 'none', 'score' => 0, 'level' => 'N/E'],
                'comp_test' => ['status' => 'none'],
                'penalty_until' => null,
                'penalty_type' => null,
                'missed_exam_date' => null
            ]);
        }
        
        // 🔥 BUSCAR ASIGNACIÓN ORAL (SIEMPRE, incluso sin cita activa)
        $oralAssignment = QuizAssignment::where('student_id', $user->id)
            ->where('test_type', 'OralTest')
            ->orderBy('created_at', 'desc')
            ->first();
        
        $oralStatus = 'none';
        $oralScore = 0;
        $oralLevel = 'N/E';
        
        if ($oralAssignment) {
            // Buscar el resultado oral en la tabla oral_results
            $oralResult = DB::table('oral_results')
                ->where('quiz_assignment_id', $oralAssignment->id)
                ->where('student_id', $user->id)
                ->first();
            
            if ($oralAssignment->passed == 1) {
                $oralStatus = 'passed';
                $oralScore = $oralResult->final_score ?? 100;
                $oralLevel = $oralResult->final_level ?? 'C1';
            } elseif ($oralAssignment->attended == 1) {
                $oralStatus = 'failed';
                $oralScore = $oralResult->final_score ?? 0;
            } elseif ($oralAssignment->start_at && now()->gt($oralAssignment->start_at)) {
                $oralStatus = 'absent';
            } else {
                $oralStatus = 'pending';
            }
        }
        
        // Buscar asignación activa (CompTest o ModularTest)
        $activeAssignment = QuizAssignment::where('student_id', $user->id)
            ->where('active', 1)
            ->whereIn('test_type', ['CompTest', 'ModularTest'])
            ->first();
        
        // Buscar último examen completado (para mostrar estado)
        $lastExam = QuizAssignment::where('student_id', $user->id)
            ->whereIn('test_type', ['CompTest', 'ModularTest'])
            ->orderBy('created_at', 'desc')
            ->first();
        
        $examStatus = 'none';
        if ($lastExam) {
            if ($lastExam->passed == 1) {
                $examStatus = 'passed';
            } elseif ($lastExam->attended == 1) {
                $examStatus = 'failed';
            } else {
                $examStatus = 'pending';
            }
        }
        
        return response()->json([
            'authorized' => !is_null($activeAssignment),
            'is_unlocked' => $activeAssignment ? ($activeAssignment->is_unlocked == 1) : false,
            'has_active_appointment' => !is_null($activeAssignment),
            'active_appointment' => $activeAssignment ? [
                'id' => $activeAssignment->id,
                'test_type' => $activeAssignment->test_type,
                'start_at' => $activeAssignment->start_at,
                'is_unlocked' => $activeAssignment->is_unlocked
            ] : null,
            'oral_assignment' => [
                'status' => $oralStatus,
                'score' => $oralScore,
                'level' => $oralLevel,
                'sanction_until' => null
            ],
            'comp_test' => [
                'status' => $examStatus,
                'sanction_until' => null
            ],
            'penalty_until' => null,
            'penalty_type' => null,
            'missed_exam_date' => null
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Error en checkStatus: ' . $e->getMessage());
        
        return response()->json([
            'authorized' => false,
            'is_unlocked' => false,
            'has_active_appointment' => false,
            'active_appointment' => null,
            'oral_assignment' => ['status' => 'none', 'score' => 0, 'level' => 'N/E'],
            'comp_test' => ['status' => 'none'],
            'penalty_until' => null,
            'penalty_type' => null,
            'missed_exam_date' => null
        ]);
    }
}
}