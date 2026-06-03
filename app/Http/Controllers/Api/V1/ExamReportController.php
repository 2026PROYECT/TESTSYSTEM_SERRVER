<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ExamAttemptReport;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ExamReportController extends Controller
{
    public function index()
    {
        try {
            $attempts = ExamAttempt::with(['student', 'quiz', 'attemptQuestions.question'])
                ->latest()
                ->get()
                ->map(function ($attempt) {
                    
                    $stats = [
                        'l_correct' => 0, 'l_incorrect' => 0,
                        'r_correct' => 0, 'r_incorrect' => 0,
                    ];

                    foreach ($attempt->attemptQuestions as $aq) {
                        $question = $aq->question;
                        if (!$question) continue;

                        $selected = (int)$aq->selected_option_id;
                        $correct = (int)$question->right_answer;
                        
                        $isCorrect = ($selected !== 0 && $selected === $correct);

                        $area = strtoupper(trim($question->area));

                        if ($area === 'L') {
                            $isCorrect ? $stats['l_correct']++ : $stats['l_incorrect']++;
                        } else {
                            $isCorrect ? $stats['r_correct']++ : $stats['r_incorrect']++;
                        }
                    }

                    // NOMBRE COMPLETO
                    $fullName = trim(
                        ($attempt->student->name ?? '') . ' ' . 
                        ($attempt->student->lastname ?? '') . ' ' . 
                        ($attempt->student->surname ?? '')
                    );
                    $studentName = !empty($fullName) ? $fullName : ($attempt->student->name ?? 'N/A');

                    return [
                        'id'              => $attempt->id,
                        'student_name'    => $studentName,
                        'quiz_title'      => $attempt->quiz->title ?? 'N/A',
                        'score'           => $attempt->score ?? 0,
                        'correct_answers' => $attempt->correct_answers ?? 0,
                        'status'          => $attempt->status,
                        'completed_at'    => $attempt->completed_at,
                        'l_correct'       => $stats['l_correct'],
                        'l_incorrect'     => $stats['l_incorrect'],
                        'r_correct'       => $stats['r_correct'],
                        'r_incorrect'     => $stats['r_incorrect'],
                        'stats_l'         => $stats['l_correct'] . ' / ' . ($stats['l_correct'] + $stats['l_incorrect']),
                        'stats_r'         => $stats['r_correct'] . ' / ' . ($stats['r_correct'] + $stats['r_incorrect']),
                    ];
                });

            return response()->json($attempts);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error crítico en el reporte',
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    private function checkIfCorrect($attemptQuestion, $question)
    {
        if (!$attemptQuestion->selected_option_id) return false;
        return $attemptQuestion->is_correct ?? false;
    }

    public function show($id)
    {
        try {
            $attempt = ExamAttempt::with(['student', 'quiz'])->find($id);

            if (!$attempt) {
                return response()->json(['message' => 'Intento no encontrado'], 404);
            }

            // NOMBRE COMPLETO
            $fullName = trim(
                ($attempt->student->name ?? '') . ' ' . 
                ($attempt->student->lastname ?? '') . ' ' . 
                ($attempt->student->surname ?? '')
            );
            $studentName = !empty($fullName) ? $fullName : ($attempt->student->name ?? 'N/A');

            $response = $attempt->toArray();
            $response['student_name'] = $studentName;

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function exportPdfGeneral(Request $request)
    {
        try {
            // Consulta simple
            $query = ExamAttempt::with(['student.studentProfile.career', 'quiz'])
                ->where('status', 'completed');

            if ($request->filter === 'passed') {
                $query->where('score', '>=', 60);
                $title = "REPORTE DE ESTUDIANTES APROBADOS";
            } elseif ($request->filter === 'failed') {
                $query->where('score', '<', 60);
                $title = "REPORTE DE ESTUDIANTES REPROBADOS";
            } else {
                $title = "CONSOLIDADO GENERAL DE CALIFICACIONES";
            }

            $attempts = $query->latest()->get();

            // Generar QR
            $qrCode = base64_encode(
                QrCode::format('svg')
                    ->size(80)
                    ->errorCorrection('H')
                    ->generate(url('/'))
            );

            // Generar HTML directamente
            $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>' . $title . '</title>
                <style>
                    @page { 
                        margin: 2cm; 
                        size: letter portrait; 
                    }
                    body { 
                        font-family: Arial, sans-serif; 
                        font-size: 10px; 
                    }
                    h1 { 
                        text-align: center; 
                        color: #1e293b; 
                        font-size: 16px;
                    }
                    .header { 
                        text-align: center; 
                        margin-bottom: 20px;
                        position: relative;
                    }
                    .qr-container {
                        position: absolute;
                        top: 0;
                        right: 0;
                        text-align: center;
                    }
                    .qr-container img {
                        width: 65px;
                        height: 65px;
                    }
                    .qr-text {
                        font-size: 6px;
                        color: #64748b;
                        margin-top: 2px;
                    }
                    table { 
                        width: 100%; 
                        border-collapse: collapse; 
                        margin-top: 20px; 
                        font-size: 9px;
                    }
                    th { 
                        background: #f1f5f9; 
                        border: 1px solid #cbd5e1; 
                        padding: 8px 5px; 
                        text-align: center; 
                    }
                    td { 
                        border: 1px solid #cbd5e1; 
                        padding: 8px 5px; 
                    }
                    .text-left { 
                        text-align: left; 
                    }
                    .text-center { 
                        text-align: center; 
                    }
                    .approved { 
                        color: #059669; 
                        font-weight: bold; 
                    }
                    .failed { 
                        color: #e11d48; 
                        font-weight: bold; 
                    }
                    .footer { 
                        margin-top: 30px; 
                        text-align: center; 
                        font-size: 8px; 
                        color: #94a3b8; 
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
                    <p>Generado por: ' . (auth()->user()->name ?? 'Sistema') . ' | Fecha: ' . now()->format('d/m/Y H:i') . '</p>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th class="text-left">Estudiante</th>
                            <th class="text-left">Carrera</th>
                            <th class="text-left">Examen</th>
                            <th>Puntaje</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($attempts as $attempt) {
                // NOMBRE COMPLETO: name + lastname + surname
                $fullName = trim(
                    ($attempt->student->name ?? '') . ' ' . 
                    ($attempt->student->lastname ?? '') . ' ' . 
                    ($attempt->student->surname ?? '')
                );
                $studentName = !empty($fullName) ? $fullName : ($attempt->student->name ?? 'N/A');
                
                $careerName = $attempt->student->studentProfile->career->name ?? $attempt->student->career->name ?? 'N/A';
                $quizTitle = $attempt->quiz->title ?? 'N/A';
                $score = $attempt->score ?? 0;
                $date = $attempt->completed_at ? date('d/m/Y', strtotime($attempt->completed_at)) : 'N/A';
                $status = $score >= 60 ? 'APROBADO' : 'PENDIENTE';
                $statusClass = $score >= 60 ? 'approved' : 'failed';
                
                $html .= '
                    <tr>
                        <td class="text-left">' . $studentName . '</td>
                        <td class="text-left">' . $careerName . '</td>
                        <td class="text-left">' . $quizTitle . '</td>
                        <td class="text-center">' . number_format($score, 2) . '%</td>
                        <td class="text-center">' . $date . '</td>
                        <td class="text-center ' . $statusClass . '">' . $status . '</td>
                    </tr>';
            }
            
            $html .= '
                    </tbody>
                </table>
                
                <div class="footer">
                    <p>Documento generado automáticamente por EmiSystem - Este reporte es válido como constancia de rendimiento académico</p>
                    <p>ID de verificación: ' . uniqid() . '</p>
                </div>
            </body>
            </html>';
            
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('letter', 'portrait');
            
            return $pdf->stream("reporte_general_" . now()->format('Ymd_His') . ".pdf");
            
        } catch (\Exception $e) {
            \Log::error('Error PDF General: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function exportPdfIndividual($id)
    {
        try {
            // Cargar el intento con relaciones
            $attempt = ExamAttempt::with(['student.studentProfile.career', 'quiz', 'attemptQuestions.question'])
                        ->findOrFail($id);

            $l_correct = 0; 
            $l_total = 0;
            $r_correct = 0; 
            $r_total = 0;

            foreach ($attempt->attemptQuestions as $aq) {
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

            // Verificación UUID
            $verification = \App\Models\Verification::firstOrCreate(
                [
                    'verifiable_id' => $attempt->id,
                    'verifiable_type' => 'App\Models\ExamAttempt'
                ],
                [
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'type' => 'COMP_EXAM',
                ]
            );

            $urlVerificacion = url("/verify/{$verification->uuid}");

            $qrcode = base64_encode(
                QrCode::format('svg')
                    ->size(150)
                    ->errorCorrection('H')
                    ->generate($urlVerificacion)
            );

            $evaluador = auth()->user();

            // NOMBRE COMPLETO para la vista
            $fullName = trim(
                ($attempt->student->name ?? '') . ' ' . 
                ($attempt->student->lastname ?? '') . ' ' . 
                ($attempt->student->surname ?? '')
            );
            $attempt->student->full_name = !empty($fullName) ? $fullName : ($attempt->student->name ?? 'N/A');

            $pdf = Pdf::loadView('pdf.exam_computerized_detail', compact(
                'attempt', 'l_correct', 'l_total', 'r_correct', 'r_total', 'evaluador', 'qrcode'
            ));
            
            $pdf->setOptions([
                'defaultFont' => 'sans-serif',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
                'dpi' => 96,
            ]);

            // Usar nombre completo para el archivo
            $fileName = str_replace(' ', '_', $fullName ?: $attempt->student->name ?? 'estudiante');
            return $pdf->stream("Planilla_{$fileName}.pdf");

        } catch (\Exception $e) {
            \Log::error("Error PDF Individual: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'error' => 'Error al generar PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}