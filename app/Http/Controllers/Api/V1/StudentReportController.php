<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Verification;
use App\Models\User;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class StudentReportController extends Controller
{
    /**
     * Lista general de reportes para la vista de Vue.
     */
    public function index()
    {
        try {
            $reports = DB::table('users as u')
                ->join('students as s', 'u.id', '=', 's.user_id')
                ->join('quiz_assignments as qa', 'u.id', '=', 'qa.student_id')
                ->join('languages as l', 'qa.language_id', '=', 'l.id')
                ->leftJoin('careers as c', 's.career_id', '=', 'c.id')
                ->select(
                    'u.id as student_id',
                    'l.id as language_id',
                    'l.name as language_name',
                    'c.name as career_name',
                    DB::raw("CONCAT(u.name, ' ', u.lastname) as full_name"),
                    
                    // ORAL
                    DB::raw("GROUP_CONCAT(DISTINCT orf.final_score ORDER BY orf.created_at DESC) as oral_history"),
                    DB::raw("GROUP_CONCAT(DISTINCT orf.final_level ORDER BY orf.created_at DESC) as level_history"),
                    DB::raw("GROUP_CONCAT(DISTINCT DATE_FORMAT(orf.created_at, '%d/%m/%y') ORDER BY orf.created_at DESC) as oral_dates"),

                    // COMPUTARIZADO
                    DB::raw("GROUP_CONCAT(DISTINCT ea.score ORDER BY ea.created_at DESC) as comp_history"),
                    DB::raw("GROUP_CONCAT(DISTINCT DATE_FORMAT(ea.created_at, '%d/%m/%y') ORDER BY ea.created_at DESC) as comp_dates"),
                    
                    // MODULAR
                    DB::raw("GROUP_CONCAT(DISTINCT mea.total_percentage ORDER BY mea.completed_at DESC) as modular_history"),
                    DB::raw("GROUP_CONCAT(DISTINCT DATE_FORMAT(mea.completed_at, '%d/%m/%y') ORDER BY mea.completed_at DESC) as modular_dates")
                )
                ->leftJoin('oral_results as orf', function($join) {
                    $join->on('u.id', '=', 'orf.student_id')
                         ->on('l.id', '=', 'orf.language_id');
                })
                ->leftJoin('exam_attempts as ea', function($join) {
                    $join->on('u.id', '=', 'ea.student_id')
                         ->on('l.id', '=', 'ea.language_id');
                })
                ->leftJoin('modular_exam_attempts as mea', function($join) {
                    $join->on('u.id', '=', 'mea.student_id');
                })
                ->groupBy('u.id', 'l.id', 'u.name', 'u.lastname', 'c.name', 'l.name')
                ->orderBy('u.name', 'asc')
                ->get();

            return response()->json($reports);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Detalle de un estudiante para modales o vistas previas.
     */
    public function show(Request $request, $id)
    {
        $langId = $request->query('language_id');

        $oralHistory = DB::table('oral_results as or')
            ->join('quiz_assignments as qa', 'or.quiz_assignment_id', '=', 'qa.id')
            ->join('languages as l', 'qa.language_id', '=', 'l.id')
            ->select('or.*', 'l.name as language_name')
            ->where('or.student_id', $id)
            ->when($langId, function($query, $langId) {
                return $query->where('qa.language_id', $langId);
            })
            ->orderBy('or.created_at', 'desc')
            ->get();

        $compHistory = DB::table('exam_attempts as ea')
            ->join('languages as l', 'ea.language_id', '=', 'l.id')
            ->select('ea.*', 'l.name as language_name')
            ->where('ea.student_id', $id)
            ->when($langId, function($query, $langId) {
                return $query->where('ea.language_id', $langId);
            })
            ->orderBy('ea.created_at', 'desc')
            ->get();

        $modularHistory = DB::table('modular_exam_attempts')
            ->join('quiz_assignments', 'modular_exam_attempts.assignment_id', '=', 'quiz_assignments.id')
            ->join('languages', 'quiz_assignments.language_id', '=', 'languages.id')
            ->select(
                'modular_exam_attempts.id',
                'modular_exam_attempts.total_percentage as score',
                'modular_exam_attempts.completed_at as created_at',
                'languages.name as language_name'
            )
            ->where('modular_exam_attempts.student_id', $id)
            ->where('modular_exam_attempts.status', 'completed')
            ->when($langId, function($query, $langId) {
                return $query->where('quiz_assignments.language_id', $langId);
            })
            ->orderBy('modular_exam_attempts.completed_at', 'desc')
            ->get();

        return response()->json([
            'oral' => $oralHistory,
            'comp' => $compHistory,
            'modular' => $modularHistory
        ]);
    }

    /**
     * Exportar reporte individual de una evaluación oral específica.
     */
    public function exportIndividualPdf($id)
    {
        $result = DB::table('oral_results as or')
            ->join('users as u', 'or.student_id', '=', 'u.id')
            ->join('quiz_assignments as qa', 'or.quiz_assignment_id', '=', 'qa.id')
            ->join('languages as l', 'qa.language_id', '=', 'l.id') 
            ->leftJoin('students as s', 'u.id', '=', 's.user_id')
            ->leftJoin('careers as c', 's.career_id', '=', 'c.id')
            ->select(
                'or.*', 
                'u.name', 
                'u.lastname', 
                'c.name as career_name',
                'l.name as language_name', 
                's.semester',
                'qa.attended' 
            )
            ->where('or.id', $id)
            ->first();

        if (!$result) {
            return abort(404, 'El registro de evaluación no existe.');
        }

        if ($result->attended == 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se puede generar un reporte para un estudiante con inasistencia.'
            ], 403);
        }

        $verification = Verification::firstOrCreate(
            [
                'verifiable_id' => $result->id,
                'verifiable_type' => 'App\Models\OralResult'
            ],
            [
                'uuid' => (string) Str::uuid(),
                'type' => 'ORAL_EXAM',
            ]
        );

        $urlVerificacion = url("/verify/{$verification->uuid}");
        $qrCodeData = QrCode::format('svg')
            ->size(150)
            ->errorCorrection('H')
            ->generate($urlVerificacion);
        $qrcodeBase64 = base64_encode($qrCodeData);

        $teacherName = "Evaluador Oficial EmiSystem";
        if ($result->teacher_id) {
            $teacher = DB::table('users')->where('id', $result->teacher_id)->first();
            if ($teacher) {
                $teacherName = trim("{$teacher->lastname} {$teacher->name}");
            }
        }

        $data = [
            'student_full_name' => trim("{$result->lastname} {$result->name}"),
            'career_name'       => $result->career_name ?? 'N/A',
            'language_name'     => $result->language_name ?? 'N/A',
            'semester'          => $result->semester ?? 'N/A',
            'displayScore'      => number_format($result->final_score, 2),
            'displayLevel'      => $result->final_level ?? 'N/E',
            'isPassed'          => ($result->final_score >= 60), 
            'date'              => date('d/m/Y', strtotime($result->created_at)),
            'teacher_feedback'  => $result->teacher_feedback ?? 'Sin observaciones adicionales.',
            'teacher_full_name' => $teacherName,
            'detailed_scores'   => is_array($result->detailed_scores) 
                ? $result->detailed_scores 
                : json_decode($result->detailed_scores ?? '{}', true),
            'qrcode'            => $qrcodeBase64,
            'student_id'        => $result->student_id 
        ];

        try {
            $pdf = Pdf::loadView('pdf.individual_report', $data);
            return $pdf->stream("Planilla_{$result->lastname}_{$result->name}.pdf");
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error crítico al renderizar el PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar historial completo de un estudiante.
     */
    public function exportHistorialPdf($student_id)
    {
        $student = DB::table('users as u')
            ->leftJoin('students as s', 'u.id', '=', 's.user_id')
            ->leftJoin('careers as c', 's.career_id', '=', 'c.id')
            ->select(
                DB::raw("CONCAT(u.lastname, ' ', u.name) as full_name"),
                'c.name as career_name'
            )
            ->where('u.id', $student_id)
            ->first();

        if (!$student) return abort(404, 'Estudiante no encontrado');

        $oral = DB::table('oral_results')
            ->where('student_id', $student_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $comp = DB::table('exam_attempts')
            ->where('student_id', $student_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $modular = DB::table('modular_exam_attempts')
            ->where('student_id', $student_id)
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->get();

        $pdf = Pdf::loadView('pdf.historial', [
            'student' => $student,
            'oral'    => $oral,
            'comp'    => $comp,
            'modular' => $modular
        ]);

        return $pdf->stream('historial_' . $student_id . '.pdf');
    }

    /**
     * Exportar reporte general consolidado de todos los estudiantes con filtros de fecha
     * GET /api/v1/reports/general
     */
    public function exportGeneralReport(Request $request)
    {
        try {
            $since = $request->query('since');
            $until = $request->query('until');
            
            // Construir condiciones de fecha para filtrar estudiantes
            $dateCondition = "";
            $params = [];
            
            if ($since && $until) {
                $dateCondition = " AND (created_at BETWEEN ? AND ?)";
                $params = [$since, $until . ' 23:59:59'];
            } elseif ($since) {
                $dateCondition = " AND (created_at >= ?)";
                $params = [$since];
            } elseif ($until) {
                $dateCondition = " AND (created_at <= ?)";
                $params = [$until . ' 23:59:59'];
            }
            
            // Obtener SOLO estudiantes con actividad en el rango de fechas
            $students = DB::select("
                SELECT DISTINCT
                    u.id,
                    CONCAT(u.name, ' ', u.lastname) as full_name,
                    COALESCE(c.name, 'Sin Carrera') as career_name
                FROM users u
                LEFT JOIN students s ON u.id = s.user_id
                LEFT JOIN careers c ON s.career_id = c.id
                WHERE u.role = 'student'
                AND (
                    EXISTS (SELECT 1 FROM oral_results WHERE student_id = u.id $dateCondition)
                    OR EXISTS (SELECT 1 FROM exam_attempts WHERE student_id = u.id $dateCondition)
                    OR EXISTS (SELECT 1 FROM modular_exam_attempts WHERE student_id = u.id AND status = 'completed' $dateCondition)
                )
                ORDER BY u.name ASC
            ", $params);
            
            // Generar QR
            $verification = Verification::firstOrCreate(
                ['verifiable_id' => 1, 'verifiable_type' => 'App\Models\Report'],
                ['uuid' => (string) Str::uuid(), 'type' => 'GENERAL_REPORT']
            );
            
            $qrCode = base64_encode(
                QrCode::format('svg')
                    ->size(80)
                    ->errorCorrection('H')
                    ->generate(url("/verify/{$verification->uuid}"))
            );
            
            // Construir HTML
            $html = '<!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>Consolidado General - EmiSystem</title>
                <style>
                    @page { margin: 3cm; size: letter portrait; }
                    body { font-family: "Helvetica", Arial, sans-serif; color: #1e293b; font-size: 9px; margin: 0; }
                    .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; }
                    table { width: 100%; border-collapse: collapse; }
                    th { text-align: center; text-transform: uppercase; font-size: 7px; color: #64748b; padding: 10px 5px; border-bottom: 2px solid #f1f5f9; }
                    td { padding: 15px 5px; border-bottom: 1px solid #f8fafc; vertical-align: middle; text-align: center; }
                    .student-name { font-size: 10px; font-weight: bold; color: #000; display: block; text-align: left; }
                    .student-career { font-size: 7px; color: #4f46e5; text-transform: uppercase; font-weight: bold; display: block; text-align: left; margin-top: 4px; }
                    .lang-tag { display: inline-block; padding: 3px 8px; border-radius: 12px; background-color: #eff6ff; color: #2563eb; font-size: 7px; font-weight: bold; border: 1px solid #dbeafe; text-transform: uppercase; }
                    .attempt-group { margin-bottom: 8px; display: block; }
                    .attempt-group:last-child { margin-bottom: 0; }
                    .score-row { font-size: 9px; font-weight: bold; display: block; }
                    .date-row { font-size: 6px; color: #94a3b8; display: block; margin-top: 2px; }
                    .text-emerald { color: #059669; }
                    .text-rose { color: #e11d48; }
                    .status-cert { color: #059669; font-weight: bold; font-size: 8px; text-transform: uppercase; }
                    .status-pend { color: #94a3b8; font-weight: bold; font-size: 8px; text-transform: uppercase; }
                </style>
            </head>
            <body>
                <div class="header">
                    <table style="width: 100%; border: none; border-collapse: collapse;">
                        <tr>
                            <td style="text-align: left; border: none; vertical-align: middle;">
                                <h2 style="margin:0; font-size: 14px;">Consolidado Histórico de Calificaciones</h2>
                                <p style="margin:5px 0 0 0; color: #64748b;">EmiSystem — Reporte de Rendimiento Académico</p>
                                <p style="font-size: 8px; color: #94a3b8; margin-top: 8px;">
                                    Generado por: <strong>' . auth()->user()->name . '</strong> | Fecha: ' . now()->format('d/m/Y H:i') . '
                                </p>';
            
            if ($since || $until) {
                $html .= '<p style="font-size: 8px; color: #4f46e5; margin-top: 5px;"><strong>Filtro por fecha:</strong> ';
                if ($since) $html .= 'Desde: ' . date('d/m/Y', strtotime($since));
                if ($until) $html .= ' Hasta: ' . date('d/m/Y', strtotime($until));
                $html .= '</p>';
            }
            
            $html .= '</td>
                            <td style="text-align: right; border: none; vertical-align: middle; width: 110px;">
                                <div style="text-align: center;">
                                    <img src="data:image/svg+xml;base64,' . $qrCode . '" style="width: 70px; height: 70px;">
                                    <p style="font-size: 5px; color: #94a3b8; margin-top: 2px; text-transform: uppercase;">Validación Oficial</p>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>';
            
            if (empty($students)) {
                $html .= '<p style="text-align: center; color: #e11d48; margin-top: 50px;">No hay registros en el rango de fechas seleccionado.</p>';
            } else {
                $html .= '<table>
                    <thead>
                        <tr>
                            <th width="22%" style="text-align: left;">Información Estudiante</th>
                            <th width="8%">Idioma</th>
                            <th width="20%">Saber Oral (Nivel - Nota)</th>
                            <th width="20%">Saber Escrito (CompTest)</th>
                            <th width="20%">Examen Modular</th>
                            <th width="10%">Estado</th>
                        </tr>
                    </thead>
                    <tbody>';
                
                foreach ($students as $student) {
                    // Obtener idioma
                    $lang = DB::selectOne("
                        SELECT l.name 
                        FROM languages l
                        JOIN quiz_assignments qa ON qa.language_id = l.id
                        WHERE qa.student_id = ?
                        LIMIT 1
                    ", [$student->id]);
                    
                    $languageName = $lang->name ?? 'N/A';
                    
                    // Obtener oral (con filtro de fecha)
                    $oralParams = [$student->id];
                    $oralQuery = "SELECT final_level, final_score, DATE_FORMAT(created_at, '%d/%m/%y') as oral_date
                                  FROM oral_results 
                                  WHERE student_id = ?";
                    if ($since) {
                        $oralQuery .= " AND created_at >= ?";
                        $oralParams[] = $since;
                    }
                    if ($until) {
                        $oralQuery .= " AND created_at <= ?";
                        $oralParams[] = $until . ' 23:59:59';
                    }
                    $oralQuery .= " ORDER BY created_at DESC LIMIT 1";
                    $oral = DB::selectOne($oralQuery, $oralParams);
                    
                    // Obtener comp (con filtro de fecha)
                    $compParams = [$student->id];
                    $compQuery = "SELECT score, DATE_FORMAT(created_at, '%d/%m/%y') as comp_date
                                  FROM exam_attempts 
                                  WHERE student_id = ?";
                    if ($since) {
                        $compQuery .= " AND created_at >= ?";
                        $compParams[] = $since;
                    }
                    if ($until) {
                        $compQuery .= " AND created_at <= ?";
                        $compParams[] = $until . ' 23:59:59';
                    }
                    $compQuery .= " ORDER BY created_at DESC LIMIT 1";
                    $comp = DB::selectOne($compQuery, $compParams);
                    
                    // Obtener modular (con filtro de fecha)
                    $modularParams = [$student->id];
                    $modularQuery = "SELECT total_percentage, DATE_FORMAT(completed_at, '%d/%m/%y') as modular_date
                                     FROM modular_exam_attempts 
                                     WHERE student_id = ? AND status = 'completed'";
                    if ($since) {
                        $modularQuery .= " AND completed_at >= ?";
                        $modularParams[] = $since;
                    }
                    if ($until) {
                        $modularQuery .= " AND completed_at <= ?";
                        $modularParams[] = $until . ' 23:59:59';
                    }
                    $modularQuery .= " ORDER BY completed_at DESC LIMIT 1";
                    $modular = DB::selectOne($modularQuery, $modularParams);
                    
                    // Oral HTML
                    $oralHtml = '';
                    if ($oral) {
                        $passed = ($oral->final_score >= 60 && in_array($oral->final_level, ['B2', 'C1', 'C2']));
                        $colorClass = $passed ? 'text-emerald' : 'text-rose';
                        $oralHtml = '<div class="attempt-group">
                                        <span class="score-row ' . $colorClass . '">' . $oral->final_level . ' - ' . number_format($oral->final_score, 2) . '</span>
                                        <span class="date-row">' . $oral->oral_date . '</span>
                                    </div>';
                    } else {
                        $oralHtml = '<span class="date-row">Sin registros</span>';
                    }
                    
                    // Comp HTML
                    $compHtml = '';
                    $hasCompPass = false;
                    if ($comp) {
                        $passedComp = $comp->score >= 60;
                        if ($passedComp) $hasCompPass = true;
                        $colorClass = $passedComp ? 'text-emerald' : 'text-rose';
                        $compHtml = '<div class="attempt-group">
                                        <span class="score-row ' . $colorClass . '">' . number_format($comp->score, 0) . '</span>
                                        <span class="date-row">' . $comp->comp_date . '</span>
                                    </div>';
                    } else {
                        $compHtml = '<span class="date-row">Sin registros</span>';
                    }
                    
                    // Modular HTML
                    $modularHtml = '';
                    $hasModularPass = false;
                    if ($modular) {
                        $passedMod = $modular->total_percentage >= 60;
                        if ($passedMod) $hasModularPass = true;
                        $colorClass = $passedMod ? 'text-emerald' : 'text-rose';
                        $modularHtml = '<div class="attempt-group">
                                            <span class="score-row ' . $colorClass . '">' . number_format($modular->total_percentage, 0) . '%</span>
                                            <span class="date-row">' . $modular->modular_date . '</span>
                                        </div>';
                    } else {
                        $modularHtml = '<span class="date-row">Sin registros</span>';
                    }
                    
                    // Estado
                    $hasOralPass = ($oral && $oral->final_score >= 60 && in_array($oral->final_level, ['B2', 'C1', 'C2']));
                    $statusHtml = ($hasOralPass && ($hasCompPass || $hasModularPass)) 
                        ? '<span class="status-cert">CERTIFICABLE</span>'
                        : '<span class="status-pend">PENDIENTE</span>';
                    
                    $html .= '<tr>
                        <td style="text-align: left;">
                            <span class="student-name">' . htmlspecialchars($student->full_name) . '</span>
                            <span class="student-career">' . htmlspecialchars($student->career_name) . '</span>
                        </td>
                        <td><span class="lang-tag">' . htmlspecialchars($languageName) . '</span></td>
                        <td>' . $oralHtml . '</td>
                        <td>' . $compHtml . '</td>
                        <td>' . $modularHtml . '</td>
                        <td>' . $statusHtml . '</td>
                    </tr>';
                }
                
                $html .= '</tbody>
                </table>';
            }
            
            $html .= '</body>
            </html>';
            
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('letter', 'portrait');
            
            return $pdf->download("consolidado_general_" . now()->format('Ymd_His') . ".pdf");
            
        } catch (\Exception $e) {
            \Log::error('Error en exportGeneralReport: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}