<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OralQuestion;
use App\Models\QuizAssignment;
use App\Models\OralResult;
use App\Models\User;
use App\Notifications\MissedExamNotification;
use App\Notifications\CustomNotification;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class TeacherController extends Controller
{
    /**
     * Datos para el Dashboard del Profesor
     */
   public function index(Request $request)
    {
        try {
            $langId = $request->header('X-Language-Id') ?? $request->query('language_id', 1);

            $exams = DB::table('quiz_assignments')
                ->join('users', 'quiz_assignments.student_id', '=', 'users.id')
                ->where('quiz_assignments.test_type', 'OralTest')
                ->where('quiz_assignments.language_id', $langId) 
                // CORRECCIÓN: Quitamos el orWhereDate para que al poner active=0 DESAPAREZCA
                ->where('quiz_assignments.active', 1) 
                ->select(
                    'quiz_assignments.id as assignment_id',
                    'quiz_assignments.student_id',
                    'quiz_assignments.language_id',
                    'users.name',
                    'users.lastname',
                    'users.picture',
                    'quiz_assignments.start_at',
                    'quiz_assignments.attended',
                    'quiz_assignments.active',
                    'quiz_assignments.created_at'
                )
                ->orderBy('quiz_assignments.created_at', 'asc')
                ->get()
                ->map(function($exam) {
                    $startTime = Carbon::parse($exam->start_at);
                    $now = Carbon::now();
                    // Lógica de habilitación ±15 min
                    $exam->can_start = $now->greaterThanOrEqualTo($startTime->copy()->subMinutes(15));
                    $exam->can_mark_absent = $now->greaterThan($startTime->copy()->addMinutes(15));
                    return $exam;
                });

            // ESTADÍSTICAS: Aquí sí usamos el historial del día para el conteo
            $todayStats = DB::table('quiz_assignments')
                ->where('language_id', $langId)
                ->whereDate('updated_at', Carbon::today())
                ->get();

            $stats = [
                [ 'label' => 'Exámenes Pendientes', 'value' => $exams->count(), 'trend' => 0 ],
                [ 'label' => 'Completados Hoy', 'value' => $todayStats->where('attended', 1)->count(), 'trend' => 0 ],
                [ 'label' => 'Inasistencias Hoy', 'value' => $todayStats->where('attended', 0)->count(), 'trend' => 0 ],
                [ 'label' => 'Total Alumnos', 'value' => $exams->unique('student_id')->count(), 'trend' => 0 ],
            ];

            return response()->json([
                'status' => 'success',
                'stats'  => $stats,
                'exams'  => $exams
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    /**
     * Obtener los detalles de un examen oral específico antes de calificar
     */
    public function showExam($id)
    {
        $exam = DB::table('quiz_assignments')
            ->join('users', 'quiz_assignments.student_id', '=', 'users.id')
            ->where('quiz_assignments.id', $id)
            ->select(
                'quiz_assignments.id',
                'users.name',
                'users.lastname',
                'users.picture',
                'quiz_assignments.test_type'
            )
            ->first();

        if (!$exam) {
            return response()->json(['message' => 'Examen no encontrado'], 404);
        }

        return response()->json($exam);
    }

    /**
     * Guardar la calificación del Examen Oral
     */
    public function storeGrade(Request $request, $id)
    {
        $request->validate([
            'grade' => 'required|numeric|min:0|max:100',
            'observations' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            // 1. Actualizamos la asignación para marcarla como completada (active = 0)
            DB::table('quiz_assignments')
                ->where('id', $id)
                ->update([
                    'active' => 0,
                    'updated_at' => now()
                ]);

            // 2. Insertamos el resultado en una tabla de notas (suponiendo que se llama 'quiz_results')
            // Si no tienes esta tabla, puedes guardarlo directamente en quiz_assignments si añades los campos.
            DB::table('quiz_results')->insert([
                'assignment_id' => $id,
                'score' => $request->grade,
                'notes' => $request->observations,
                'teacher_id' => $request->user()->id,
                'completed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json(['message' => 'Calificación guardada con éxito']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al procesar la nota', 'error' => $e->getMessage()], 500);
        }
    }

public function getQuestionsByLevel(Request $request, $level)
{
    // 1. Detectamos el idioma igual que en el index
    $langId = $request->header('X-Language-Id') ?? 1;

    // 2. Traemos las preguntas filtradas por nivel E IDIOMA
    $questions = OralQuestion::where('level', strtoupper($level))
        ->where('language_id', $langId) // <--- Filtro de seguridad
        ->where('active', true)
        ->inRandomOrder() 
        ->limit(6)       
        ->get(['id', 'question_text', 'level', 'language_id']);

    return response()->json($questions);
}

/**
 * Obtiene el detalle del alumno y su asignación
 */
public function getExamDetail($id)
{
    $exam = DB::table('quiz_assignments')
        ->join('users', 'quiz_assignments.student_id', '=', 'users.id')
        ->where('quiz_assignments.id', $id)
        ->select(
            'quiz_assignments.id as assignment_id',
            'quiz_assignments.student_id',
            'users.name',
            'users.lastname',
            'users.picture'
        )
        ->first();

    if (!$exam) {
        return response()->json(['message' => 'Examen no encontrado'], 404);
    }

    return response()->json($exam);
}

public function saveOralEvaluation(Request $request, $id)
{
    $request->validate([
        'final_level'     => 'required|string',
        'final_score'     => 'required', 
        'detailed_scores' => 'required',
    ]);

    try {
        return DB::transaction(function () use ($request, $id) {
            // 1. Buscamos la asignación
            $assignment = QuizAssignment::findOrFail($id);

            $certifiedLevel = strtoupper($request->final_level); 
            $certifiedScore = (float)$request->final_score; 

            // Lógica de aprobación (B2+ y nota >= 60)
            $isHighLevel = in_array($certifiedLevel, ['B2', 'C1', 'C2']);
            $hasPassed = $isHighLevel && ($certifiedScore >= 60.0);

            // 2. Procesamos detailed_scores para que sea un array limpio
            $scores = $request->detailed_scores;
            if (is_string($scores)) {
                $scores = json_decode($scores, true);
            }

            // 3. CREAR RESULTADO
            OralResult::create([
                'quiz_assignment_id' => $id,
                'student_id'         => $assignment->student_id,
                'language_id'        => $assignment->language_id,
                'teacher_id'         => auth()->id(), 
                'final_level'        => $certifiedLevel,
                'final_score'        => $certifiedScore,
                'detailed_scores'    => $scores, 
                'teacher_feedback'   => $request->teacher_feedback ?? 'Sin observaciones.',
            ]);

            // 4. ACTUALIZAR ASIGNACIÓN
            $assignment->update([
                'active'   => false,
                'passed'   => $hasPassed,
                'attended' => 1,
            ]);

            // ✅ NOTIFICACIÓN - DENTRO de la transacción
            $student = \App\Models\User::find($assignment->student_id);
            if ($student) {
                $student->notify(new \App\Notifications\CustomNotification(
                    'exam_result',
                    '🎉 Resultado de Examen',
                    "Tu examen oral ha sido calificado. Puntaje: {$certifiedScore}% - Nivel: {$certifiedLevel}",
                    [
                        'assignment_id' => $id,
                        'score' => $certifiedScore,
                        'level' => $certifiedLevel,
                        'test_type' => 'OralTest'
                    ]
                ));
                
                \Log::info("Notificación de resultado enviada a: {$student->email}");
            }

            return response()->json([
                'status' => 'success',
                'message' => "Evaluación registrada correctamente."
            ]);
        });
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
}
/**
 * Marca a un estudiante como ausente (Inasistencia)
 */
public function markAsAbsent(Request $request, $id)
{
    try {
        return DB::transaction(function () use ($request, $id) {
            $assignment = QuizAssignment::findOrFail($id);
            
            // Obtener el estudiante para la notificación
            $student = User::find($assignment->student_id);
            
            // Guardar datos antes de actualizar
            $examType = $assignment->test_type;
            $examDate = $assignment->start_at;
            
            // 1. Desactivar y marcar inasistencia
            $assignment->update([
                'active'   => 0,        // Sale del dashboard
                'attended' => 0,        // 0 = Ausente
                'passed'   => 0,
                'updated_at' => now(),
                'notification_sent' => true
            ]);
            
            // 2. Registrar el resultado como "No se presentó"
            OralResult::updateOrCreate(
                ['quiz_assignment_id' => $id],
                [
                    'student_id'         => $assignment->student_id,
                    'language_id'        => $assignment->language_id,
                    'teacher_id'         => auth()->id(),
                    'final_level'        => '0',
                    'final_score'        => 0.00,
                    'detailed_scores'    => json_encode(['status' => 'No se presentó']),
                    'teacher_feedback'   => 'Inasistencia registrada por el docente.',
                    'created_at'         => now(),
                    'updated_at'         => now()
                ]
            );
            
            // 3. Actualizar sanción del estudiante (penalty_until +14 días)
            if ($student) {
                $student->update([
                    'penalty_until' => now()->addDays(14)
                ]);
            }
            
            // 4. ENVIAR NOTIFICACIÓN AL ESTUDIANTE
            if ($student) {
                try {
                    $student->notify(new MissedExamNotification($assignment, $student, 14));
                    Log::info('Notificación de inasistencia enviada a: ' . $student->email);
                } catch (\Exception $e) {
                    Log::error('Error enviando notificación de inasistencia: ' . $e->getMessage());
                }
            }
            
            // 5. Registrar en auditoría
            \App\Models\AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'Marcar inasistencia (Teacher)',
                'entity_type' => 'quiz_assignment',
                'entity_id' => $assignment->id,
                'new_data' => [
                    'student_id' => $assignment->student_id,
                    'student_email' => $student?->email,
                    'exam_type' => $examType,
                    'exam_date' => $examDate,
                    'notification_sent' => true
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'severity' => 'warning'
            ]);
            
            return response()->json([
                'status' => 'success', 
                'message' => 'Inasistencia registrada y notificación enviada al estudiante'
            ]);
        });
        
    } catch (\Exception $e) {
        Log::error('Error en markAsAbsent: ' . $e->getMessage());
        return response()->json([
            'status' => 'error', 
            'message' => 'Error al registrar inasistencia: ' . $e->getMessage()
        ], 500);
    }
}

public function getEvaluationResult($assignmentId)
{
    $result = DB::table('oral_results')
        // Unimos con quiz_assignments para llegar al estudiante
        ->join('quiz_assignments', 'oral_results.quiz_assignment_id', '=', 'quiz_assignments.id')
        
        // ¡ESTE ES EL CAMBIO CLAVE! 
        // Unimos con 'users' en lugar de 'students' porque ahí están el nombre y la foto
        ->join('users', 'oral_results.student_id', '=', 'users.id') 
        
        ->select(
            'oral_results.*',
            'users.name',      // Ahora sí encontrará la columna
            'users.lastname',
            'users.picture'
        )
        ->where('oral_results.quiz_assignment_id', $assignmentId)
        ->first();

    if (!$result) {
        return response()->json(['message' => 'No encontrado'], 404);
    }

    $result->detailed_scores = json_decode($result->detailed_scores);

    return response()->json($result);
}
public function getCompletedExams(Request $request)
{
    try {
        $user = $request->user();
        
        // --- AdminOralReportController.php ---
$query = DB::table('oral_results')
    ->join('users', 'oral_results.student_id', '=', 'users.id')
    ->join('quiz_assignments', 'oral_results.quiz_assignment_id', '=', 'quiz_assignments.id')
    ->join('languages', 'quiz_assignments.language_id', '=', 'languages.id') // <--- EL JOIN VITAL
    ->select(
        'oral_results.id as result_id',
        'oral_results.final_level',
        'oral_results.final_score',
        'oral_results.created_at',
        'users.name',
        'users.lastname',
        'users.picture',
        'quiz_assignments.attended',
        'languages.name as language_name' // <--- EL CAMPO QUE FALTA
    );

        // LÓGICA DE FILTRADO:
        if (!$request->is('*admin*')) {
            $query->where('oral_results.teacher_id', $user->id);
        }

        $results = $query->orderBy('oral_results.created_at', 'desc')->get();

        return response()->json($results);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
public function showResult($id) // Este $id viene del {id} de la ruta
{
    $result = DB::table('oral_results')
        ->join('users', 'oral_results.student_id', '=', 'users.id')
        ->where('oral_results.id', $id) // <-- DEBE BUSCAR POR 'id', NO POR 'quiz_assignment_id'
        ->select(
            'oral_results.*',
            'users.name',
            'users.lastname',
            'users.picture'
        )
        ->first();

    if (!$result) {
        return response()->json(['message' => 'Resultado no encontrado'], 404);
    }

    // Esto asegura que solo decodifique si realmente es una cadena de texto JSON
if (is_string($result->detailed_scores)) {
    $result->detailed_scores = json_decode($result->detailed_scores);
}

    return response()->json($result);
}

}