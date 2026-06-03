<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Exception;

class ReportController extends Controller
{
    /**
     * Genera un reporte PDF de todos los estudiantes inscritos.
     */
    public function exportStudents()
    {
        try {
            // 1. Obtener datos con relaciones para evitar el problema N+1
            $students = User::where('role', 'student')
                ->with(['student.career']) // Trae el perfil y la carrera asociada
                ->orderBy('lastname', 'asc')
                ->get();

            // 2. Verificar si hay registros
            if ($students->isEmpty()) {
                return response()->json(['message' => 'No hay estudiantes registrados para generar el reporte'], 404);
            }

            // 3. Preparar datos adicionales para la vista
            $data = [
                'title' => 'Reporte General de Estudiantes',
                'date' => date('d/m/Y H:i:s'),
                'students' => $students,
                'total' => $students->count()
            ];

            // 4. Cargar la vista y generar el PDF
            // Nota: La vista debe existir en resources/views/reports/students.blade.php
            $pdf = Pdf::loadView('reports.students', $data);

            // 5. Configuración opcional (A4, Vertical)
            $pdf->setPaper('a4', 'portrait');

            // 6. Retornar el flujo del archivo para descarga
            return $pdf->download('Reporte_Estudiantes_' . date('Ymd') . '.pdf');

        } catch (Exception $e) {
            // En caso de error, devolvemos JSON ya que es una API
            return response()->json([
                'error' => 'Error al generar el PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}