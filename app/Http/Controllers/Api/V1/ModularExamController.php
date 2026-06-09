<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\QuizAssignment;
use App\Models\QuizAssignmentModule;
use App\Models\ModularExamAttempt;
use App\Models\ModularAnswer;
use App\Models\Language;
use App\Models\Verification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ModularExamController extends Controller
{
    const DEFAULT_MODULE_DURATION = 300; // 5 minutos por defecto si no tiene duration_seconds

    /**
     * ============================================================
     * VERIFICACIÓN DE DISPONIBILIDAD (PÚBLICO)
     * GET /api/v1/student/modular-exam/check-availability/{languageId}
     * ============================================================
     */
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
                $count = DB::table('modules')
                    ->where('language_id', $languageId)
                    ->where('level', $need['level'])
                    ->whereRaw('LOWER(TRIM(type)) = ?', [$need['type']])
                    ->count();
                
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
     * CARGAR MÓDULOS DEL EXAMEN (MÉTODO PRIVADO)
     * ============================================================
     */
    private function loadExamModules($languageId, &$totalDurationSeconds)
    {
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
        $totalDurationSeconds = 0;
        
        foreach ($modulesOrder as $index => $order) {
            Log::info("Buscando módulo: level={$order['level']}, type={$order['type']}");
            
            $module = DB::table('modules')
                ->where('language_id', $languageId)
                ->where('level', $order['level'])
                ->whereRaw('LOWER(TRIM(type)) = ?', [$order['type']])
                ->whereNotIn('id', $usedModuleIds)
                ->inRandomOrder()
                ->first();
            
            if (!$module) {
                Log::warning("No se encontró módulo específico para: {$order['level']} - {$order['type']}, buscando alternativo");
                
                $module = DB::table('modules')
                    ->where('language_id', $languageId)
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
                
                // Obtener duración del módulo (priorizar duration_seconds, si no usar valor por defecto)
                $moduleDuration = $module->duration_seconds ?? self::DEFAULT_MODULE_DURATION;
                $totalDurationSeconds += $moduleDuration;
                
                Log::info("Módulo encontrado: ID={$module->id}, duración={$moduleDuration}s, total acumulado={$totalDurationSeconds}s");
                
                $questions = DB::table('module_questions')
                    ->where('module_id', $module->id)
                    ->orderBy('order_position', 'asc')
                    ->get();
                
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
                    'duration_seconds' => $moduleDuration,
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
        
        if (count($modulesData) < 8) {
            Log::error("SOLO " . count($modulesData) . " DE 8 MÓDULOS CARGADOS. Faltan: " . implode(', ', $missingModules));
            return [
                'success' => false,
                'error' => 'No hay suficientes módulos para completar el examen',
                'modules_loaded' => count($modulesData),
                'modules_needed' => 8,
                'missing_modules' => $missingModules,
                'message' => 'Contacta al administrador: faltan módulos ' . implode(', ', $missingModules)
            ];
        }
        
        return [
            'success' => true,
            'modules' => $modulesData,
            'total_duration' => $totalDurationSeconds
        ];
    }

    /**
     * ============================================================
     * CARGAR EXAMEN MODULAR
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
            
            // Cargar módulos y calcular duración total
            $modulesResult = $this->loadExamModules($assignment->language_id, $totalDuration);
            
            if (!$modulesResult['success']) {
                return response()->json($modulesResult, 400);
            }
            
            $modulesData = $modulesResult['modules'];
            $totalDurationSeconds = $modulesResult['total_duration'];
            
            Log::info("✅ 8 módulos cargados exitosamente. Duración total: {$totalDurationSeconds} segundos (" . round($totalDurationSeconds / 60) . " minutos)");
            
            // Buscar o crear attempt
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
                    'expires_at' => now()->addSeconds($totalDurationSeconds),
                    'time_left' => $totalDurationSeconds,
                    'played_audios' => json_encode([]),
                    'viewed_media' => json_encode([]),
                    'answers' => json_encode([]),
                    'total_score' => 0,
                    'total_percentage' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                $attempt = DB::table('modular_exam_attempts')
                    ->where('id', $attemptId)
                    ->first();
                    
                Log::info("Nuevo attempt creado: {$attempt->id}, duración total: {$totalDurationSeconds}s");
            } else {
                Log::info("Attempt existente: {$attempt->id}, módulo actual: {$attempt->current_module_index}");
            }
            
            // Calcular tiempo restante (priorizar time_left guardado)
            $timeLeft = $attempt->time_left > 0 
                ? $attempt->time_left 
                : max(0, Carbon::parse($attempt->expires_at)->diffInSeconds(now(), false));
            
            $savedAnswers = json_decode($attempt->answers ?? '[]', true);
            $playedAudios = json_decode($attempt->played_audios ?? '[]', true);
            $viewedMedia = json_decode($attempt->viewed_media ?? '[]', true);
            
            return response()->json([
                'success' => true,
                'attempt_id' => $attempt->id,
                'current_module' => $attempt->current_module_index,
                'modules' => $modulesData,
                'total_modules' => count($modulesData),
                'total_duration_seconds' => $totalDurationSeconds,
                'time_left' => $timeLeft,
                'saved_answers' => $savedAnswers,
                'played_audios' => $playedAudios,
                'viewed_media' => $viewedMedia,
                'status' => $attempt->status
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en loadExam: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * ============================================================
     * SINCRONIZAR TIMER (llamado cada 5 segundos)
     * POST /api/v1/student/modular-exam/sync-timer/{attemptId}
     * ============================================================
     */
    public function syncTimer(Request $request, $attemptId)
    {
        try {
            $request->validate([
                'time_left' => 'required|integer|min:0'
            ]);
            
            $attempt = DB::table('modular_exam_attempts')
                ->where('id', $attemptId)
                ->where('student_id', auth()->id())
                ->first();
            
            if (!$attempt) {
                return response()->json(['error' => 'Intento no encontrado'], 404);
            }
            
            DB::table('modular_exam_attempts')
                ->where('id', $attemptId)
                ->update([
                    'time_left' => $request->time_left,
                    'last_sync_at' => now(),
                    'updated_at' => now()
                ]);
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            Log::error('Error en syncTimer: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * ============================================================
     * REGISTRAR AUDIO REPRODUCIDO
     * POST /api/v1/student/modular-exam/audio-played/{attemptId}
     * ============================================================
     */
    public function registerAudioPlayed(Request $request, $attemptId)
    {
        try {
            $request->validate([
                'module_id' => 'required|integer'
            ]);
            
            $attempt = DB::table('modular_exam_attempts')
                ->where('id', $attemptId)
                ->where('student_id', auth()->id())
                ->first();
            
            if (!$attempt) {
                return response()->json(['error' => 'Intento no encontrado'], 404);
            }
            
            $playedAudios = json_decode($attempt->played_audios ?? '[]', true);
            
            if (!in_array($request->module_id, $playedAudios)) {
                $playedAudios[] = $request->module_id;
            }
            
            DB::table('modular_exam_attempts')
                ->where('id', $attemptId)
                ->update([
                    'played_audios' => json_encode($playedAudios),
                    'updated_at' => now()
                ]);
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            Log::error('Error en registerAudioPlayed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * ============================================================
     * REGISTRAR IMAGEN VISTA
     * POST /api/v1/student/modular-exam/media-viewed/{attemptId}
     * ============================================================
     */
    public function registerMediaViewed(Request $request, $attemptId)
    {
        try {
            $request->validate([
                'module_id' => 'required|integer',
                'media_type' => 'required|in:audio,image'
            ]);
            
            $attempt = DB::table('modular_exam_attempts')
                ->where('id', $attemptId)
                ->where('student_id', auth()->id())
                ->first();
            
            if (!$attempt) {
                return response()->json(['error' => 'Intento no encontrado'], 404);
            }
            
            $viewedMedia = json_decode($attempt->viewed_media ?? '[]', true);
            $key = $request->media_type . '_' . $request->module_id;
            
            if (!in_array($key, $viewedMedia)) {
                $viewedMedia[] = $key;
            }
            
            DB::table('modular_exam_attempts')
                ->where('id', $attemptId)
                ->update([
                    'viewed_media' => json_encode($viewedMedia),
                    'updated_at' => now()
                ]);
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            Log::error('Error en registerMediaViewed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * ============================================================
     * EVENTO DE SEGURIDAD
     * POST /api/v1/student/modular-exam/security-event
     * ============================================================
     */
    public function securityEvent(Request $request)
    {
        try {
            DB::table('security_logs')->insert([
                'user_id' => auth()->id(),
                'exam_attempt_id' => $request->exam_attempt_id ?? null,
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
            $totalModules = 8;
            $nextIndex = $currentIndex + 1;
            
            Log::info("Módulo actual: {$currentIndex}, Siguiente: {$nextIndex}, Total: {$totalModules}");
            
            if ($nextIndex >= $totalModules) {
                Log::info("=== FINALIZANDO EXAMEN ===");
                
                $results = $this->calculateResults($attemptId);
                
                Log::info("Resultados calculados:", [
                    'total_score' => $results['total_score'],
                    'total_points' => $results['total_points'],
                    'total_percentage' => $results['total_percentage']
                ]);
                
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
                
                Log::info("Examen finalizado. Aprobado: " . ($results['total_percentage'] >= 60 ? 'SÍ' : 'NO'));
                
                return response()->json([
                    'success' => true,
                    'completed' => true,
                    'results' => $results
                ]);
            } 
            
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
     * ============================================================
     * FINALIZAR EXAMEN MODULAR
     * POST /api/v1/student/modular-exam/finish/{attemptId}
     * ============================================================
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
     * INVALIDAR EXAMEN POR EXPULSIÓN
     * POST /api/v1/student/modular-exam/invalidate/{attemptId}
     * ============================================================
     */
    public function invalidateExam($attemptId)
    {
        try {
            $attempt = DB::table('modular_exam_attempts')
                ->where('id', $attemptId)
                ->where('student_id', auth()->id())
                ->first();
            
            if (!$attempt) {
                return response()->json(['error' => 'Intento no encontrado'], 404);
            }
            
            DB::table('modular_exam_attempts')
                ->where('id', $attemptId)
                ->update([
                    'status' => 'invalidated',
                    'completed_at' => now(),
                    'updated_at' => now()
                ]);
            
            QuizAssignment::where('id', $attempt->assignment_id)->update([
                'attended' => 1,
                'active' => 0,
                'updated_at' => now()
            ]);
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            Log::error('Error en invalidateExam: ' . $e->getMessage());
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
        
        foreach ($results['by_level'] as $level => $data) {
            $results['by_level'][$level]['percentage'] = $data['total'] > 0 
                ? round(($data['score'] / $data['total']) * 100) 
                : 0;
        }
        
        foreach ($results['by_type'] as $type => $data) {
            $results['by_type'][$type]['percentage'] = $data['total'] > 0 
                ? round(($data['score'] / $data['total']) * 100) 
                : 0;
        }
        
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
     * LISTA DE EXÁMENES MODULARES COMPLETADOS (ADMIN)
     * GET /api/v1/admin/modular-reports
     * ============================================================
     */
    public function adminIndex()
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
            Log::error('Error en adminIndex modular reports: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}