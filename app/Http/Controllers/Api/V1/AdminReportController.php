<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AdminReportController extends Controller
{
    public function exportGlobalInstitutionalReport(Request $request) 
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        try {
            $results = DB::table('oral_results')
                ->leftJoin('users as students', 'oral_results.student_id', '=', 'students.id')
                ->leftJoin('languages', 'oral_results.language_id', '=', 'languages.id')
                ->select(
                    'oral_results.*',
                    'students.name',
                    'students.lastname',
                    'languages.name as language_name'
                )
                ->orderBy('oral_results.created_at', 'desc')
                ->get();

            $data = [
                'results' => $results,
                'date'    => now()->format('d/m/Y H:i'),
            ];

            $pdf = Pdf::loadView('pdf.general_history_report', $data);
            $pdf->setPaper('letter', 'vertical');

            return $pdf->download("Reporte_Institucional_EMI.pdf");

        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function generateAcademicSummary(Request $request) 
    {
        try {
            ini_set('memory_limit', '256M');
            set_time_limit(60); 

            $user = auth()->user();
            $since = $request->query('since');
            $until = $request->query('until');
            $params = [];
            $dateFilter = "";

            if ($since && $until) {
                $dateFilter = " AND qa.created_at BETWEEN ? AND ? ";
                $params[] = $since . " 00:00:00";
                $params[] = $until . " 23:59:59";
            }

            // Consulta SQL para obtener estudiantes con sus datos
            $rawStudents = DB::select("
                SELECT 
                    u.id AS student_id,
                    u.name,
                    u.lastname,
                    s.id_number AS id_card,
                    CONCAT(u.lastname, ' ', u.name) AS full_name,
                    c.name AS career_name,
                    l.name AS language_name,
                    (SELECT GROUP_CONCAT(CONCAT(final_score, '|', final_level, '|', DATE_FORMAT(created_at, '%d/%m/%y')) ORDER BY created_at DESC) 
                     FROM oral_results WHERE student_id = u.id AND language_id = l.id) AS oral_raw,
                    (SELECT GROUP_CONCAT(CONCAT(score, '|', DATE_FORMAT(created_at, '%d/%m/%y')) ORDER BY created_at DESC) 
                     FROM exam_attempts WHERE student_id = u.id AND language_id = l.id) AS comp_raw
                FROM users u
                JOIN students s ON u.id = s.user_id
                LEFT JOIN careers c ON s.career_id = c.id
                JOIN quiz_assignments qa ON u.id = qa.student_id
                JOIN languages l ON qa.language_id = l.id
                WHERE 1=1 {$dateFilter} 
                GROUP BY u.id, l.id, u.name, u.lastname, s.id_number, c.name, l.name
                ORDER BY u.lastname ASC
            ", $params);

            // Si no hay resultados con filtro, obtener todos los estudiantes
            if (empty($rawStudents)) {
                $rawStudents = DB::select("
                    SELECT 
                        u.id AS student_id,
                        u.name,
                        u.lastname,
                        s.id_number AS id_card,
                        CONCAT(u.lastname, ' ', u.name) AS full_name,
                        c.name AS career_name,
                        l.name AS language_name,
                        (SELECT GROUP_CONCAT(CONCAT(final_score, '|', final_level, '|', DATE_FORMAT(created_at, '%d/%m/%y')) ORDER BY created_at DESC) 
                         FROM oral_results WHERE student_id = u.id AND language_id = l.id) AS oral_raw,
                        (SELECT GROUP_CONCAT(CONCAT(score, '|', DATE_FORMAT(created_at, '%d/%m/%y')) ORDER BY created_at DESC) 
                         FROM exam_attempts WHERE student_id = u.id AND language_id = l.id) AS comp_raw
                    FROM users u
                    JOIN students s ON u.id = s.user_id
                    LEFT JOIN careers c ON s.career_id = c.id
                    JOIN quiz_assignments qa ON u.id = qa.student_id
                    JOIN languages l ON qa.language_id = l.id
                    GROUP BY u.id, l.id, u.name, u.lastname, s.id_number, c.name, l.name
                    ORDER BY u.lastname ASC
                ");
            }
            
            // Guardar IDs de estudiantes para el QR
            $studentIds = array_column($rawStudents, 'student_id');
            
            $students = collect($rawStudents)->map(function($s) {
                return (object)[
                    'student_id'        => $s->student_id,
                    'full_name'         => $s->full_name,
                    'name'              => $s->name,
                    'lastname'          => $s->lastname,
                    'id_card'           => $s->id_card ?? 'N/D',
                    'career_name'       => $s->career_name ?? 'N/A',
                    'language_name'     => $s->language_name,
                    'oral_all_attempts' => $this->parsePdfAttempts($s->oral_raw ?? '', true),
                    'comp_all_attempts' => $this->parsePdfAttempts($s->comp_raw ?? '', false),
                ];
            });

            // UUID y QR
            $uuid = (string) Str::uuid();
            
            // Guardar metadata con los IDs y datos de estudiantes
            DB::table('verifications')->insert([
                'uuid' => $uuid,
                'verifiable_id' => $user->id,
                'verifiable_type' => 'App\Models\User',
                'type' => 'GLOBAL_REPORT',
                'metadata' => json_encode([
                    'since' => $since,
                    'until' => $until,
                    'student_ids' => $studentIds,
                    'student_count' => count($studentIds),
                    'students' => $students->map(function($s) {
                        return [
                            'full_name' => $s->full_name,
                            'id_card' => $s->id_card,
                            'name' => $s->name,
                            'lastname' => $s->lastname,
                            'career' => $s->career_name
                        ];
                    })->toArray()
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // URL correcta con /verify/
            $urlVue = url("/verify/{$uuid}"); 
            $qrRaw = QrCode::format('svg')->size(100)->margin(0)->generate($urlVue);

            $data = [
                'students'       => $students,
                'qrcode'         => base64_encode($qrRaw),
                'since'          => $since,
                'until'          => $until,
                'generatedBy'    => "{$user->lastname} {$user->name}",
                'generationDate' => now()->format('d/m/Y H:i:s')
            ];

            $pdf = Pdf::loadView('pdf.reporte_general', $data);
            $pdf->setPaper('letter', 'portrait');
            $pdf->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'tempDir' => storage_path('app/public'), 
                'fontDir' => storage_path('fonts'),
                'fontCache' => storage_path('fonts'),
                'logOutputDir' => storage_path('logs'),
            ]);

            return $pdf->stream('Reporte_General_EMI.pdf');

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error en servidor: ' . $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function exportOralGeneralPdf(Request $request) 
    {
        try {
            ini_set('memory_limit', '256M');
            $user = auth()->user();
            $since = $request->query('since');
            $until = $request->query('until');
            $params = [];
            $dateFilter = "";

            if ($since && $until) {
                $dateFilter = " AND o.created_at BETWEEN ? AND ? ";
                $params[] = $since . " 00:00:00";
                $params[] = $until . " 23:59:59";
            }

            // Consulta para obtener estudiantes con resultados orales
            $rawStudents = DB::select("
                SELECT 
                    u.id AS student_id,
                    u.name,
                    u.lastname,
                    s.id_number AS id_card,
                    CONCAT(u.lastname, ' ', u.name) AS full_name,
                    c.name AS career_name,
                    l.name AS language_name,
                    GROUP_CONCAT(CONCAT(o.final_score, '|', o.final_level, '|', DATE_FORMAT(o.created_at, '%d/%m/%y')) ORDER BY o.created_at DESC) AS oral_raw
                FROM oral_results o
                JOIN users u ON o.student_id = u.id
                JOIN students s ON u.id = s.user_id
                LEFT JOIN careers c ON s.career_id = c.id
                JOIN languages l ON o.language_id = l.id
                WHERE 1=1 {$dateFilter} 
                GROUP BY u.id, l.id, u.name, u.lastname, s.id_number, c.name, l.name
                ORDER BY u.lastname ASC
            ", $params);

            // Si no hay resultados con filtro, obtener todos
            if (empty($rawStudents)) {
                $rawStudents = DB::select("
                    SELECT 
                        u.id AS student_id,
                        u.name,
                        u.lastname,
                        s.id_number AS id_card,
                        CONCAT(u.lastname, ' ', u.name) AS full_name,
                        c.name AS career_name,
                        l.name AS language_name,
                        GROUP_CONCAT(CONCAT(o.final_score, '|', o.final_level, '|', DATE_FORMAT(o.created_at, '%d/%m/%y')) ORDER BY o.created_at DESC) AS oral_raw
                    FROM oral_results o
                    JOIN users u ON o.student_id = u.id
                    JOIN students s ON u.id = s.user_id
                    LEFT JOIN careers c ON s.career_id = c.id
                    JOIN languages l ON o.language_id = l.id
                    GROUP BY u.id, l.id, u.name, u.lastname, s.id_number, c.name, l.name
                    ORDER BY u.lastname ASC
                ");
            }

            // Guardar IDs de estudiantes
            $studentIds = array_column($rawStudents, 'student_id');

            $students = collect($rawStudents)->map(function($s) {
                return (object)[
                    'student_id'        => $s->student_id,
                    'full_name'         => $s->full_name,
                    'name'              => $s->name,
                    'lastname'          => $s->lastname,
                    'id_card'           => $s->id_card ?? 'N/D',
                    'career_name'       => $s->career_name ?? 'N/A',
                    'language_name'     => $s->language_name,
                    'oral_all_attempts' => $this->parsePdfAttempts($s->oral_raw ?? '', true),
                ];
            });

            // UUID y QR
            $uuid = (string) Str::uuid();
            
            // Guardar metadata con los IDs y datos de estudiantes
            DB::table('verifications')->insert([
                'uuid' => $uuid,
                'verifiable_id' => $user->id,
                'verifiable_type' => 'App\Models\User',
                'type' => 'ORAL_ONLY_REPORT',
                'metadata' => json_encode([
                    'since' => $since,
                    'until' => $until,
                    'student_ids' => $studentIds,
                    'student_count' => count($studentIds),
                    'students' => $students->map(function($s) {
                        return [
                            'full_name' => $s->full_name,
                            'id_card' => $s->id_card,
                            'name' => $s->name,
                            'lastname' => $s->lastname,
                            'career' => $s->career_name
                        ];
                    })->toArray()
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // URL correcta con /verify/
            $qrcode = base64_encode(QrCode::format('svg')->size(100)->margin(0)->generate(url("/verify/{$uuid}")));

            $data = [
                'students'       => $students,
                'qrcode'         => $qrcode,
                'since'          => $since,
                'until'          => $until,
                'generatedBy'    => "{$user->lastname} {$user->name}",
                'generationDate' => now()->format('d/m/Y H:i:s')
            ];

            return Pdf::loadView('pdf.reporte_oral_solo', $data)
                ->setPaper('letter', 'portrait')
                ->setOptions([
                    'tempDir' => storage_path('app/public'),
                    'fontDir' => storage_path('fonts'),
                    'isRemoteEnabled' => true
                ])->stream('Consolidado_Oral_EMI.pdf');

        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function parsePdfAttempts($raw, $isOral = false)
    {
        if (!$raw) return [];
        $attempts = explode(',', $raw);
        return array_map(function($item) use ($isOral) {
            $parts = explode('|', $item);
            if ($isOral) {
                return (object)[
                    'score' => $parts[0] ?? '0',
                    'level' => $parts[1] ?? 'N/A',
                    'date'  => $parts[2] ?? '--'
                ];
            }
            return (object)[
                'score' => $parts[0] ?? '0',
                'date'  => $parts[1] ?? '--'
            ];
        }, $attempts);
    }
}