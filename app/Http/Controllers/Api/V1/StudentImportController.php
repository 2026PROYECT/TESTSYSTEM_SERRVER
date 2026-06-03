<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\StudentsImport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class StudentImportController extends Controller
{
    public function import(Request $request)
    {
        // 1. Role validation
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized. Admin role required.'
            ], 403);
        }

        // 2. File validation
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new StudentsImport, $request->file('file'));

            return response()->json([
                'status'  => 'success',
                'message' => 'Estudiantes importados correctamente.'
            ], 200);

        } catch (ValidationException $e) {
            // Row-level validation errors
            $failures = $e->failures();
            $errors = [];

            foreach ($failures as $failure) {
                $errors[] = [
                    'row'       => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors'    => $failure->errors(),
                    'values'    => $failure->values(),
                ];
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Errores de validación en el archivo.',
                'errors'  => $errors
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error en el proceso: ' . $e->getMessage()
            ], 500);
        }
    }
}
