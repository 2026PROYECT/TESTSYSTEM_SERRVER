<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QuizMaintenanceController extends Controller
{
    public function truncateByLanguage(Request $request)
    {
        $langId = $request->language_id;
        Schema::disableForeignKeyConstraints();
        
        // Eliminar datos relacionados al idioma específico
        DB::table('attempt_questions')->whereIn('question_id', function($q) use ($langId) {
            $q->select('id')->from('question_banks')->where('language_id', $langId);
        })->delete();

        DB::table('exam_attempts')->whereIn('quiz_id', function($q) use ($langId) {
            $q->select('id')->from('quizzes')->where('language_id', $langId);
        })->delete();

        DB::table('question_banks')->where('language_id', $langId)->delete();
        DB::table('quizzes')->where('language_id', $langId)->delete();
        
        Schema::enableForeignKeyConstraints();
        return response()->json(['message' => 'Success']);
    }

    public function uploadSql(Request $request)
{
    $request->validate([
        'file' => 'required|file',
        'language_id' => 'required'
    ]);

    try {
        // Leemos el contenido del archivo subido
        $sql = file_get_contents($request->file('file')->getRealPath());

        if (empty($sql)) {
            return response()->json(['message' => 'El archivo está vacío'], 422);
        }

        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        
        // Usamos unprepared para ejecutar múltiples sentencias SQL crudas
        \Illuminate\Support\Facades\DB::unprepared($sql);
        
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        return response()->json(['message' => 'Importación exitosa']);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
        // Esto te devolverá el error real en la consola de red para depurar
        return response()->json(['message' => 'Error de SQL: ' . $e->getMessage()], 500);
    }
}
    }
