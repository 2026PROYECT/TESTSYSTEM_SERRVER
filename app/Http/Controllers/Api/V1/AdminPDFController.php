<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StudentReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AdminPDFController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'admin']);
    }

    public function generateIndividualReport($id)
    {
        try {
            $user = Auth::user();
            if (!$user || $user->role !== 'admin') {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            $report = StudentReport::with(['student', 'teacher', 'language'])->findOrFail($id);

            $data = [
                'student_full_name' => $report->student->full_name ?? 'N/A',
                'career_name' => $report->student->career->name ?? 'N/A',
                'language_name' => $report->language->name ?? 'N/A',
                'semester' => $report->semester ?? 'N/A',
                'date' => $report->evaluation_date ? $report->evaluation_date->format('d/m/Y') : now()->format('d/m/Y'),
                'displayScore' => $report->final_score ?? 0,
                'displayLevel' => $report->level ?? 'A1',
                'teacher_feedback' => $report->feedback ?? 'Sin observaciones.',
                'teacher_full_name' => $report->teacher->full_name ?? 'N/A',
                'student_id' => $report->student_id ?? $report->id,
                'detailed_scores' => $report->detailed_scores ?? [],
                'qrcode' => $this->generateQRCode($report->id),
            ];

            $pdf = Pdf::loadView('pdf.individual_report', $data);
            $pdf->setPaper('a4', 'portrait');

            return $pdf->download("reporte_admin_{$id}.pdf");

        } catch (\Exception $e) {
            \Log::error('Error PDF Admin: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function previewIndividualReport($id)
    {
        try {
            $user = Auth::user();
            if (!$user || $user->role !== 'admin') {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            $report = StudentReport::with(['student', 'teacher', 'language'])->findOrFail($id);

            $data = [
                'student_full_name' => $report->student->full_name ?? 'N/A',
                'career_name' => $report->student->career->name ?? 'N/A',
                'language_name' => $report->language->name ?? 'N/A',
                'semester' => $report->semester ?? 'N/A',
                'date' => $report->evaluation_date ? $report->evaluation_date->format('d/m/Y') : now()->format('d/m/Y'),
                'displayScore' => $report->final_score ?? 0,
                'displayLevel' => $report->level ?? 'A1',
                'teacher_feedback' => $report->feedback ?? 'Sin observaciones.',
                'teacher_full_name' => $report->teacher->full_name ?? 'N/A',
                'student_id' => $report->student_id ?? $report->id,
                'detailed_scores' => $report->detailed_scores ?? [],
                'qrcode' => $this->generateQRCode($report->id),
            ];

            $pdf = Pdf::loadView('pdf.individual_report', $data);
            $pdf->setPaper('a4', 'portrait');

            return $pdf->stream("preview_admin_{$id}.pdf");

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function generateBulkReport(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user || $user->role !== 'admin') {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            $reports = StudentReport::with(['student', 'teacher', 'language'])
                ->orderBy('created_at', 'desc')
                ->get();

            $html = '<h1>Reporte General</h1><p>Total: ' . $reports->count() . '</p>';
            $pdf = Pdf::loadHTML($html);
            
            return $pdf->download('reporte_general_admin.pdf');

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function generateQRCode($reportId)
    {
        try {
            $qrCode = QrCode::format('svg')
                ->size(100)
                ->errorCorrection('M')
                ->generate(url('/'));
            
            return base64_encode($qrCode);
        } catch (\Exception $e) {
            return base64_encode('<svg width="100" height="100"></svg>');
        }
    }
}