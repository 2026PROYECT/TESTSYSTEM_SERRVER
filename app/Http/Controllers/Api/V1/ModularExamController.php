<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\QuizAssignment;
use App\Models\Quiz;
use App\Models\Module;
use App\Models\QuizAssignmentModule;
use App\Models\ModularExamAttempt;
use App\Models\ModularAnswer;
use App\Models\Language;
use App\Models\Verification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ModularExamController extends Controller
{
    /**
     * ============================================================
     * VERIFICACIÓN DE DISPONIBILIDAD (PÚBLICO)
     * GET /api/v1/student/modular-exam/check-availability/{languageId}
     * ============================================================
     */
    const TOTAL_TIME = 40; // 40 minutos para el examen modular
    public function checkAvailability($languageId)
    {
        try {
            Log::info("Verificando disponibilidad para language_id: {$languageId}");
            
            $modulesNeeded = [
                ['level' => 'A1', 'type' => 'listening'],
                ['level' => 'A1', 'type' => 'reading'],
                ['level' => 'A2', 'type' => 'listening'],
                ['level' => 'A2', 'type' => 'reading'],
                ['level' => 'B1', 'type' => 'listening'],
                ['level' => 'B1', 'type' => 'reading'],
                ['level' => 'B2', 'type' => 'listening'],
                ['level' => 'B2', 'type' => 'reading'],
            ];
            
            $availability = [];
            $allAvailable = true;
            $missingModules = [];
            
            foreach ($modulesNeeded as $need) {
                // Buscar módulos con case-insensitive
                $count = DB::table('modules')
                    ->where('language_id', $languageId)
                    ->where('level', $need['level'])
                    ->whereRaw('LOWER(TRIM(type)) = ?', [$need['type']])
                    ->count();
                
                // Verificar que tengan preguntas
                $hasQuestions = false;
                if ($count > 0) {
                    $hasQuestions = DB::table('modules')
                        ->join('module_questions', 'modules.id', '=', 'module_questions.module_id')
                        ->where('modules.language_id', $languageId)
                        ->where('modules.level', $need['level'])
                        ->whereRaw('LOWER(TRIM(modules.type)) = ?', [$need['type']])
                        ->exists();
                }
                
                $isAvailable = ($count > 0 && $hasQuestions);
                
                $availability[] = [
                    'level' => $need['level'],
                    'type' => $need['type'],
                    'required' => 1,
                    'available' => $count,
                    'has_questions' => $hasQuestions,
                    'is_available' => $isAvailable
                ];
                
                if (!$isAvailable) {
                    $allAvailable = false;
                    $missingModules[] = "{$need['level']} - {$need['type']}";
                }
            }
            
            Log::info("Verificación completada. Todo disponible: " . ($allAvailable ? 'SÍ' : 'NO'));
            
            return response()->json([
                'success' => true,
                'language_id' => (int)$languageId,
                'all_available' => $allAvailable,
                'modules_needed' => 8,
                'modules_available' => count(array_filter($availability, fn($m) => $m['is_available'])),
                'availability' => $availability,
                'missing_modules' => $missingModules,
                'can_start_exam' => $allAvailable,
                'message' => $allAvailable 
                    ? '✅ Todos los módulos están disponibles. Puedes comenzar el examen.'
                    : '❌ Faltan módulos para completar el examen. Contacta al administrador.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en checkAvailability: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * ============================================================
     * VERIFICACIÓN DE DISPONIBILIDAD (MÉTODO PRIVADO)
     * ============================================================
     */
    private function checkModuleAvailability($languageId)
    {
        $modulesNeeded = [
            ['level' => 'A1', 'type' => 'listening'],
            ['level' => 'A1', 'type' => 'reading'],
            ['level' => 'A2', 'type' => 'listening'],
            ['level' => 'A2', 'type' => 'reading'],
            ['level' => 'B1', 'type' => 'listening'],
            ['level' => 'B1', 'type' => 'reading'],
            ['level' => 'B2', 'type' => 'listening'],
            ['level' => 'B2', 'type' => 'reading'],
        ];
        
        $missing = [];
        $available = [];
        
        foreach ($modulesNeeded as $need) {
            $count = DB::table('modules')
                ->where('language_id', $languageId)
                ->where('level', $need['level'])
                ->whereRaw('LOWER(TRIM(type)) = ?', [$need['type']])
                ->count();
            
            if ($count == 0) {
                $missing[] = "{$need['level']} - {$need['type']}";
            } else {
                $available[] = "{$need['level']} - {$need['type']} ({$count})";
            }
        }
        
        return [
            'available' => empty($missing),
            'missing' => $missing,
            'available_summary' => $available,
            'message' => empty($missing) 
                ? 'Todos los módulos están disponibles'
                : 'Faltan módulos: ' . implode(', ', $missing)
        ];
    }


    /**
     * ============================================================
     * CREAR EXAMEN MODULAR (CON VERIFICACIÓN)
     * POST /api/v1/student/modular-exam/create-random
     * ============================================================
     */
    public function createRandomModularExam(Request $request)
    {
        try {
            $user = auth()->user();
            $languageId = $request->language_id ?? 1;
            
            Log::info("Creando examen modular para usuario: {$user->id}, language_id: {$languageId}");
            
            // ✅ VERIFICAR DISPONIBILIDAD ANTES DE CREAR
            $availabilityCheck = $this->checkModuleAvailability($languageId);
            
            if (!$availabilityCheck['available']) {
                Log::warning("No se puede crear examen: faltan módulos", $availabilityCheck['missing']);
                
                return response()->json([
                    'success' => false,
                    'error' => 'No es posible crear el examen modular',
                    'message' => $availabilityCheck['message'],
                    'missing_modules' => $availabilityCheck['missing'],
                    'available_modules' => $availabilityCheck['available_summary']
                ], 400);
            }
            
            // Verificar si ya existe un examen activo
            $existingAssignment = QuizAssignment::where('student_id', $user->id)
                ->where('test_type', 'ModularTest')
                ->where('active', 1)
                ->first();
            
            if ($existingAssignment) {
                Log::info("Usuario ya tiene examen activo: {$existingAssignment->id}");
                
                return response()->json([
                    'success' => true,
                    'assignment_id' => $existingAssignment->id,
                    'message' => 'Ya tienes un examen modular activo'
                ]);
            }
            
            // Crear nuevo assignment
            $assignment = QuizAssignment::create([
                'student_id' => $user->id,
                'language_id' => $languageId,
                'test_type' => 'ModularTest',
                'start_at' => now(),
                'active' => 1,
                'attended' => 0,
                'passed' => 0,
                'is_unlocked' => 1
            ]);
            
            Log::info("Examen modular creado exitosamente: {$assignment->id}");
            
            return response()->json([
                'success' => true,
                'assignment_id' => $assignment->id,
                'message' => 'Examen modular creado exitosamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en createRandomModularExam: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * ============================================================
     * CARGAR EXAMEN MODULAR (SELECCIONA 8 MÓDULOS)
     * GET /api/v1/student/modular-exam/load/{assignmentId}
     * ============================================================
     */
    public function loadExam($assignmentId)
    {
        try {
            Log::info("Cargando examen modular: {$assignmentId}");
            
            $assignment = QuizAssignment::where('id', $assignmentId)
                ->where('student_id', auth()->id())
                ->where('test_type', 'ModularTest')
                ->first();
            
            if (!$assignment) {
                Log::error("Examen no encontrado: {$assignmentId}");
                return response()->json(['error' => 'Examen no encontrado'], 404);
            }
            
            // Orden de módulos requeridos (8 módulos)
            $modulesOrder = [
                ['level' => 'A1', 'type' => 'listening'],
                ['level' => 'A1', 'type' => 'reading'],
                ['level' => 'A2', 'type' => 'listening'],
                ['level' => 'A2', 'type' => 'reading'],
                ['level' => 'B1', 'type' => 'listening'],
                ['level' => 'B1', 'type' => 'reading'],
                ['level' => 'B2', 'type' => 'listening'],
                ['level' => 'B2', 'type' => 'reading'],
            ];
            
            $modulesData = [];
            $usedModuleIds = [];
            $missingModules = [];
            
            foreach ($modulesOrder as $index => $order) {
                Log::info("Buscando módulo: level={$order['level']}, type={$order['type']}");
                
                // Búsqueda principal (case-insensitive)
                $module = DB::table('modules')
                    ->where('language_id', $assignment->language_id)
                    ->where('level', $order['level'])
                    ->whereRaw('LOWER(TRIM(type)) = ?', [$order['type']])
                    ->whereNotIn('id', $usedModuleIds)
                    ->inRandomOrder()
                    ->first();
                
                // Si no encuentra, buscar cualquier módulo del nivel
                if (!$module) {
                    Log::warning("No se encontró módulo específico para: {$order['level']} - {$order['type']}, buscando alternativo");
                    
                    $module = DB::table('modules')
                        ->where('language_id', $assignment->language_id)
                        ->where('level', $order['level'])
                        ->whereNotIn('id', $usedModuleIds)
                        ->inRandomOrder()
                        ->first();
                    
                    if ($module) {
                        Log::info("Usando módulo alternativo: ID={$module->id}, type={$module->type} para requerido {$order['type']}");
                    }
                }
                
                if ($module) {
                    $usedModuleIds[] = $module->id;
                    
                    // Cargar preguntas del módulo
                    $questions = DB::table('module_questions')
                        ->where('module_id', $module->id)
                        ->orderBy('order_position', 'asc')
                        ->get();
                    
                    Log::info("Módulo encontrado: ID={$module->id}, preguntas=" . $questions->count());
                    
                    $questionsData = [];
                    foreach ($questions as $question) {
                        $questionsData[] = [
                            'id' => $question->id,
                            'text' => $question->question_text,
                            'options' => [
                                'A' => $question->option_a,
                                'B' => $question->option_b,
                                'C' => $question->option_c,
                                'D' => $question->option_d,
                            ],
                            'points' => $question->points ?? 1
                        ];
                    }
                    
                    $modulesData[] = [
                        'id' => $module->id,
                        'title' => $module->title,
                        'level' => $module->level,
                        'type' => $module->type,
                        'content' => $module->content,
                        'audio' => $module->audio ? '/storage/' . ltrim($module->audio, '/') : null,
                        'picture' => $module->picture ? '/storage/' . ltrim($module->picture, '/') : null,
                        'questions' => $questionsData,
                        'order' => $index
                    ];
                } else {
                    $missingModules[] = "{$order['level']} - {$order['type']}";
                    Log::error("Módulo NO encontrado para: {$order['level']} - {$order['type']}");
                }
            }
            
            // ✅ VERIFICAR QUE SE CARGARON LOS 8 MÓDULOS
            if (count($modulesData) < 8) {
                Log::error("SOLO " . count($modulesData) . " DE 8 MÓDULOS CARGADOS. Faltan: " . implode(', ', $missingModules));
                
                return response()->json([
                    'success' => false,
                    'error' => 'No hay suficientes módulos para completar el examen',
                    'modules_loaded' => count($modulesData),
                    'modules_needed' => 8,
                    'missing_modules' => $missingModules,
                    'message' => 'Contacta al administrador: faltan módulos ' . implode(', ', $missingModules)
                ], 400);
            }
            
            Log::info("✅ 8 módulos cargados exitosamente");
            
            // Crear o recuperar intento (attempt)
            $attempt = DB::table('modular_exam_attempts')
                ->where('assignment_id', $assignmentId)
                ->where('student_id', auth()->id())
                ->first();
            
            if (!$attempt) {
                $attemptId = DB::table('modular_exam_attempts')->insertGetId([
                    'assignment_id' => $assignmentId,
                    'student_id' => auth()->id(),
                    'current_module_index' => 0,
                    'status' => 'in_progress',
                    'started_at' => now(),
                    'expires_at' => now()->addMinutes(self::TOTAL_TIME),
                    'answers' => json_encode([]),
                    'total_score' => 0,
                    'total_percentage' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                $attempt = DB::table('modular_exam_attempts')
                    ->where('id', $attemptId)
                    ->first();
                    
                Log::info("Nuevo attempt creado: {$attempt->id}");
            } else {
                Log::info("Attempt existente: {$attempt->id}, módulo actual: {$attempt->current_module_index}");
            }
            
            $timeLeft = max(0, Carbon::parse($attempt->expires_at)->diffInSeconds(now(), false));
            $savedAnswers = json_decode($attempt->answers ?? '[]', true);
            
            return response()->json([
                'success' => true,
                'attempt_id' => $attempt->id,
                'current_module' => $attempt->current_module_index,
                'modules' => $modulesData,
                'total_modules' => count($modulesData),
                'time_left' => $timeLeft,
                'saved_answers' => $savedAnswers,
                'status' => $attempt->status
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en loadExam: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

public function securityEvent(Request $request)
{
    try {
        DB::table('security_logs')->insert([
            'user_id' => auth()->id(),
            'exam_attempt_id' => $request->exam_attempt_id ?? null, // 🔥 Permitir null
            'event_type' => $request->event_type,
            'details' => $request->details,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        return response()->json(['success' => true]);
        
    } catch (\Exception $e) {
        Log::error('Error security event: ' . $e->getMessage());
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}
    /**
     * ============================================================
     * GUARDAR RESPUESTAS DE UN MÓDULO
     * POST /api/v1/student/modular-exam/save/{attemptId}
     * ============================================================
     */
    public function saveModuleAnswers(Request $request, $attemptId)
    {
        try {
            Log::info("Guardando respuestas para attempt: {$attemptId}");
            
            $request->validate([
                'module_id' => 'required|integer',
                'answers' => 'required|array'
            ]);
            
            $attempt = DB::table('modular_exam_attempts')
                ->where('id', $attemptId)
                ->where('student_id', auth()->id())
                ->first();
            
            if (!$attempt) {
                Log::error("Intento no encontrado: {$attemptId}");
                return response()->json(['error' => 'Intento no encontrado'], 404);
            }
            
            $answers = json_decode($attempt->answers ?? '[]', true);
            $answers[$request->module_id] = $request->answers;
            
            DB::table('modular_exam_attempts')
                ->where('id', $attemptId)
                ->update([
                    'answers' => json_encode($answers),
                    'updated_at' => now()
                ]);
            
            Log::info("Respuestas guardadas correctamente para módulo: {$request->module_id}");
            
            return response()->json([
                'success' => true,
                'message' => 'Respuestas guardadas correctamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en saveModuleAnswers: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * ============================================================
     * AVANZAR AL SIGUIENTE MÓDULO O FINALIZAR
     * POST /api/v1/student/modular-exam/next/{attemptId}
     * ============================================================
     */
    public function nextModule(Request $request, $attemptId)
    {
        try {
            Log::info("=== nextModule llamado ===");
            Log::info("Attempt ID: {$attemptId}");
            
            $attempt = DB::table('modular_exam_attempts')
                ->where('id', $attemptId)
                ->where('student_id', auth()->id())
                ->first();
            
            if (!$attempt) {
                Log::error("Intento no encontrado: {$attemptId}");
                return response()->json(['error' => 'Intento no encontrado'], 404);
            }
            
            $currentIndex = $attempt->current_module_index;
            $totalModules = 8; // Total de módulos requeridos
            $nextIndex = $currentIndex + 1;
            
            Log::info("Módulo actual: {$currentIndex}, Siguiente: {$nextIndex}, Total: {$totalModules}");
            
            // Si es el último módulo, finalizar examen
            if ($nextIndex >= $totalModules) {
                Log::info("=== FINALIZANDO EXAMEN ===");
                
                $results = $this->calculateResults($attemptId);
                
                Log::info("Resultados calculados:", [
                    'total_score' => $results['total_score'],
                    'total_points' => $results['total_points'],
                    'total_percentage' => $results['total_percentage']
                ]);
                
                // Actualizar attempt
                DB::table('modular_exam_attempts')
                    ->where('id', $attemptId)
                    ->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'total_score' => $results['total_score'],
                        'total_percentage' => $results['total_percentage'],
                        'results_data' => json_encode($results),
                        'updated_at' => now()
                    ]);
                
                // Actualizar assignment
                QuizAssignment::where('id', $attempt->assignment_id)->update([
                    'passed' => $results['total_percentage'] >= 60 ? 1 : 0,
                    'attended' => 1,
                    'active' => 0,
                    'updated_at' => now()
                ]);
                
                Log::info("Examen finalizado. Aprobado: " . ($results['total_percentage'] >= 60 ? 'SÍ' : 'NO'));
                
                return response()->json([
                    'success' => true,
                    'completed' => true,
                    'results' => $results
                ]);
            } 
            
            // Avanzar al siguiente módulo
            Log::info("=== AVANZANDO AL MÓDULO {$nextIndex} ===");
            
            DB::table('modular_exam_attempts')
                ->where('id', $attemptId)
                ->update([
                    'current_module_index' => $nextIndex,
                    'updated_at' => now()
                ]);
            
            return response()->json([
                'success' => true,
                'completed' => false,
                'next_module' => $nextIndex,
                'message' => "Avanzando al módulo {$nextIndex}"
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en nextModule: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
/**
 * FINALIZAR EXAMEN MODULAR
 * POST /api/v1/student/modular-exam/finish/{attemptId}
 */
public function finishExam($attemptId)
{
    try {
        Log::info("Finalizando examen manualmente: {$attemptId}");
        
        $attempt = DB::table('modular_exam_attempts')
            ->where('id', $attemptId)
            ->where('student_id', auth()->id())
            ->first();
        
        if (!$attempt) {
            return response()->json(['error' => 'Intento no encontrado'], 404);
        }
        
        $results = $this->calculateResults($attemptId);
        
        DB::table('modular_exam_attempts')
            ->where('id', $attemptId)
            ->update([
                'status' => 'completed',
                'completed_at' => now(),
                'total_score' => $results['total_score'],
                'total_percentage' => $results['total_percentage'],
                'results_data' => json_encode($results),
                'updated_at' => now()
            ]);
        
        QuizAssignment::where('id', $attempt->assignment_id)->update([
            'passed' => $results['total_percentage'] >= 60 ? 1 : 0,
            'attended' => 1,
            'active' => 0,
            'updated_at' => now()
        ]);
        
        return response()->json([
            'success' => true,
            'results' => $results
        ]);
        
    } catch (\Exception $e) {
        Log::error('Error en finishExam: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    /**
     * ============================================================
     * CALCULAR RESULTADOS DEL EXAMEN
     * ============================================================
     */
    private function calculateResults($attemptId)
    {
        Log::info("Calculando resultados para attempt: {$attemptId}");
        
        $attempt = DB::table('modular_exam_attempts')
            ->where('id', $attemptId)
            ->first();
        
        $answers = json_decode($attempt->answers ?? '[]', true);
        
        Log::info("Respuestas encontradas para " . count($answers) . " módulos");
        
        $results = [
            'total_score' => 0,
            'total_points' => 0,
            'by_level' => [
                'A1' => ['score' => 0, 'total' => 0, 'percentage' => 0],
                'A2' => ['score' => 0, 'total' => 0, 'percentage' => 0],
                'B1' => ['score' => 0, 'total' => 0, 'percentage' => 0],
                'B2' => ['score' => 0, 'total' => 0, 'percentage' => 0]
            ],
            'by_type' => [
                'listening' => ['score' => 0, 'total' => 0, 'percentage' => 0],
                'reading' => ['score' => 0, 'total' => 0, 'percentage' => 0]
            ],
            'details' => []
        ];
        
        foreach ($answers as $moduleId => $moduleAnswers) {
            $module = DB::table('modules')->where('id', $moduleId)->first();
            
            if ($module) {
                $moduleScore = 0;
                $moduleTotal = 0;
                
                foreach ($moduleAnswers as $answer) {
                    $question = DB::table('module_questions')
                        ->where('id', $answer['question_id'])
                        ->first();
                    
                    if ($question) {
                        $moduleTotal += $question->points;
                        $correctLetter = $this->getOptionLetter($question->right_answer);
                        
                        if ($answer['selected_option'] === $correctLetter) {
                            $moduleScore += $question->points;
                        }
                    }
                }
                
                $percentage = $moduleTotal > 0 ? round(($moduleScore / $moduleTotal) * 100) : 0;
                
                Log::info("Módulo {$module->title}: {$moduleScore}/{$moduleTotal} = {$percentage}%");
                
                $results['details'][] = [
                    'module_id' => $module->id,
                    'title' => $module->title,
                    'level' => $module->level,
                    'type' => $module->type,
                    'score' => $moduleScore,
                    'total' => $moduleTotal,
                    'percentage' => $percentage
                ];
                
                $results['total_score'] += $moduleScore;
                $results['total_points'] += $moduleTotal;
                $results['by_level'][$module->level]['score'] += $moduleScore;
                $results['by_level'][$module->level]['total'] += $moduleTotal;
                $results['by_type'][$module->type]['score'] += $moduleScore;
                $results['by_type'][$module->type]['total'] += $moduleTotal;
            }
        }
        
        // Calcular porcentajes por nivel
        foreach ($results['by_level'] as $level => $data) {
            $results['by_level'][$level]['percentage'] = $data['total'] > 0 
                ? round(($data['score'] / $data['total']) * 100) 
                : 0;
        }
        
        // Calcular porcentajes por tipo
        foreach ($results['by_type'] as $type => $data) {
            $results['by_type'][$type]['percentage'] = $data['total'] > 0 
                ? round(($data['score'] / $data['total']) * 100) 
                : 0;
        }
        
        // Calcular porcentaje total
        $results['total_percentage'] = $results['total_points'] > 0 
            ? round(($results['total_score'] / $results['total_points']) * 100) 
            : 0;
        
        Log::info("RESULTADO FINAL: {$results['total_score']}/{$results['total_points']} = {$results['total_percentage']}%");
        
        return $results;
    }


    /**
     * ============================================================
     * CONVERTIR NÚMERO DE OPCIÓN A LETRA
     * ============================================================
     */
    private function getOptionLetter($number)
    {
        $letters = ['A', 'B', 'C', 'D'];
        return $letters[$number - 1] ?? 'A';
    }


    /**
     * ============================================================
     * OBTENER RESULTADOS DE UN INTENTO
     * GET /api/v1/student/modular-results/{attemptId}
     * ============================================================
     */
    public function getResults($attemptId)
    {
        try {
            Log::info("Obteniendo resultados para attempt: {$attemptId}");
            
            $attempt = DB::table('modular_exam_attempts')
                ->where('id', $attemptId)
                ->where('student_id', auth()->id())
                ->first();
            
            if (!$attempt) {
                Log::error("Resultado no encontrado: {$attemptId}");
                return response()->json(['error' => 'Resultado no encontrado'], 404);
            }
            
            $results = json_decode($attempt->results_data, true);
            
            return response()->json($results);
            
        } catch (\Exception $e) {
            Log::error('Error en getResults: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * ============================================================
     * OBTENER HISTORIAL DE EXÁMENES MODULARES
     * GET /api/v1/student/modular-history
     * ============================================================
     */
    public function getModularHistory(Request $request)
    {
        try {
            $user = auth()->user();
            Log::info("Obteniendo historial para usuario: {$user->id}");
            
            $attempts = DB::table('modular_exam_attempts')
                ->join('quiz_assignments', 'modular_exam_attempts.assignment_id', '=', 'quiz_assignments.id')
                ->join('languages', 'quiz_assignments.language_id', '=', 'languages.id')
                ->where('modular_exam_attempts.student_id', $user->id)
                ->where('modular_exam_attempts.status', 'completed')
                ->orderBy('modular_exam_attempts.completed_at', 'desc')
                ->select(
                    'modular_exam_attempts.id as attempt_id',
                    'modular_exam_attempts.total_percentage',
                    'modular_exam_attempts.completed_at',
                    'languages.name as language_name'
                )
                ->get();
            
            $history = [];
            foreach ($attempts as $attempt) {
                $history[] = [
                    'attempt_id' => $attempt->attempt_id,
                    'language_name' => $attempt->language_name,
                    'date' => Carbon::parse($attempt->completed_at)->format('d/m/Y H:i'),
                    'total_percentage' => $attempt->total_percentage,
                    'passed' => $attempt->total_percentage >= 60
                ];
            }
            
            Log::info("Historial encontrado: " . count($history) . " registros");
            
            return response()->json([
                'success' => true,
                'attempts' => $history
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en getModularHistory: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'attempts' => []
            ], 500);
        }
    }


    /**
     * ============================================================
     * DESCARGAR PDF DE RESULTADOS (ESTUDIANTE)
     * GET /api/v1/student/modular-results/{attemptId}/download
     * ============================================================
     */
   public function downloadResultsPDF($attemptId)
{
    try {
        Log::info("Descargando PDF para attempt: {$attemptId}");
        
        $attempt = DB::table('modular_exam_attempts')
            ->where('id', $attemptId)
            ->where('student_id', auth()->id())
            ->first();
        
        if (!$attempt) {
            return response()->json(['error' => 'Resultado no encontrado'], 404);
        }
        
        $results = json_decode($attempt->results_data, true);
        $student = auth()->user();
        $assignment = QuizAssignment::find($attempt->assignment_id);
        $language = Language::find($assignment->language_id);
        
        // Obtener carrera del estudiante
        $career = DB::table('students')
            ->leftJoin('careers', 'students.career_id', '=', 'careers.id')
            ->where('students.user_id', $student->id)
            ->select('careers.name as career_name')
            ->first();
        
        // Generar QR de verificación
        $verification = Verification::firstOrCreate(
            [
                'verifiable_id' => $attemptId,
                'verifiable_type' => 'App\Models\ModularExamAttempt'
            ],
            [
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'MODULAR_EXAM',
            ]
        );
        
        $urlVerificacion = url("/verify/{$verification->uuid}");
        $qrCode = base64_encode(
            QrCode::format('svg')
                ->size(100)
                ->errorCorrection('H')
                ->generate($urlVerificacion)
        );
        
        $data = [
            'student_name' => trim($student->name . ' ' . ($student->lastname ?? '') . ' ' . ($student->surname ?? '')),
            'student_email' => $student->email,
            'career' => $career->career_name ?? 'NO ASIGNADA',
            'language_name' => $language->name ?? 'No especificado',
            'date' => Carbon::parse($attempt->completed_at)->format('d/m/Y H:i'),
            'total_percentage' => $results['total_percentage'] ?? 0,
            'total_score' => $results['total_score'] ?? 0,
            'total_points' => $results['total_points'] ?? 0,
            'levels' => $results['by_level'] ?? [],
            'by_type' => $results['by_type'] ?? [],
            'details' => $results['details'] ?? [],
            'passed' => ($results['total_percentage'] ?? 0) >= 60,
            'qrCode' => $qrCode,
            'teacher_name' => 'Sistema'
        ];
        
        // ✅ Usando la vista Blade
        $pdf = Pdf::loadView('pdf.modular_results', $data);
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->download("Reporte_Modular_{$attemptId}.pdf");
        
    } catch (\Exception $e) {
        Log::error('Error en downloadResultsPDF: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    /**
     * ============================================================
     * OBTENER NIVEL DE TEXTO SEGÚN PORCENTAJE
     * ============================================================
     */
    private function getNivelText($percentage)
    {
        if ($percentage >= 90) return "C2 - MAESTRÍA";
        if ($percentage >= 80) return "C1 - AVANZADO DOMINIO OPERATIVO EFICAZ";
        if ($percentage >= 60) return "B2 - INTERMEDIO-ALTO";
        if ($percentage >= 50) return "B1 - INTERMEDIO";
        if ($percentage >= 40) return "A2 - BÁSICO";
        return "A1 - PRINCIPIANTE";
    }


    /**
     * ============================================================
     * ==================== ADMIN REPORTS ========================
     * ============================================================
     */


    /**
     * LISTA DE EXÁMENES MODULARES COMPLETADOS (ADMIN)
     * GET /api/v1/admin/modular-reports
     */
    public function index()
    {
        try {
            Log::info("Admin obteniendo lista de exámenes modulares");
            
            $attempts = DB::table('modular_exam_attempts')
                ->join('users', 'modular_exam_attempts.student_id', '=', 'users.id')
                ->join('quiz_assignments', 'modular_exam_attempts.assignment_id', '=', 'quiz_assignments.id')
                ->join('languages', 'quiz_assignments.language_id', '=', 'languages.id')
                ->where('modular_exam_attempts.status', 'completed')
                ->orderBy('modular_exam_attempts.completed_at', 'desc')
                ->select(
                    'modular_exam_attempts.id',
                    'modular_exam_attempts.total_score',
                    'modular_exam_attempts.total_percentage',
                    'modular_exam_attempts.results_data',
                    'modular_exam_attempts.completed_at',
                    DB::raw("CONCAT(users.name, ' ', users.lastname, ' ', users.surname) as student_name"),
                    'users.email as student_email',
                    'languages.name as language_name'
                )
                ->get();
            
            $processed = $attempts->map(function ($attempt) {
                $results = json_decode($attempt->results_data, true);
                return [
                    'id' => $attempt->id,
                    'student_name' => $attempt->student_name,
                    'student_email' => $attempt->student_email,
                    'language_name' => $attempt->language_name,
                    'total_score' => $attempt->total_score,
                    'total_points' => $results['total_points'] ?? 0,
                    'total_percentage' => $attempt->total_percentage,
                    'completed_at' => $attempt->completed_at,
                    'results_data' => $results
                ];
            });
            
            Log::info("Total exámenes encontrados: " . $processed->count());
            
            return response()->json($processed);
            
        } catch (\Exception $e) {
            Log::error('Error en index modular reports: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * EXPORTAR PDF INDIVIDUAL (ADMIN)
     * GET /api/v1/admin/modular-reports/{id}/pdf
     */
    public function exportPdfIndividual($id)
    {
        try {
            Log::info("Admin exportando PDF individual: {$id}");
            
            $attempt = DB::table('modular_exam_attempts')->where('id', $id)->first();
            if (!$attempt) {
                return response()->json(['error' => 'Resultado no encontrado'], 404);
            }
            
            $student = DB::table('users')->where('id', $attempt->student_id)->first();
            $assignment = QuizAssignment::find($attempt->assignment_id);
            $language = Language::find($assignment->language_id);
            $results = json_decode($attempt->results_data, true);
            
            $l_score = $results['by_type']['listening']['score'] ?? 0;
            $l_total = $results['by_type']['listening']['total'] ?? 0;
            $r_score = $results['by_type']['reading']['score'] ?? 0;
            $r_total = $results['by_type']['reading']['total'] ?? 0;
            $total_percentage = $attempt->total_percentage;
            $levels = $results['by_level'] ?? [];
            
            // Generar QR
            $verification = Verification::firstOrCreate(
                [
                    'verifiable_id' => $attempt->id,
                    'verifiable_type' => 'App\Models\ModularExamAttempt'
                ],
                [
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'type' => 'EXAMEN MODULAR',
                ]
            );
            
            $urlVerificacion = url("/verify/{$verification->uuid}");
            $qrcode = base64_encode(
                QrCode::format('svg')
                    ->size(100)
                    ->errorCorrection('H')
                    ->generate($urlVerificacion)
            );
            
            $evaluador = auth()->user();
            $fullName = trim(($student->name ?? '') . ' ' . ($student->lastname ?? '') . ' ' . ($student->surname ?? ''));
            $studentName = !empty($fullName) ? $fullName : ($student->name ?? 'N/A');
            
          $studentData = DB::table('students')
    ->leftJoin('careers', 'students.career_id', '=', 'careers.id')
    ->where('students.user_id', $attempt->student_id)
    ->select('careers.name as career_name')
    ->first();
            
            $careerName = $studentData->career_name ?? 'NO ASIGNADA';
            $completed_at = $attempt->completed_at;
            
            $html = '
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <style>
                    @page {
                        margin-top: 5cm;
                        margin-right: 2cm;
                        margin-bottom: 2.5cm;
                        margin-left: 2cm;
                    }
                    body { 
                        font-family: \'Arial\', sans-serif; 
                        font-size: 11px; 
                        line-height: 1.2; 
                    }
                    
                    .table-container { 
                        width: 100%; 
                        border-collapse: collapse; 
                        margin-bottom: 15px; 
                    }
                    .table-container th, .table-container td { 
                        border: 1px solid #000; 
                        padding: 5px; 
                        text-align: center; 
                    }
                    
                    .title-header { 
                        font-weight: bold; 
                        font-size: 15px; 
                        text-align: center; 
                        margin-bottom: 10px; 
                        text-transform: uppercase; 
                    }
                    .section-label { 
                        text-align: left !important; 
                        font-size: 12px; 
                        font-weight: bold; 
                        border: none !important; 
                        padding: 10px 0 5px 0; 
                    }

                    .bg-gray { 
                        background-color: #ffffff; 
                        font-weight: bold; 
                        font-size: 9px; 
                    }
                    .full-name { 
                        font-size: 11px; 
                        font-weight: bold; 
                        text-transform: uppercase; 
                    }
                    
                    .final-score-box { 
                        font-size: 28px; 
                        font-weight: bold; 
                        vertical-align: middle; 
                    }
                    
                    .signature-table { 
                        width: 100%; 
                        margin-top: 60px; 
                        border: none !important; 
                    }
                    .signature-table td { 
                        border: none !important; 
                        text-align: center; 
                        width: 50%; 
                        vertical-align: bottom; 
                    }
                    .sig-line { 
                        border-top: 1px solid #000; 
                        width: 80%; 
                        margin: 0 auto; 
                        padding-top: 5px; 
                        font-weight: bold; 
                        text-transform: uppercase; 
                        font-size: 9px; 
                    }
                </style>
            </head>
            <body>

                <div class="title-header">
                    PLANILLA DE EVALUACIÓN<br>
                    EXAMEN MODULAR - ' . strtoupper($language->name ?? 'IDIOMA') . '
                </div>

                <div class="section-label">I. INFORMACIÓN PERSONAL DEL EVALUADO</div>
                <table class="table-container">
                    <tr>
                        <td class="bg-gray" width="20%"><strong>NOMBRE:</strong></td>
                        <td class="full-name" width="55%" style="text-align: left; padding-left: 10px;">
                            ' . $studentName . '
                        </td>
                        <td width="12%" rowspan="2" class="bg-gray"><div align="right"><strong>NOTA FINAL</strong></div></td>
                        <td class="final-score-box" width="13%" rowspan="2" style="font-size: 24px; background: #f8fafc;">
                            ' . $total_percentage . '%
                        </td>
                    </tr>
                    
                    <tr>
                        <td colspan="2" style="padding: 0;">
                            <table width="100%" style="border: none; border-collapse: collapse;">
                                <tr>
                                    <td class="bg-gray" style="border: none; border-right: 1px solid #000;" width="25%"><strong>CARRERA:</strong></td>
                                    <td style="border: none; border-right: 1px solid #000; text-align: left; padding-left: 10px;" width="40%">
                                        ' . $careerName . '
                                    </td>
                                    <td class="bg-gray" style="border: none; border-right: 1px solid #000;" width="15%"><strong>IDIOMA:</strong></td>
                                    <td style="border: none; text-align: center;" width="20%">
                                        ' . ($language->name ?? 'N/A') . '
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <tr>
                        <td class="bg-gray"><strong>FECHA EVALUACIÓN</strong></td>
                        <td colspan="3" style="text-align: left; padding-left: 10px;">
                            ' . \Carbon\Carbon::parse($completed_at)->format('d \d\e F \d\e Y') . '
                        </td>
                    </tr>
                </table>

                <div class="section-label">II. RESULTADOS DE LA EVALUACIÓN</div>
                <table class="table-container">
                    <tr>
                        <th width="30%" rowspan="2">CRITERIO</th>
                        <th colspan="2">PARCIAL</th>
                        <th width="15%" rowspan="2">TOTAL<br>PREGUNTAS</th>
                        <th width="15%" rowspan="2">NOTA<br>FINAL</th>
                    </tr>
                    <tr>
                        <th width="15%">CORRECTAS</th>
                        <th width="15%">INCORRECTAS</th>
                    </tr>
                    <tr>
                        <td style="text-align: left; font-weight: bold;">COMPRENSIÓN AUDITIVA</td>
                        <td>' . $l_score . '</td>
                        <td>' . ($l_total - $l_score) . '</td>
                        <td>' . $l_total . '</td>
                        <td rowspan="2" class="final-score-box">' . $total_percentage . '%</td>
                    </tr>
                    <tr>
                        <td style="text-align: left; font-weight: bold;">COMPRENSIÓN DE LECTURA</td>
                        <td>' . $r_score . '</td>
                        <td>' . ($r_total - $r_score) . '</td>
                        <td>' . $r_total . '</td>
                    </tr>
                </table>

                <div class="section-label">III. RESULTADOS POR NIVEL</div>
                <table class="table-container">
                    <tr>
                        <th width="25%">NIVEL</th>
                        <th width="25%">PUNTAJE</th>
                        <th width="25%">PORCENTAJE</th>
                        <th width="25%">ESTADO</th>
                    </tr>';
            
            foreach ($levels as $levelName => $levelData) {
                $html .= '
                    <tr>
                        <td style="text-align: center; font-weight: bold;">' . $levelName . '</td>
                        <td style="text-align: center;">' . $levelData['score'] . '/' . $levelData['total'] . '</td>
                        <td style="text-align: center;">' . $levelData['percentage'] . '%</td>
                        <td style="text-align: center;">
                            <span style="color: ' . ($levelData['percentage'] >= 60 ? '#059669' : '#e11d48') . '; font-weight: bold;">
                                ' . ($levelData['percentage'] >= 60 ? 'APROBADO' : 'REPROBADO') . '
                            </span>
                        </td>
                    </tr>';
            }
            
            $html .= '
                </table>

                <div class="section-label">IV. NIVEL DE CONOCIMIENTO ALCANZADO</div>
                <table class="table-container">
                    <tr>
                        <td style="padding: 15px; font-size: 14px; font-weight: bold;">
                            ' . $this->getNivelText($total_percentage) . '
                        </td>
                    </tr>
                </table>

                <table class="signature-table">
                    <tr>
                        <td>
                            <div class="sig-line" style="text-transform: uppercase; font-weight: bold; margin-bottom: 5px;">
                                ' . $studentName . '
                            </div>
                            CONFORMIDAD ESTUDIANTE
                        </td>
                        <td>
                            <div class="sig-line" style="text-transform: uppercase; font-weight: bold; margin-bottom: 5px;">
                                ' . ($evaluador->name ?? '') . ' ' . ($evaluador->lastname ?? '') . '
                            </div>
                            EVALUADOR
                        </td>
                    </tr>
                </table>

                <div style="text-align: center; margin-top: 20px;">
                    <img src="data:image/svg+xml;base64,' . $qrcode . '" width="100" height="100">
                    <p style="font-size: 7px; color: #64748b; margin-top: 5px; text-transform: uppercase;">
                        Validación Electrónica de Autenticidad
                    </p>
                </div>
            </body>
            </html>';
            
            $pdf = Pdf::loadHTML($html);
            return $pdf->download("Reporte_Modular_{$id}.pdf");
            
        } catch (\Exception $e) {
            Log::error('Error PDF Individual Modular: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


  /**
 * EXPORTAR LISTA GENERAL (ADMIN)
 * GET /api/v1/admin/modular-reports/export-pdf
 */

public function exportPdfGeneral(Request $request)
{
    try {
        Log::info("Admin exportando PDF general");
        
        $query = DB::table('modular_exam_attempts')
            ->join('users', 'modular_exam_attempts.student_id', '=', 'users.id')
            ->join('quiz_assignments', 'modular_exam_attempts.assignment_id', '=', 'quiz_assignments.id')
            ->join('languages', 'quiz_assignments.language_id', '=', 'languages.id')
            ->where('modular_exam_attempts.status', 'completed');
        
        $filter = $request->filter;
        if ($filter === 'passed') {
            $query->where('modular_exam_attempts.total_percentage', '>=', 60);
            $title = "REPORTE DE EXÁMENES MODULARES APROBADOS";
        } elseif ($filter === 'failed') {
            $query->where('modular_exam_attempts.total_percentage', '<', 60);
            $title = "REPORTE DE EXÁMENES MODULARES REPROBADOS";
        } else {
            $title = "CONSOLIDADO GENERAL DE EXÁMENES MODULARES";
        }
        
        $attempts = $query->orderBy('modular_exam_attempts.completed_at', 'desc')
            ->select(
                'modular_exam_attempts.id',
                'modular_exam_attempts.student_id',
                'modular_exam_attempts.total_percentage',
                'modular_exam_attempts.completed_at',
                DB::raw("CONCAT(users.name, ' ', users.lastname, ' ', COALESCE(users.surname, '')) as student_name"),
                'languages.name as language_name'
            )
            ->get();
        
        // ==============================================
        // Preparar lista de estudiantes con id_number
        // ==============================================
        $studentsList = [];
        foreach ($attempts as $attempt) {
            // Obtener el id_number del estudiante
            $studentData = DB::table('students')
                ->where('user_id', $attempt->student_id)
                ->first();
            
            $idNumber = $studentData->id_number ?? 'N/D';
            
            $studentsList[] = [
                'full_name' => trim($attempt->student_name),
                'id_number' => $idNumber,
                'language' => $attempt->language_name,
                'percentage' => (int)$attempt->total_percentage,
                'date' => Carbon::parse($attempt->completed_at)->format('d/m/Y')
            ];
        }
        
        // Calcular estadísticas
        $totalStudents = count($attempts);
        $approvedCount = $attempts->where('total_percentage', '>=', 60)->count();
        $failedCount = $attempts->where('total_percentage', '<', 60)->count();
        
        // ==============================================
        // Crear nueva verificación con metadata completo
        // ==============================================
        $verification = Verification::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'verifiable_type' => null,
            'verifiable_id' => null,
            'type' => 'MODULAR_GENERAL_REPORT',
            'metadata' => json_encode([
                'students' => $studentsList,
                'since' => $request->since ?? null,
                'until' => $request->until ?? null,
                'generated_by' => auth()->user()->name ?? 'Sistema',
                'filter' => $filter ?: 'all',
                'total_students' => $totalStudents,
                'approved_count' => $approvedCount,
                'failed_count' => $failedCount
            ], JSON_UNESCAPED_UNICODE),  // ← Para mantener caracteres especiales
            'scans_count' => 0
        ]);
        
        // ==============================================
        // Generar QR con URL de verificación
        // ==============================================
        $urlVerificacion = url("/verify/{$verification->uuid}");
        $qrCode = base64_encode(
            QrCode::format('svg')
                ->size(80)
                ->errorCorrection('H')
                ->generate($urlVerificacion)
        );
        
        $html = $this->generateGeneralReportHTML($title, $attempts, $qrCode, $verification);
        
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('letter', 'portrait');
        
        Log::info("PDF general generado: " . $attempts->count() . " registros");
        
        return $pdf->download("reporte_modular_" . now()->format('Ymd_His') . ".pdf");
        
    } catch (\Exception $e) {
        Log::error('Error PDF General Modular: ' . $e->getMessage());
        Log::error($e->getTraceAsString());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
   /**
 * GENERAR HTML PARA REPORTE GENERAL
 */
private function generateGeneralReportHTML($title, $attempts, $qrCode, $verification = null)
{
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . $title . '</title>
        <style>
            @page { margin: 2cm; size: letter portrait; }
            body { font-family: Arial, sans-serif; font-size: 10px; }
            h1 { text-align: center; color: #1e293b; font-size: 16px; }
            .header { text-align: center; margin-bottom: 20px; position: relative; }
            .qr-container { position: absolute; top: 0; right: 0; text-align: center; }
            .qr-container img { width: 65px; height: 65px; }
            .qr-text { font-size: 6px; color: #64748b; margin-top: 2px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 9px; }
            th { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px 5px; text-align: center; }
            td { border: 1px solid #cbd5e1; padding: 8px 5px; }
            .text-left { text-align: left; }
            .text-center { text-align: center; }
            .approved { color: #059669; font-weight: bold; }
            .failed { color: #e11d48; font-weight: bold; }
            .footer { margin-top: 30px; text-align: center; font-size: 8px; color: #94a3b8; }
            .verification-code { 
                background: #f8fafc; 
                padding: 5px 10px; 
                border-radius: 8px; 
                font-family: monospace; 
                font-size: 8px;
                display: inline-block;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="qr-container">
                <img src="data:image/svg+xml;base64,' . $qrCode . '">
                <div class="qr-text">Validación Oficial</div>
            </div>
            <h1>' . $title . '</h1>
            <p>Sistema de Evaluación de Idiomas - EmiSystem</p>
            <p>Generado por: ' . (auth()->user()->name ?? 'Sistema') . ' | Fecha: ' . now()->format('d/m/Y H:i') . '</p>';
    
    if ($verification) {
        $html .= '<p class="verification-code" style="margin-top: 10px;"> Código de verificación: ' . strtoupper(substr($verification->uuid, 0, 8)) . '</p>';
    }
    
    $html .= '
        </div>
        
        <table>
            <thead>
                <tr>
                    <th class="text-left">Estudiante</th>
                    <th class="text-left">Idioma</th>
                    <th>Puntaje</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($attempts as $attempt) {
        $status = $attempt->total_percentage >= 60 ? 'APROBADO' : 'REPROBADO';
        $statusClass = $attempt->total_percentage >= 60 ? 'approved' : 'failed';
        
        $html .= '
                <tr>
                    <td class="text-left">' . ($attempt->student_name ?? 'N/A') . '</td>
                    <td class="text-left">' . ($attempt->language_name ?? 'N/A') . '</td>
                    <td class="text-center">' . number_format($attempt->total_percentage, 1) . '%</td>
                    <td class="text-center">' . Carbon::parse($attempt->completed_at)->format('d/m/Y') . '</td>
                    <td class="text-center ' . $statusClass . '">' . $status . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
        
        <div class="footer">
            <p>Documento generado automáticamente por EmiSystem - Reporte de Exámenes Modulares</p>
            <p>Verifica la autenticidad de este documento escaneando el código QR o ingresando el código en emisystem.edu/verify</p>
        </div>
    </body>
    </html>';
    
    return $html;
}
}