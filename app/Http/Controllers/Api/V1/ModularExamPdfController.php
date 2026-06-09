<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\QuizAssignment;
use App\Models\Language;
use App\Models\Verification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ModularExamPdfController extends Controller
{
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
            
            $career = DB::table('students')
                ->leftJoin('careers', 'students.career_id', '=', 'careers.id')
                ->where('students.user_id', $student->id)
                ->select('careers.name as career_name')
                ->first();
            
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
     * EXPORTAR PDF INDIVIDUAL (ADMIN)
     * GET /api/v1/admin/modular-reports/{id}/pdf
     * ============================================================
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
            
            $html = $this->generateIndividualReportHTML(
                $language, $studentName, $careerName, $completed_at,
                $l_score, $l_total, $r_score, $r_total, $total_percentage,
                $levels, $qrcode, $evaluador
            );
            
            $pdf = Pdf::loadHTML($html);
            return $pdf->download("Reporte_Modular_{$id}.pdf");
            
        } catch (\Exception $e) {
            Log::error('Error PDF Individual Modular: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * ============================================================
     * EXPORTAR LISTA GENERAL (ADMIN)
     * GET /api/v1/admin/modular-reports/export-pdf
     * ============================================================
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
            
            $studentsList = [];
            foreach ($attempts as $attempt) {
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
            
            $totalStudents = count($attempts);
            $approvedCount = $attempts->where('total_percentage', '>=', 60)->count();
            $failedCount = $attempts->where('total_percentage', '<', 60)->count();
            
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
                ], JSON_UNESCAPED_UNICODE),
                'scans_count' => 0
            ]);
            
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
     * ============================================================
     * GENERAR HTML PARA REPORTE INDIVIDUAL
     * ============================================================
     */
    private function generateIndividualReportHTML($language, $studentName, $careerName, $completed_at, $l_score, $l_total, $r_score, $r_total, $total_percentage, $levels, $qrcode, $evaluador)
    {
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
                    <td width="12%" class="bg-gray"><div align="right"><strong>NOTA FINAL</strong></div></td>
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
        
        return $html;
    }

    /**
     * ============================================================
     * GENERAR HTML PARA REPORTE GENERAL
     * ============================================================
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

    /**
     * ============================================================
     * OBTENER NIVEL DE TEXTO SEGÚN PORCENTAJE
     * ============================================================
     */
    private function getNivelText($percentage)
    {
        if ($percentage >= 90) return "C2 - MAESTRÍA";
        if ($percentage >= 80) return "C1 - AVANZADO DOMINIO OPERATIVO EFICAZ";
        if ($percentage >= 70) return "B2+ - INTERMEDIO-ALTO PLUS";
        if ($percentage >= 60) return "B2 - INTERMEDIO-ALTO";
        if ($percentage >= 50) return "B1 - INTERMEDIO";
        if ($percentage >= 40) return "A2 - BÁSICO";
        return "A1 - PRINCIPIANTE";
    }
}