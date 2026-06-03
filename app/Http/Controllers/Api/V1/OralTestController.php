<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OralResult;
use App\Models\Language;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class OralTestController extends Controller
{
    /**
     * Obtener historial de exámenes orales del estudiante
     */
    public function getOralHistory(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Consulta directa a la tabla oral_results
            $results = DB::table('oral_results')
                ->join('quiz_assignments', 'oral_results.quiz_assignment_id', '=', 'quiz_assignments.id')
                ->join('languages', 'quiz_assignments.language_id', '=', 'languages.id')
                ->where('oral_results.student_id', $user->id)
                ->orderBy('oral_results.created_at', 'desc')
                ->select(
                    'oral_results.id',
                    'oral_results.quiz_assignment_id',
                    'oral_results.final_level',
                    'oral_results.final_score',
                    'oral_results.teacher_feedback',
                    'oral_results.created_at',
                    'languages.name as language_name',
                    'quiz_assignments.attended',
                    'quiz_assignments.passed'
                )
                ->get();
            
            $attempts = [];
            
            foreach ($results as $result) {
                $attempts[] = [
                    'id' => $result->quiz_assignment_id,
                    'oral_result_id' => $result->id,
                    'language_name' => $result->language_name,
                    'date' => Carbon::parse($result->created_at)->format('d/m/Y H:i'),
                    'level' => $result->final_level ?? 'No determinado',
                    'score' => $result->final_score ? number_format($result->final_score, 1) : null,
                    'attended' => $result->attended == 1,
                    'passed' => $result->passed == 1,
                    'feedback' => $result->teacher_feedback
                ];
            }
            
            return response()->json([
                'success' => true,
                'attempts' => $attempts
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error en getOralHistory: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'attempts' => [],
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function translateStatus($status)
    {
        $labels = [
            'completed' => 'Presentado',
            'missed' => 'No Presentado',
            'failed' => 'Reprobado'
        ];
        return $labels[$status] ?? $status;
    }

    public function show(Request $request, $id)
    {
        try {
            $result = OralResult::where('student_id', auth()->id())
                ->with(['teacher:id,name,lastname'])
                ->findOrFail($id);

            $languageId = $request->user()->active_lang_id ?? $result->language_id;
            $language = Language::find($languageId);

            return response()->json([
                'status' => 'success',
                'data'   => $result,
                'teacher_name'  => $result->teacher ? $result->teacher->name . ' ' . $result->teacher->lastname : 'Docente Autorizado',
                'language_name' => $language ? $language->name : 'Departamento'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadCertificate($id)
    {
        try {
            $result = OralResult::with([
                'student.studentProfile.career', 
                'teacher'
            ])->findOrFail($id);
            
            if (!$result->student || !$result->student->studentProfile) {
                throw new \Exception("El perfil académico del estudiante no fue encontrado.");
            }

            $language = Language::find($result->language_id);
            $language_name = $language ? $language->name : 'Idioma no especificado';

            $verification = \App\Models\Verification::firstOrCreate(
                [
                    'verifiable_id' => $result->id,
                    'verifiable_type' => 'App\Models\OralResult'
                ],
                [
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'type' => 'ORAL_EXAM',
                ]
            );

            $urlVerificacion = url("/verify/{$verification->uuid}");

            $qrCode = base64_encode(
                QrCode::format('svg')
                    ->size(150)
                    ->errorCorrection('H')
                    ->margin(1)
                    ->generate($urlVerificacion)
            );

            $levelsSummary = [];
            foreach ($result->detailed_scores as $level => $data) {
                $scores = array_filter($data, fn($v) => is_numeric($v));
                $avg = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
                
                $levelsSummary[] = [
                    'level' => $level,
                    'average' => round($avg, 1),
                    'completed' => $data['completed'] ?? false,
                    'is_final' => $level === $result->final_level
                ];
            }

            return Pdf::loadView('pdf.oral_certificate', [
                'result'          => $result,
                'qrCode'          => $qrCode,
                'language_name'   => $language_name,
                'levelsSummary'   => $levelsSummary,
                'currentDetailed' => $result->detailed_scores[$result->final_level] ?? []
            ])->stream("EMI_Reporte_{$result->student_id}.pdf");

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function verifyPublicly($id)
    {
        $result = OralResult::find($id);

        if (!$result) {
            return response()->json(['error' => 'Certificado no encontrado en la base de datos'], 404);
        }

        try {
            $result->load(['teacher', 'student.studentProfile.career']);
            return view('public.verify_oral', compact('result'));
        } catch (\Exception $e) {
            return "Error de Relaciones: " . $e->getMessage();
        }
    }
}