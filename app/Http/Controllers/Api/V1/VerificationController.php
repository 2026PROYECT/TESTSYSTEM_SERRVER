<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Verification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerificationController extends Controller
{
    public function show($uuid): JsonResponse
    {
        try {
            // 🔥 ELIMINAR EL WITH PROBLEMÁTICO - Buscar sin relaciones
            $verification = Verification::where('uuid', $uuid)->first();

            if (!$verification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento no encontrado'
                ], 404);
            }

            // Incrementar contador de escaneos
            $verification->increment('scans_count');

            // Variables por defecto
            $responseData = [
                'type' => $verification->type,
                'header' => [
                    'title' => 'Documento Válido',
                    'icon' => '💻',
                    'color' => 'blue'
                ],
                'students_list' => [],
                'stats' => [
                    ['label' => 'Estado', 'value' => 'Verificado']
                ],
                'list' => null,
                'meta' => [
                    ['label' => 'Generado:', 'value' => $verification->created_at->format('d/m/Y')],
                    ['label' => 'ID Verif:', 'value' => strtoupper(substr($uuid, 0, 8))],
                    ['label' => 'Origen:', 'value' => 'BASE DE DATOS OFICIAL'],
                ],
                'footer' => 'EmiSystem - Verificación de Integridad Académica'
            ];

            // === SEGÚN EL TIPO DE VERIFICACIÓN ===
            switch ($verification->type) {

                /**
                 * ==========================================================
                 * 1. EXAMEN MODULAR INDIVIDUAL (Certificado)
                 * ==========================================================
                 */
                case 'MODULAR_EXAM':
                    // Cargar relaciones solo aquí
                    $verification->load('verifiable.student.career');
                    $source = $verification->verifiable;
                    if (!$source) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Datos del examen no encontrados'
                        ], 404);
                    }

                    $responseData['header'] = [
                        'title' => 'Certificado de Examen Modular',
                        'icon' => '📘',
                        'color' => 'indigo'
                    ];
                    
                    $studentUser = $source->student;
                    if ($studentUser) {
                        $responseData['student'] = [
                            'full_name' => trim("{$studentUser->lastname} {$studentUser->name} {$studentUser->surname}"),
                            'id_card' => $studentUser->id_number ?? 'N/D',
                            'name' => $studentUser->name ?? 'N/D',
                            'lastname' => $studentUser->lastname ?? 'N/D',
                            'career' => $studentUser->career->name ?? 'Carrera no registrada'
                        ];
                    }
                    
                    $assignment = $source->assignment;
                    $language = $assignment?->language;
                    $languageName = $language->name ?? 'No especificado';
                    
                    $results = $source->results_data;
                    if (is_string($results)) {
                        $results = json_decode($results, true);
                    }
                    if (!is_array($results)) {
                        $results = [];
                    }
                    
                    $totalPercentage = $source->total_percentage ?? 0;
                    $totalScore = $source->total_score ?? 0;
                    $totalPoints = $results['total_points'] ?? 0;
                    $passed = $totalPercentage >= 60;
                    
                    $listeningScore = $results['by_type']['listening']['score'] ?? 0;
                    $listeningTotal = $results['by_type']['listening']['total'] ?? 0;
                    $listeningPercentage = $listeningTotal > 0 ? round(($listeningScore / $listeningTotal) * 100) : 0;
                    
                    $readingScore = $results['by_type']['reading']['score'] ?? 0;
                    $readingTotal = $results['by_type']['reading']['total'] ?? 0;
                    $readingPercentage = $readingTotal > 0 ? round(($readingScore / $readingTotal) * 100) : 0;
                    
                    $responseData['stats'] = [
                        ['label' => '🗣️ Idioma', 'value' => $languageName],
                        ['label' => '🎯 Puntaje Total', 'value' => "{$totalScore} / {$totalPoints} puntos"],
                        ['label' => '📊 Porcentaje', 'value' => "{$totalPercentage}%"],
                        ['label' => '🎧 Listening', 'value' => "{$listeningPercentage}% ({$listeningScore}/{$listeningTotal})"],
                        ['label' => '📖 Reading', 'value' => "{$readingPercentage}% ({$readingScore}/{$readingTotal})"],
                        ['label' => '📅 Fecha', 'value' => $source->completed_at ? date('d/m/Y', strtotime($source->completed_at)) : 'N/A'],
                        ['label' => 'Estado', 'value' => $passed ? '✅ APROBADO' : '❌ REPROBADO'],
                    ];
                    break;

                /**
                 * ==========================================================
                 * 2. EXAMEN COMPUTARIZADO INDIVIDUAL (Certificado)
                 * ==========================================================
                 */
                case 'COMP_EXAM':
                    $verification->load('verifiable.student.career');
                    $source = $verification->verifiable;
                    if (!$source) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Datos del examen no encontrados'
                        ], 404);
                    }

                    $responseData['header'] = [
                        'title' => 'Certificado de Examen Computarizado',
                        'icon' => '💻',
                        'color' => 'emerald'
                    ];
                    
                    $studentUser = $source->student;
                    if ($studentUser) {
                        $responseData['student'] = [
                            'full_name' => trim("{$studentUser->lastname} {$studentUser->name} {$studentUser->surname}"),
                            'id_card' => $studentUser->id_number ?? 'N/D',
                            'name' => $studentUser->name ?? 'N/D',
                            'lastname' => $studentUser->lastname ?? 'N/D',
                            'career' => $studentUser->career->name ?? 'Carrera no registrada'
                        ];
                    }
                    
                    $quizTitle = $source->quiz->title ?? 'No especificado';
                    $score = $source->score ?? 0;
                    $passed = $score >= 60;
                    
                    $l_correct = 0;
                    $l_total = 0;
                    $r_correct = 0;
                    $r_total = 0;
                    
                    foreach ($source->attemptQuestions as $aq) {
                        $q = $aq->question;
                        if (!$q) continue;
                        
                        $isCorrect = (int)$aq->selected_option_id === (int)$q->right_answer;
                        $area = strtoupper(trim($q->area ?? 'R'));
                        
                        if ($area === 'L') {
                            $l_total++;
                            if ($isCorrect) $l_correct++;
                        } else {
                            $r_total++;
                            if ($isCorrect) $r_correct++;
                        }
                    }
                    
                    $responseData['stats'] = [
                        ['label' => '📝 Examen', 'value' => $quizTitle],
                        ['label' => '🎯 Puntaje Total', 'value' => "{$score}%"],
                        ['label' => '🎧 Listening', 'value' => "{$l_correct} / {$l_total}"],
                        ['label' => '📖 Reading', 'value' => "{$r_correct} / {$r_total}"],
                        ['label' => '📅 Fecha', 'value' => $source->completed_at ? date('d/m/Y', strtotime($source->completed_at)) : 'N/A'],
                        ['label' => 'Estado', 'value' => $passed ? '✅ APROBADO' : '❌ REPROBADO'],
                    ];
                    break;

                /**
                 * ==========================================================
                 * 3. EXAMEN ORAL INDIVIDUAL (Certificado)
                 * ==========================================================
                 */
                case 'ORAL_EXAM':
                    $verification->load('verifiable.student.career');
                    $source = $verification->verifiable;
                    if (!$source) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Datos de examen no encontrados'
                        ], 404);
                    }

                    $responseData['header'] = [
                        'title' => 'Evaluación Oral Válida',
                        'icon' => '🛡️',
                        'color' => 'emerald'
                    ];
                    
                    $studentUser = $source->student;
                    if ($studentUser) {
                        $responseData['student'] = [
                            'full_name' => trim("{$studentUser->lastname} {$studentUser->name}"),
                            'id_card' => $studentUser->id_number ?? 'N/D',
                            'name' => $studentUser->name ?? 'N/D',
                            'lastname' => $studentUser->lastname ?? 'N/D',
                            'career' => $studentUser->career->name ?? 'Carrera no registrada'
                        ];
                    }
                    
                    $responseData['stats'] = [
                        ['label' => 'Nota Final', 'value' => number_format($source->final_score, 2)],
                        ['label' => 'Nivel', 'value' => $source->final_level ?? 'N/E'],
                    ];
                    break;

                /**
                 * ==========================================================
                 * 4. REPORTE DE USUARIO BLOQUEADO (MODULAR)
                 * ==========================================================
                 */
                case 'MODULAR_USER_REPORT':
                    $meta = $verification->metadata;
                    if (is_string($meta)) {
                        $meta = json_decode($meta, true);
                    }
                    if (!is_array($meta)) {
                        $meta = [];
                    }
                    
                    $responseData['header'] = [
                        'title' => 'Reporte de Usuario Bloqueado - Examen Modular',
                        'icon' => '🚫',
                        'color' => 'red'
                    ];
                    
                    $responseData['student'] = [
                        'full_name' => $meta['user_name'] ?? 'No especificado',
                        'id_card' => 'N/A',
                        'name' => $meta['user_name'] ?? 'N/A',
                        'lastname' => '',
                        'career' => 'Sistema de Evaluación'
                    ];
                    
                    $invalidatedExams = $meta['invalidated_exams'] ?? [];
                    $totalViolations = $meta['total_violations'] ?? 0;
                    
                    $responseData['stats'] = [
                        ['label' => '👤 Usuario', 'value' => $meta['user_email'] ?? 'N/A'],
                        ['label' => '🚫 Exámenes Invalidados', 'value' => (string)count($invalidatedExams)],
                        ['label' => '⚠️ Total Violaciones', 'value' => (string)$totalViolations],
                        ['label' => '📅 Fecha Reporte', 'value' => $verification->created_at->format('d/m/Y H:i')],
                        ['label' => '🔒 Estado', 'value' => 'DOCUMENTO DE SEGURIDAD'],
                    ];
                    
                    $responseData['students_list'] = array_map(function($exam) {
                        return [
                            'attempt_id' => $exam['attempt_id'],
                            'started_at' => date('d/m/Y H:i', strtotime($exam['started_at'])),
                            'violations_count' => $exam['violations_count'],
                            'status' => 'INVALIDADO'
                        ];
                    }, $invalidatedExams);
                    break;

                /**
                 * ==========================================================
                 * 5. REPORTE DE EXAMEN MODULAR INVALIDADO (Individual)
                 * ==========================================================
                 */
                case 'MODULAR_EXAM_REPORT':
                    $meta = $verification->metadata;
                    if (is_string($meta)) {
                        $meta = json_decode($meta, true);
                    }
                    if (!is_array($meta)) {
                        $meta = [];
                    }
                    
                    $responseData['header'] = [
                        'title' => 'Reporte de Examen Modular Invalidado',
                        'icon' => '📘',
                        'color' => 'red'
                    ];
                    
                    $responseData['student'] = [
                        'full_name' => $meta['student_name'] ?? 'No especificado',
                        'id_card' => 'N/A',
                        'name' => $meta['student_name'] ?? 'N/A',
                        'lastname' => '',
                        'career' => 'Sistema de Evaluación'
                    ];
                    
                    $responseData['stats'] = [
                        ['label' => '📝 ID Examen', 'value' => (string)($meta['attempt_id'] ?? 'N/A')],
                        ['label' => '⚠️ Total Violaciones', 'value' => (string)($meta['violations_count'] ?? 0)],
                        ['label' => '📅 Fecha Reporte', 'value' => $verification->created_at->format('d/m/Y H:i')],
                        ['label' => '🔒 Estado', 'value' => 'EXAMEN INVALIDADO'],
                    ];
                    break;

                /**
                 * ==========================================================
                 * 6. REPORTE GENERAL MODULAR
                 * ==========================================================
                 */
                case 'MODULAR_GENERAL_REPORT':
                    $meta = $verification->metadata;
                    if (is_string($meta)) {
                        $meta = json_decode($meta, true);
                    }
                    if (!is_array($meta)) {
                        $meta = [];
                    }
                    
                    $responseData['header'] = [
                        'title' => 'Reporte Consolidado - Exámenes Modulares',
                        'icon' => '📊',
                        'color' => 'blue'
                    ];
                    
                    $studentsList = $meta['students'] ?? [];
                    $totalStudents = $meta['total_students'] ?? count($studentsList);
                    $approvedCount = $meta['approved_count'] ?? 0;
                    $failedCount = $meta['failed_count'] ?? 0;
                    
                    $responseData['students_list'] = $studentsList;
                    $responseData['stats'] = [
                        ['label' => '📅 Fecha emisión', 'value' => $verification->created_at->format('d/m/Y H:i')],
                        ['label' => '👨‍🎓 Total Estudiantes', 'value' => (string)$totalStudents],
                        ['label' => '✅ Aprobados', 'value' => (string)$approvedCount],
                        ['label' => '❌ Reprobados', 'value' => (string)$failedCount],
                        ['label' => '🔒 Estado', 'value' => 'DOCUMENTO OFICIAL'],
                    ];
                    break;

                /**
                 * ==========================================================
                 * 7. REPORTE GENERAL COMPUTARIZADO
                 * ==========================================================
                 */
                case 'COMP_GENERAL_REPORT':
                    $meta = $verification->metadata;
                    if (is_string($meta)) {
                        $meta = json_decode($meta, true);
                    }
                    if (!is_array($meta)) {
                        $meta = [];
                    }
                    
                    $responseData['header'] = [
                        'title' => 'Reporte Consolidado - Exámenes Computarizados',
                        'icon' => '📊',
                        'color' => 'emerald'
                    ];
                    
                    $studentsList = $meta['students'] ?? [];
                    $totalStudents = $meta['total_students'] ?? count($studentsList);
                    $approvedCount = $meta['approved_count'] ?? 0;
                    $failedCount = $meta['failed_count'] ?? 0;
                    
                    $responseData['students_list'] = $studentsList;
                    $responseData['stats'] = [
                        ['label' => '📅 Fecha emisión', 'value' => $verification->created_at->format('d/m/Y H:i')],
                        ['label' => '👨‍🎓 Total Estudiantes', 'value' => (string)$totalStudents],
                        ['label' => '✅ Aprobados', 'value' => (string)$approvedCount],
                        ['label' => '❌ Reprobados', 'value' => (string)$failedCount],
                        ['label' => '🔒 Estado', 'value' => 'DOCUMENTO OFICIAL'],
                    ];
                    break;

                /**
                 * ==========================================================
                 * 8. REPORTES GENERALES (GLOBAL_REPORT, ORAL_ONLY_REPORT)
                 * ==========================================================
                 */
                case 'GLOBAL_REPORT':
                case 'ORAL_ONLY_REPORT':
                    $meta = $verification->metadata;
                    if (is_string($meta)) {
                        $meta = json_decode($meta, true);
                    }
                    if (!is_array($meta)) {
                        $meta = [];
                    }
                    
                    $responseData['header'] = [
                        'title' => $verification->type === 'GLOBAL_REPORT' ? 'Reporte Grupal Verificado' : 'Reporte Oral Verificado',
                        'icon' => '📊',
                        'color' => 'indigo'
                    ];
                    
                    $studentsList = [];
                    if (isset($meta['students']) && is_array($meta['students']) && count($meta['students']) > 0) {
                        $studentsList = $meta['students'];
                    } elseif (isset($meta['student_ids']) && is_array($meta['student_ids']) && count($meta['student_ids']) > 0) {
                        $studentsFromDb = DB::table('users as u')
                            ->join('students as s', 'u.id', '=', 's.user_id')
                            ->leftJoin('careers as c', 's.career_id', '=', 'c.id')
                            ->whereIn('u.id', $meta['student_ids'])
                            ->select(
                                'u.id',
                                'u.name',
                                'u.lastname',
                                's.id_number as id_card',
                                'c.name as career_name'
                            )
                            ->get();
                        
                        foreach ($studentsFromDb as $student) {
                            $studentsList[] = [
                                'full_name' => trim($student->lastname . ' ' . $student->name),
                                'id_card' => $student->id_card ?? 'N/D',
                                'name' => $student->name,
                                'lastname' => $student->lastname,
                                'career' => $student->career_name ?? 'N/A'
                            ];
                        }
                    }
                    
                    $responseData['student'] = [
                        'full_name' => $verification->type === 'GLOBAL_REPORT' 
                            ? 'Consolidado Histórico de Calificaciones' 
                            : 'Consolidado de Evaluaciones Orales',
                        'id_card' => 'N/A',
                        'name' => 'Sistema',
                        'lastname' => 'EmiSystem',
                        'career' => 'EmiSystem - Gestión Académica'
                    ];
                    
                    $fechaDesde = isset($meta['since']) && $meta['since'] ? date('d/m/Y', strtotime($meta['since'])) : 'Inicio';
                    $fechaHasta = isset($meta['until']) && $meta['until'] ? date('d/m/Y', strtotime($meta['until'])) : 'Fin';

                    $responseData['students_list'] = $studentsList;
                    $responseData['stats'] = [
                        ['label' => 'Periodo', 'value' => "$fechaDesde - $fechaHasta"],
                        ['label' => 'Cantidad Estudiantes', 'value' => (string)count($studentsList)],
                        ['label' => 'Estado', 'value' => 'OFICIAL'],
                    ];
                    break;

                /**
                 * ==========================================================
                 * 9. CASO POR DEFECTO
                 * ==========================================================
                 */
                default:
                    $responseData['student'] = [
                        'full_name' => 'Registro de Sistema',
                        'id_card' => 'N/A',
                        'name' => 'Usuario',
                        'lastname' => 'Verificado',
                        'career' => 'EMI - Validación'
                    ];
                    break;
            }

            return response()->json($responseData);

        } catch (\Exception $e) {
            Log::error('Error en VerificationController@show: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar el documento: ' . $e->getMessage()
            ], 500);
        }
    }
}