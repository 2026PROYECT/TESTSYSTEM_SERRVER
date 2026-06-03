<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\Language;
use App\Models\QuizAssignment;
use App\Models\Verification;
use Carbon\Carbon;

class AdminModularReportController extends Controller
{
    public function index()
    {
        try {
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
                    'users.id as student_id',
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
                    'listening_score' => $results['by_type']['listening']['score'] ?? 0,
                    'listening_total' => $results['by_type']['listening']['total'] ?? 0,
                    'listening_percentage' => $results['by_type']['listening']['percentage'] ?? 0,
                    'reading_score' => $results['by_type']['reading']['score'] ?? 0,
                    'reading_total' => $results['by_type']['reading']['total'] ?? 0,
                    'reading_percentage' => $results['by_type']['reading']['percentage'] ?? 0,
                    'results_data' => $results
                ];
            });

            return response()->json($processed);

        } catch (\Exception $e) {
            \Log::error('Error en index modular reports: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function exportPdfGeneral(Request $request)
    {
        try {
            $query = DB::table('modular_exam_attempts')
                ->join('users', 'modular_exam_attempts.student_id', '=', 'users.id')
                ->join('quiz_assignments', 'modular_exam_attempts.assignment_id', '=', 'quiz_assignments.id')
                ->join('languages', 'quiz_assignments.language_id', '=', 'languages.id')
                ->where('modular_exam_attempts.status', 'completed');

            if ($request->filter === 'passed') {
                $query->where('modular_exam_attempts.total_percentage', '>=', 60);
                $title = "REPORTE DE EXÁMENES MODULARES APROBADOS";
            } elseif ($request->filter === 'failed') {
                $query->where('modular_exam_attempts.total_percentage', '<', 60);
                $title = "REPORTE DE EXÁMENES MODULARES REPROBADOS";
            } else {
                $title = "CONSOLIDADO GENERAL DE EXÁMENES MODULARES";
            }

            $attempts = $query->orderBy('modular_exam_attempts.completed_at', 'desc')
                ->select(
                    'modular_exam_attempts.id',
                    'modular_exam_attempts.total_percentage',
                    'modular_exam_attempts.completed_at',
                    DB::raw("CONCAT(users.name, ' ', users.lastname, ' ', users.surname) as student_name"),
                    'languages.name as language_name'
                )
                ->get();

            $qrCode = base64_encode(
                QrCode::format('svg')
                    ->size(80)
                    ->errorCorrection('H')
                    ->generate(url('/'))
            );

            $html = $this->generateGeneralReportHTML($title, $attempts, $qrCode);
            
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('letter', 'portrait');
            
            return $pdf->download("reporte_modular_" . now()->format('Ymd_His') . ".pdf");

        } catch (\Exception $e) {
            \Log::error('Error PDF General Modular: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function exportPdfIndividual($id)
    {
        try {
            $attempt = DB::table('modular_exam_attempts')
                ->where('id', $id)
                ->first();

            if (!$attempt) {
                return response()->json(['error' => 'Resultado no encontrado'], 404);
            }

            $results = json_decode($attempt->results_data, true);
            
            $student = DB::table('users')->where('id', $attempt->student_id)->first();
            $assignment = QuizAssignment::find($attempt->assignment_id);
            $language = Language::find($assignment->language_id);
            
            $career = DB::table('student_profiles')
                ->leftJoin('careers', 'student_profiles.career_id', '=', 'careers.id')
                ->where('student_profiles.user_id', $attempt->student_id)
                ->select('careers.name as career_name')
                ->first();

            $verification = Verification::firstOrCreate(
                [
                    'verifiable_id' => $attempt->id,
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

            $evaluador = auth()->user();

            $data = [
                'student_name' => trim(($student->name ?? '') . ' ' . ($student->lastname ?? '') . ' ' . ($student->surname ?? '')),
                'student_email' => $student->email ?? '',
                'career' => $career->career_name ?? 'NO ASIGNADA',
                'language_name' => $language->name ?? 'No especificado',
                'date' => Carbon::parse($attempt->completed_at)->format('d \d\e F \d\e Y'),
                'total_percentage' => $results['total_percentage'],
                'total_score' => $results['total_score'],
                'total_points' => $results['total_points'],
                'levels' => $results['by_level'] ?? [],
                'by_type' => $results['by_type'] ?? [],
                'details' => $results['details'] ?? [],
                'passed' => $results['total_percentage'] >= 60,
                'qrCode' => $qrCode,
                'teacher_name' => ($evaluador->name ?? '') . ' ' . ($evaluador->lastname ?? '')
            ];

            $pdf = Pdf::loadView('pdf.admin_modular_results', $data);
            $pdf->setPaper('a4', 'portrait');
            
            $fileName = str_replace(' ', '_', $data['student_name']);
            return $pdf->download("Reporte_Modular_{$fileName}.pdf");

        } catch (\Exception $e) {
            \Log::error('Error PDF Individual Modular: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function generateGeneralReportHTML($title, $attempts, $qrCode)
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
                <p>ID de verificación: ' . uniqid() . '</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
}