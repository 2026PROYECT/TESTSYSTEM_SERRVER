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
            $verification = Verification::with([
                'verifiable.student.studentProfile.career'
            ])->where('uuid', $uuid)->first();

            if (!$verification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento no encontrado'
                ], 404);
            }

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

            // ⚠️ SOLO agregar 'student' si es necesario (no para reportes generales)
            // El bloque 'student' se agregará solo en los casos que lo necesiten

            // === SEGÚN EL TIPO DE VERIFICACIÓN ===
            switch ($verification->type) {

                /**
                 * ==========================================================
                 * EXAMEN MODULAR INDIVIDUAL
                 * ==========================================================
                 */
                case 'MODULAR_EXAM':
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
                    
                    // Datos del estudiante
                    $studentUser = $source->student;
                    if ($studentUser) {
                        $responseData['student'] = [
                            'full_name' => trim("{$studentUser->lastname} {$studentUser->name} {$studentUser->surname}"),
                            'id_card' => $studentUser->studentProfile->id_number ?? 'N/D',
                            'name' => $studentUser->name ?? 'N/D',
                            'lastname' => $studentUser->lastname ?? 'N/D',
                            'career' => $studentUser->studentProfile->career->name ?? 'Carrera no registrada'
                        ];
                    }
                    
                    // Datos del examen
                    $assignment = $source->assignment;
                    $language = $assignment?->language;
                    $languageName = $language->name ?? 'No especificado';
                    
                    // Resultados (seguro)
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
                    
                    // Resultados por habilidad
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
                 * REPORTE GENERAL MODULAR
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
                      
                        'icon' => '📊',
                        'color' => 'blue'
                    ];
                    
                    $studentsList = $meta['students'] ?? [];
                    $totalStudents = $meta['total_students'] ?? count($studentsList);
                    $approvedCount = $meta['approved_count'] ?? 0;
                    $failedCount = $meta['failed_count'] ?? 0;
                    
                    // ✅ NO se agrega el bloque 'student' (no hay "Carnet: N/D")
                    // ✅ NO se agrega "Reporte Institucional"
                    
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
                 * REPORTES GENERALES (GLOBAL_REPORT, ORAL_ONLY_REPORT)
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
                 * EXAMEN ORAL INDIVIDUAL
                 * ==========================================================
                 */
                case 'ORAL_EXAM':
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
                            'id_card' => $studentUser->studentProfile->id_number ?? 'N/D',
                            'name' => $studentUser->name ?? 'N/D',
                            'lastname' => $studentUser->lastname ?? 'N/D',
                            'career' => $studentUser->studentProfile->career->name ?? 'Carrera no registrada'
                        ];
                    }
                    
                    $responseData['stats'] = [
                        ['label' => 'Nota Final', 'value' => number_format($source->final_score, 2)],
                        ['label' => 'Nivel', 'value' => $source->final_level ?? 'N/E'],
                    ];
                    break;

                /**
                 * ==========================================================
                 * CASO POR DEFECTO
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