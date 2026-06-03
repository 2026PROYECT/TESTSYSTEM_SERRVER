<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\Verification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ExamReportController extends Controller
{
    /**
     * LISTA DE EXÁMENES COMPUTARIZADOS (ADMIN)
     * GET /api/v1/admin/comp-reports
     */
    public function indexComp()
    {
        try {
            $attempts = DB::table('exam_attempts')
                ->join('users', 'exam_attempts.student_id', '=', 'users.id')
                ->join('quizzes', 'exam_attempts.quiz_id', '=', 'quizzes.id')
                ->where('exam_attempts.status', 'completed')
                ->orderBy('exam_attempts.completed_at', 'desc')
                ->select(
                    'exam_attempts.id',
                    'exam_attempts.score',
                    'exam_attempts.correct_answers',
                    'exam_attempts.completed_at',
                    'exam_attempts.status',
                    DB::raw("CONCAT(users.name, ' ', users.lastname, ' ', COALESCE(users.surname, '')) as student_name"),
                    'quizzes.title as quiz_title'
                )
                ->get();

            $processed = $attempts->map(function ($attempt) {
                $stats = DB::table('attempt_questions')
                    ->join('question_banks', 'attempt_questions.question_id', '=', 'question_banks.id')
                    ->where('attempt_questions.exam_attempt_id', $attempt->id)
                    ->select(
                        'question_banks.area',
                        DB::raw('COUNT(*) as total'),
                        DB::raw('SUM(CASE WHEN attempt_questions.selected_option_id = question_banks.right_answer THEN 1 ELSE 0 END) as correct')
                    )
                    ->groupBy('question_banks.area')
                    ->get();

                $l_correct = 0;
                $l_total = 0;
                $r_correct = 0;
                $r_total = 0;

                foreach ($stats as $stat) {
                    $area = strtoupper(trim($stat->area));
                    if ($area === 'L') {
                        $l_correct = $stat->correct;
                        $l_total = $stat->total;
                    } else {
                        $r_correct = $stat->correct;
                        $r_total = $stat->total;
                    }
                }

                return [
                    'id' => $attempt->id,
                    'student_name' => $attempt->student_name,
                    'quiz_title' => $attempt->quiz_title,
                    'score' => $attempt->score,
                    'correct_answers' => $attempt->correct_answers,
                    'status' => $attempt->status,
                    'completed_at' => $attempt->completed_at,
                    'stats_l' => $l_correct . ' / ' . $l_total,
                    'stats_r' => $r_correct . ' / ' . $r_total,
                ];
            });

            return response()->json($processed);

        } catch (\Exception $e) {
            Log::error('Error en indexComp: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * EXPORTAR PDF GENERAL COMP (ADMIN)
     * GET /api/v1/admin/comp-reports/export-pdf
     */
    public function exportPdfGeneralComp(Request $request)
    {
        try {
            Log::info("Admin exportando PDF general COMP");

            $query = DB::table('exam_attempts')
                ->join('users', 'exam_attempts.student_id', '=', 'users.id')
                ->join('quizzes', 'exam_attempts.quiz_id', '=', 'quizzes.id')
                ->where('exam_attempts.status', 'completed');

            $filter = $request->filter;
            if ($filter === 'passed') {
                $query->where('exam_attempts.score', '>=', 60);
                $title = "REPORTE DE EXÁMENES COMPUTARIZADOS APROBADOS";
            } elseif ($filter === 'failed') {
                $query->where('exam_attempts.score', '<', 60);
                $title = "REPORTE DE EXÁMENES COMPUTARIZADOS REPROBADOS";
            } else {
                $title = "CONSOLIDADO GENERAL DE EXÁMENES COMPUTARIZADOS";
            }

            $attempts = $query->orderBy('exam_attempts.completed_at', 'desc')
                ->select(
                    'exam_attempts.id',
                    'exam_attempts.student_id',
                    'exam_attempts.score',
                    'exam_attempts.completed_at',
                    DB::raw("CONCAT(users.name, ' ', users.lastname, ' ', COALESCE(users.surname, '')) as student_name"),
                    'quizzes.title as quiz_title'
                )
                ->get();

            $studentsList = [];
            foreach ($attempts as $attempt) {
                $studentData = DB::table('students')
                    ->where('user_id', $attempt->student_id)
                    ->first();

                $studentsList[] = [
                    'full_name' => trim($attempt->student_name),
                    'id_number' => $studentData->id_number ?? 'N/D',
                    'quiz_title' => $attempt->quiz_title,
                    'score' => (int)$attempt->score,
                    'date' => Carbon::parse($attempt->completed_at)->format('d/m/Y')
                ];
            }

            $totalStudents = count($attempts);
            $approvedCount = $attempts->where('score', '>=', 60)->count();
            $failedCount = $attempts->where('score', '<', 60)->count();

            $verification = Verification::create([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'verifiable_type' => null,
                'verifiable_id' => null,
                'type' => 'COMP_GENERAL_REPORT',
                'metadata' => json_encode([
                    'students' => $studentsList,
                    'filter' => $filter ?: 'all',
                    'total_students' => $totalStudents,
                    'approved_count' => $approvedCount,
                    'failed_count' => $failedCount,
                    'generated_by' => auth()->user()->name ?? 'Sistema'
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

            $html = $this->generateCompReportHTML($title, $attempts, $qrCode, $verification);

            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('letter', 'portrait');

            return $pdf->download("reporte_comp_" . now()->format('Ymd_His') . ".pdf");

        } catch (\Exception $e) {
            Log::error('Error PDF General COMP: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * EXPORTAR PDF INDIVIDUAL COMP (ADMIN)
     * GET /api/v1/admin/comp-reports/{id}/pdf
     */
    public function exportPdfIndividualComp($id)
    {
        try {
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

            $verification = Verification::firstOrCreate(
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

            $fileName = str_replace(' ', '_', $fullName ?: $attempt->student->name ?? 'estudiante');
            return $pdf->stream("Planilla_COMP_{$fileName}.pdf");

        } catch (\Exception $e) {
            Log::error("Error PDF Individual COMP: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * GENERAR HTML PARA REPORTE GENERAL COMP
     */
    private function generateCompReportHTML($title, $attempts, $qrCode, $verification = null)
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
            $html .= '<p class="verification-code"> Código: ' . strtoupper(substr($verification->uuid, 0, 8)) . '</p>';
        }

        $html .= '
            </div>
            <table>
                <thead>
                    <tr>
                        <th class="text-left">Estudiante</th>
                        <th class="text-left">Carnet</th>
                        <th class="text-left">Examen</th>
                        <th>Puntaje</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($attempts as $attempt) {
            $studentData = DB::table('students')
                ->where('user_id', $attempt->student_id)
                ->first();
            $idNumber = $studentData->id_number ?? 'N/D';

            $status = $attempt->score >= 60 ? 'APROBADO' : 'REPROBADO';
            $statusClass = $attempt->score >= 60 ? 'approved' : 'failed';

            $html .= '
                <tr>
                    <td class="text-left">' . ($attempt->student_name ?? 'N/A') . '</td>
                    <td class="text-left">' . $idNumber . '</td>
                    <td class="text-left">' . ($attempt->quiz_title ?? 'N/A') . '</td>
                    <td class="text-center">' . number_format($attempt->score, 1) . '%</td>
                    <td class="text-center">' . Carbon::parse($attempt->completed_at)->format('d/m/Y') . '</td>
                    <td class="text-center ' . $statusClass . '">' . $status . '</td>
                </tr>';
        }

        $html .= '
                </tbody>
            </table>
            <div class="footer">
                <p>Documento generado por EmiSystem - Reporte de Exámenes Computarizados</p>
                <p>Verifica la autenticidad escaneando el código QR</p>
            </div>
        </body>
        </html>';

        return $html;
    }
}