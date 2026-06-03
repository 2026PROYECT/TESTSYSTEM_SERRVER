<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ExamAttempt; 
use App\Models\AttemptQuestion;
use App\Models\QuestionBank;
use App\Models\QuizAssignment;
use Carbon\Carbon;

class StudentQuizController extends Controller
{
    private $optionMap = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4];

    /**
     * CHECK STATUS
     */
/**
     * CHECK STATUS - CON SOPORTE PARA MODULARTEST
     */
    public function checkStatus(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            // ========== Verificar cita activa ==========
            $hasActiveAppointment = DB::table('quiz_assignments')
                ->where('student_id', $user->id)
                ->where('active', true)
                ->exists();

            $activeAppointment = null;
            if ($hasActiveAppointment) {
                $activeAppointment = DB::table('quiz_assignments')
                    ->where('student_id', $user->id)
                    ->where('active', true)
                    ->join('languages', 'quiz_assignments.language_id', '=', 'languages.id')
                    ->select('quiz_assignments.*', 'languages.name as language_name')
                    ->first();
            }

            // ========== Estado del examen ORAL ==========
            $oralAssignment = DB::table('quiz_assignments')
                ->where('student_id', $user->id)
                ->where('test_type', 'OralTest')
                ->orderBy('id', 'desc')
                ->first();

            $oralStatus = 'none';
            $oralScore = 0;
            $oralLevel = 'N/E';
            $oralSanctionUntil = null;
            $oralSanctionType = null;

            if ($oralAssignment) {
                $result = DB::table('oral_results')
                    ->where('quiz_assignment_id', $oralAssignment->id)
                    ->first();

                if ($oralAssignment->attended == 0 || $oralAssignment->attended == 2) {
                    $oralStatus = 'absent';
                } elseif ($oralAssignment->passed == 1) {
                    $oralStatus = 'passed';
                } else {
                    $oralStatus = 'failed';
                }

                $oralScore = $result->final_score ?? 0;
                $oralLevel = $result->final_level ?? 'N/E';
                
                if (!$hasActiveAppointment && $oralStatus !== 'passed') {
                    $oralMissed = DB::table('quiz_assignments')
                        ->where('student_id', $user->id)
                        ->where('test_type', 'OralTest')
                        ->where('attended', 0)
                        ->where('start_at', '<', now())
                        ->orderBy('start_at', 'desc')
                        ->first();
                    
                    if ($oralMissed) {
                        $oralSanctionUntil = Carbon::parse($oralMissed->start_at)->addDays(14);
                        $oralSanctionType = 'absence';
                    } else {
                        $oralFailed = DB::table('quiz_assignments')
                            ->where('student_id', $user->id)
                            ->where('test_type', 'OralTest')
                            ->where('attended', 1)
                            ->where('passed', 0)
                            ->where('active', 0)
                            ->orderBy('updated_at', 'desc')
                            ->first();
                        
                        if ($oralFailed) {
                            $oralSanctionUntil = Carbon::parse($oralFailed->updated_at)->addDays(7);
                            $oralSanctionType = 'failed';
                        }
                    }
                }
            }

            // ========== Estado del examen MODULARTEST (NUEVO) ==========
            $modularAssignment = DB::table('quiz_assignments')
                ->where('student_id', $user->id)
                ->where('test_type', 'ModularTest')
                ->orderBy('id', 'desc')
                ->first();

            $modularStatus = 'none';
            $modularScore = 0;
            $modularSanctionUntil = null;
            $modularSanctionType = null;

            if ($modularAssignment) {
                if ($modularAssignment->passed == 1) {
                    $modularStatus = 'passed';
                } elseif ($modularAssignment->active == 1) {
                    $modularStatus = 'active';
                } elseif ($modularAssignment->attended == 1 && $modularAssignment->passed == 0) {
                    $modularStatus = 'failed';
                } else {
                    $modularStatus = 'pending';
                }
                
                if (!$hasActiveAppointment && $modularStatus !== 'passed' && $modularAssignment->active != 1) {
                    $modularMissed = DB::table('quiz_assignments')
                        ->where('student_id', $user->id)
                        ->where('test_type', 'ModularTest')
                        ->where('attended', 0)
                        ->where('start_at', '<', now())
                        ->orderBy('start_at', 'desc')
                        ->first();
                    
                    if ($modularMissed) {
                        $modularSanctionUntil = Carbon::parse($modularMissed->start_at)->addDays(14);
                        $modularSanctionType = 'absence';
                    } else {
                        $modularFailed = DB::table('quiz_assignments')
                            ->where('student_id', $user->id)
                            ->where('test_type', 'ModularTest')
                            ->where('attended', 1)
                            ->where('passed', 0)
                            ->where('active', 0)
                            ->orderBy('updated_at', 'desc')
                            ->first();
                        
                        if ($modularFailed) {
                            $modularSanctionUntil = Carbon::parse($modularFailed->updated_at)->addDays(7);
                            $modularSanctionType = 'failed';
                        }
                    }
                }
            }

            // ========== Estado del examen COMPTEST ==========
            $compAssignment = DB::table('quiz_assignments')
                ->where('student_id', $user->id)
                ->where('test_type', 'CompTest')
                ->orderBy('id', 'desc')
                ->first();

            $compStatus = 'none';
            $isUnlocked = false;
            $compSanctionUntil = null;
            $compSanctionType = null;
            $missedExamDate = null;

            if ($compAssignment) {
                if ($compAssignment->passed == 1) {
                    $compStatus = 'passed';
                } elseif ($compAssignment->active == 1) {
                    $compStatus = 'active';
                } elseif ($compAssignment->attended == 1 && $compAssignment->passed == 0) {
                    $compStatus = 'failed';
                } else {
                    $compStatus = 'locked';
                }
                
                $isUnlocked = $compAssignment->is_unlocked ?? false;
                
                if (!$hasActiveAppointment && $compStatus !== 'passed') {
                    $compMissed = DB::table('quiz_assignments')
                        ->where('student_id', $user->id)
                        ->where('test_type', 'CompTest')
                        ->where('attended', 0)
                        ->where('start_at', '<', now())
                        ->orderBy('start_at', 'desc')
                        ->first();
                    
                    if ($compMissed) {
                        $missedExamDate = $compMissed->start_at;
                        $compSanctionUntil = Carbon::parse($compMissed->start_at)->addDays(14);
                        $compSanctionType = 'absence';
                    } else {
                        $compFailed = DB::table('quiz_assignments')
                            ->where('student_id', $user->id)
                            ->where('test_type', 'CompTest')
                            ->where('attended', 1)
                            ->where('passed', 0)
                            ->where('active', 0)
                            ->orderBy('updated_at', 'desc')
                            ->first();
                        
                        if ($compFailed) {
                            $missedExamDate = $compFailed->start_at;
                            $compSanctionUntil = Carbon::parse($compFailed->updated_at)->addDays(7);
                            $compSanctionType = 'failed';
                        }
                    }
                }
            }

            // Determinar la sanción activa
            $activePenaltyUntil = null;
            $activePenaltyType = null;
            $activeMissedDate = null;
            
            $sanctions = [];
            if ($compSanctionUntil) $sanctions[] = ['date' => $compSanctionUntil, 'type' => $compSanctionType, 'missed_date' => $missedExamDate];
            if ($modularSanctionUntil) $sanctions[] = ['date' => $modularSanctionUntil, 'type' => $modularSanctionType, 'missed_date' => null];
            if ($oralSanctionUntil) $sanctions[] = ['date' => $oralSanctionUntil, 'type' => $oralSanctionType, 'missed_date' => null];
            
            if (!empty($sanctions)) {
                usort($sanctions, function($a, $b) {
                    return $b['date'] <=> $a['date'];
                });
                $activePenaltyUntil = $sanctions[0]['date'];
                $activePenaltyType = $sanctions[0]['type'];
                $activeMissedDate = $sanctions[0]['missed_date'];
            }

            return response()->json([
                'oral_assignment' => [
                    'status' => $oralStatus,
                    'score' => $oralScore,
                    'level' => $oralLevel,
                    'sanction_until' => $oralSanctionUntil,
                    'sanction_type' => $oralSanctionType
                ],
                // 👉 NUEVO: ModularTest
                'modular_test' => [
                    'status' => $modularStatus,
                    'score' => $modularScore,
                    'sanction_until' => $modularSanctionUntil,
                    'sanction_type' => $modularSanctionType
                ],
                'authorized' => ($oralStatus === 'passed'),
                'is_unlocked' => $isUnlocked,
                'comp_test' => [
                    'status' => $compStatus,
                    'sanction_until' => $compSanctionUntil,
                    'sanction_type' => $compSanctionType
                ],
                'penalty_until' => $activePenaltyUntil,
                'penalty_type' => $activePenaltyType,
                'missed_exam_date' => $activeMissedDate,
                'has_active_appointment' => $hasActiveAppointment,
                'active_appointment' => $activeAppointment ? [
                    'id' => $activeAppointment->id,
                    'test_type' => $activeAppointment->test_type,
                    'start_at' => $activeAppointment->start_at,
                    'language' => $activeAppointment->language_name ?? null,
                    'is_unlocked' => $activeAppointment->is_unlocked ?? false
                ] : null
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error en checkStatus: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al obtener el estado',
                'message' => $e->getMessage()
            ], 500);
        }
    }

   /**
 * INICIAR EXAMEN - CORREGIDO
 */
 public function startModularExam(Request $request)
    {
        try {
            $studentId = auth()->id();
            
            $modularAssignment = DB::table('quiz_assignments')
                ->where('student_id', $studentId)
                ->where('test_type', 'ModularTest')
                ->where('active', 1)
                ->first();

            if (!$modularAssignment) {
                return response()->json([
                    'message' => 'No tienes una cita programada activa para este examen modular.'
                ], 403);
            }

            if (!$modularAssignment->is_unlocked) {
                return response()->json([
                    'message' => 'Acceso Bloqueado. Por favor, solicite al encargado del laboratorio que habilite su equipo.'
                ], 403);
            }

            $existingAttempt = DB::table('exam_attempts')
                ->where('student_id', $studentId)
                ->where('quiz_assignment_id', $modularAssignment->id)
                ->where('status', 'in_progress')
                ->first();

            if ($existingAttempt) {
                return response()->json([
                    'status' => 'reconnected', 
                    'attempt_id' => $existingAttempt->id,
                    'message' => 'Reanudando examen pendiente...'
                ]);
            }

            return DB::transaction(function () use ($studentId, $modularAssignment) {
                // Seleccionar un QUIZ modular aleatorio
                $quiz = DB::table('quizzes')
                    ->where('language_id', $modularAssignment->language_id)
                    ->where('type', 'modular')
                    ->inRandomOrder()
                    ->first();

                if (!$quiz) {
                    return response()->json(['message' => 'No hay exámenes modulares configurados.'], 404);
                }

                $totalQuestions = $quiz->total_questions ?? 15;

                $attemptId = DB::table('exam_attempts')->insertGetId([
                    'student_id'          => $studentId,
                    'quiz_id'             => $quiz->id,
                    'language_id'         => $modularAssignment->language_id, 
                    'started_at'          => now(),
                    'expires_at'          => now()->addMinutes((int) $quiz->duration_minutes), 
                    'last_question_index' => 0,
                    'status'              => 'in_progress',
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);

                $questions = DB::table('question_banks')
                    ->where('quiz_id', $quiz->id)
                    ->inRandomOrder()
                    ->limit($totalQuestions)
                    ->get();

                foreach ($questions as $index => $q) {
                    DB::table('attempt_questions')->insert([
                        'exam_attempt_id' => $attemptId,
                        'question_id'     => $q->id,
                        'order_position'  => $index + 1,
                        'created_at'      => now(),
                    ]);
                }

                return response()->json([
                    'status' => 'started', 
                    'attempt_id' => $attemptId
                ]);
            });

        } catch (\Exception $e) {
            \Log::error('Error en startModularExam: ' . $e->getMessage());
            return response()->json(['message' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    /**
     * CARGAR EXAMEN - MODIFICADO (añadir total_questions)
     */
    public function loadExam($attemptId)
    {
        try {
            $attempt = ExamAttempt::with(['quiz'])
                ->where('student_id', auth()->id()) 
                ->findOrFail($attemptId);

            $now = now();
            $expiresAt = Carbon::parse($attempt->expires_at);
            $secondsLeft = $now->diffInSeconds($expiresAt, false);

            if ($secondsLeft <= 0) {
                if ($attempt->status !== 'completed') {
                    $this->finishExam($attemptId);
                }
                return response()->json(['status' => 'expired', 'seconds_left' => 0], 403);
            }

            // 🔥 Obtener total_questions del quiz
            $totalQuestions = $attempt->quiz->total_questions ?? $attempt->quiz->questions()->count() ?? 15;

            $questions = AttemptQuestion::where('exam_attempt_id', $attempt->id)
                ->with('question') 
                ->orderBy('order_position', 'asc')
                ->get()
                ->map(function($aq) {
                    $q = $aq->question; 
                    return [
                        'id' => $q->id,
                        'prompt' => $q->question1,
                        'picture' => $q->picture,
                        'sound' => $q->sound,
                        'selected_option_id' => $aq->selected_option_id,
                        'options' => [
                            ['id' => 'A', 'option_text' => $q->option_a],
                            ['id' => 'B', 'option_text' => $q->option_b],
                            ['id' => 'C', 'option_text' => $q->option_c],
                            ['id' => 'D', 'option_text' => $q->option_d],
                        ]
                    ];
                });

            return response()->json([
                'quiz_title' => $attempt->quiz->title ?? 'Examen',
                'total_questions' => $totalQuestions,  // 🔥 AÑADIDO
                'questions' => $questions,
                'seconds_left' => $secondsLeft,
                'current_index' => $attempt->last_question_index 
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en loadExam: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * GUARDAR RESPUESTA
     */
    public function saveAnswer(Request $request, $attemptId)
    {
        try {
            $attempt = ExamAttempt::find($attemptId);
            if (!$attempt) return response()->json(['message' => 'No encontrado'], 404);

            $now = now();
            $expiresAt = Carbon::parse($attempt->expires_at);
            if ($now->greaterThan($expiresAt)) {
                if ($attempt->status !== 'completed') $this->finishExam($attemptId);
                return response()->json(['status' => 'expired'], 403);
            }

            $selectedOptionRaw = $request->input('option_id');
            $selectedOptionId = $this->optionMap[strtoupper($selectedOptionRaw)] ?? $selectedOptionRaw;

            AttemptQuestion::where('exam_attempt_id', $attemptId)
                ->where('question_id', $request->input('question_id'))
                ->update(['selected_option_id' => $selectedOptionId]);
            
            $attempt->update(['last_question_index' => $request->input('current_index')]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * FINALIZAR EXAMEN
     */
    public function finishExam($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $attempt = DB::table('exam_attempts')->where('id', $id)->first();
                
                if (!$attempt || $attempt->status === 'completed') {
                    return response()->json(['message' => 'Ya finalizado'], 400);
                }

                $questions = DB::table('attempt_questions')
                    ->join('question_banks', 'attempt_questions.question_id', '=', 'question_banks.id')
                    ->where('attempt_questions.exam_attempt_id', $id)
                    ->select('attempt_questions.selected_option_id', 'question_banks.right_answer')
                    ->get();

                $total = $questions->count();
                $correctCount = 0;
                foreach ($questions as $q) {
                    if ((int)$q->selected_option_id === (int)$q->right_answer) $correctCount++;
                }
                
                $score = $total > 0 ? round(($correctCount / $total) * 100) : 0;
                $hasPassed = $score >= 60 ? 1 : 0;

                DB::table('quiz_assignments')
                    ->where('student_id', $attempt->student_id)
                    ->where('language_id', $attempt->language_id)
                    ->where('test_type', 'CompTest')
                    ->update([
                        'active'     => 0,
                        'attended'   => 1,
                        'passed'     => $hasPassed,
                        'updated_at' => now()
                    ]);

                DB::table('exam_attempts')->where('id', $id)->update([
                    'score'           => $score,
                    'correct_answers' => $correctCount,
                    'status'          => 'completed',
                    'completed_at'    => now(),
                    'updated_at'      => now(),
                ]);

                return response()->json([
                    'status' => 'success',
                    'score'  => $score,
                    'correct_count' => $correctCount,
                    'total_questions' => $total
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
/**
 * INICIAR EXAMEN COMPTEST (COMPUTARIZADO)
 */
/**
 * INICIAR EXAMEN COMPTEST (COMPUTARIZADO)
 */
public function startRandomExam(Request $request)
{ 
    try {
        $studentId = auth()->id();
        
        $compAssignment = DB::table('quiz_assignments')
            ->where('student_id', $studentId)
            ->where('test_type', 'CompTest')
            ->where('active', 1)
            ->first();

        if (!$compAssignment) {
            return response()->json([
                'message' => 'No tienes una cita programada activa para este examen.'
            ], 403);
        }

        if (!$compAssignment->is_unlocked) {
            return response()->json([
                'message' => 'Acceso Bloqueado. Por favor, solicite al encargado del laboratorio que habilite su equipo.'
            ], 403);
        }

        $existingAttempt = DB::table('exam_attempts')
            ->where('student_id', $studentId)
            ->where('status', 'in_progress')
            ->first();

        if ($existingAttempt) {
            return response()->json([
                'status' => 'reconnected', 
                'attempt_id' => $existingAttempt->id,
                'message' => 'Reanudando examen pendiente...'
            ]);
        }

        return DB::transaction(function () use ($studentId, $compAssignment) {
            // Seleccionar un QUIZ aleatorio del idioma
            $quiz = DB::table('quizzes')
                ->where('language_id', $compAssignment->language_id)
                ->where('type', 'comp_test')
                ->inRandomOrder()
                ->first();

            if (!$quiz) {
                // Intentar sin filtrar por type
                $quiz = DB::table('quizzes')
                    ->where('language_id', $compAssignment->language_id)
                    ->inRandomOrder()
                    ->first();
            }

            if (!$quiz) {
                return response()->json(['message' => 'No hay exámenes configurados en el sistema.'], 404);
            }

            $totalQuestions = $quiz->total_questions ?? 15;

            // ✅ CÓDIGO CORREGIDO - SIN quiz_assignment_id
            $attemptId = DB::table('exam_attempts')->insertGetId([
                'student_id'          => $studentId,
                'quiz_id'             => $quiz->id,
                'language_id'         => $compAssignment->language_id, 
                'started_at'          => now(),
                'expires_at'          => now()->addMinutes((int) $quiz->duration_minutes), 
                'last_question_index' => 0,
                'status'              => 'in_progress',
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            $questions = DB::table('question_banks')
                ->where('quiz_id', $quiz->id)
                ->inRandomOrder()
                ->limit($totalQuestions)
                ->get();

            foreach ($questions as $index => $q) {
                DB::table('attempt_questions')->insert([
                    'exam_attempt_id' => $attemptId,
                    'question_id'     => $q->id,
                    'order_position'  => $index + 1,
                    'created_at'      => now(),
                ]);
            }

            return response()->json([
                'status' => 'started', 
                'attempt_id' => $attemptId
            ]);
        });

    } catch (\Exception $e) {
        \Log::error('Error en startRandomExam: ' . $e->getMessage());
        return response()->json(['message' => 'Error interno: ' . $e->getMessage()], 500);
    }
}
    /**
     * HISTORIAL DE RESULTADOS
     */
    public function getResultsHistory()
    {
        $history = DB::table('exam_attempts')
            ->where('student_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json(['data' => $history]);
    }

    /**
     * OBTENER ÚLTIMO EXAMEN FALTADO
     */
    public function getLastMissedExam(Request $request)
{
    try {
        $student = auth()->user();
        
        if (!$student) {
            return response()->json(['error' => 'No autenticado'], 401);
        }
        
        $missedAssignment = QuizAssignment::where('student_id', $student->id)
            ->where(function($query) {
                $query->where('attended', 0)   // Solo attended = 0
                      ->orWhere('attended', 2); // O attended = 2 (marcado como inasistencia)
            })
            ->where('start_at', '<', now())
            ->orderBy('start_at', 'desc')
            ->first();
        
        if ($missedAssignment) {
            return response()->json([
                'success' => true,
                'start_at' => $missedAssignment->start_at,
                'quiz_title' => $missedAssignment->quiz->title ?? 'Examen',
                'status' => $missedAssignment->attended == 0 ? 'absent' : 'missed',
                'assignment_id' => $missedAssignment->id
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'No se encontraron exámenes faltados'
        ], 404);
        
    } catch (\Exception $e) {
        \Log::error('Error en getLastMissedExam: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error' => 'Error al obtener el examen faltado',
            'message' => $e->getMessage()
        ], 500);
    }
}
    public function getMissedExams()
{
    try {
        $studentId = auth()->id();
        
        $missedExams = DB::table('quiz_assignments')
            ->where('student_id', $studentId)
            ->where('attended', 0)
            ->where('start_at', '<', now())
            ->orderBy('start_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $missedExams
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Error en getMissedExams: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}
}