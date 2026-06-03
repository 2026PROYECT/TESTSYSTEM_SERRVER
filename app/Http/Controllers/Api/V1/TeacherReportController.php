<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class TeacherReportController extends Controller
{
    /**
     * Lista de exámenes completados.
     * Copiado exactamente de la lógica funcional del TeacherController.
     */
   public function getCompletedExams(Request $request)
{
    // Captura el ID desde el header que configuramos en Vue
    $langId = $request->header('X-Language-Id') ?? $request->query('language_id');

    $query = DB::table('oral_results')
        ->join('users', 'oral_results.student_id', '=', 'users.id')
        ->join('quiz_assignments', 'oral_results.quiz_assignment_id', '=', 'quiz_assignments.id')
        ->join('languages', 'quiz_assignments.language_id', '=', 'languages.id')
        ->select(
            'oral_results.id as result_id', 
            'oral_results.final_level',
            'oral_results.final_score',
            'oral_results.created_at',
            'users.name', 
            'users.lastname',
            'users.picture',
            'quiz_assignments.attended',
            'languages.name as language_name'
        );

    // FILTRO DE IDIOMA: Esto es lo que evita que se "muestre todo"
    if ($langId) {
        $query->where('quiz_assignments.language_id', $langId);
    }

    if (!$request->is('*admin*')) {
        $query->where('oral_results.teacher_id', auth()->id());
    }

    return response()->json($query->orderBy('oral_results.created_at', 'desc')->get());
}
    /**
     * Reporte PDF General.
     */
  
public function downloadGeneralReport(Request $request)
{
    try {
        $user = $request->user();
        
        // 1. Iniciamos la consulta base con todos los JOINS necesarios
        $query = DB::table('oral_results')
            ->join('users', 'oral_results.student_id', '=', 'users.id')
            ->join('languages', 'oral_results.language_id', '=', 'languages.id')
            ->join('quiz_assignments', 'oral_results.quiz_assignment_id', '=', 'quiz_assignments.id')
            ->select(
                'oral_results.*',
                'users.name',
                'users.lastname',
                'languages.name as language_name',
                'quiz_assignments.attended'
            );

        // 2. LÓGICA DE FILTRADO INTELIGENTE
        if ($request->is('*admin*')) {
            // Si es ADMIN: No aplicamos filtros de idioma ni de profesor. 
            // Sacamos el historial COMPLETO de la institución.
        } else {
            // Si es TEACHER: Solo sus alumnos y del idioma que tiene activo en su sesión.
            $query->where('oral_results.teacher_id', $user->id);
            
            $langId = $request->header('X-Language-Id') ?? $request->query('language_id');
            if ($langId) {
                $query->where('oral_results.language_id', $langId);
            }
        }

        $results = $query->orderBy('oral_results.created_at', 'desc')->get();

        $data = [
            'results' => $results,
            'date'    => date('d/m/Y H:i'),
            'teacher' => $request->is('*admin*') ? 'Administración Central' : $user->name . ' ' . $user->lastname,
            'is_admin'=> $request->is('*admin*')
        ];

        $pdf = Pdf::loadView('pdf.general_history_report', $data);
        
        // Si el reporte es muy grande (muchos idiomas), podrías usar horizontal
        if ($results->count() > 20) {
            $pdf->setPaper('letter', 'landscape');
        }

        return $pdf->stream("Reporte_General_Historico.pdf");

    } catch (\Exception $e) {
        return response()->json(['error' => 'Error: ' . $e->getMessage()], 500);
    }
}
public function showResult($id) // Este $id viene del {id} de la ruta
{
    $result = DB::table('oral_results')
        ->join('users', 'oral_results.student_id', '=', 'users.id')
        ->where('oral_results.id', $id) // <-- DEBE BUSCAR POR 'id', NO POR 'quiz_assignment_id'
        ->select(
            'oral_results.*',
            'users.name',
            'users.lastname',
            'users.picture'
        )
        ->first();

    if (!$result) {
        return response()->json(['message' => 'Resultado no encontrado'], 404);
    }

    // Esto asegura que solo decodifique si realmente es una cadena de texto JSON
if (is_string($result->detailed_scores)) {
    $result->detailed_scores = json_decode($result->detailed_scores);
}

    return response()->json($result);
}
}