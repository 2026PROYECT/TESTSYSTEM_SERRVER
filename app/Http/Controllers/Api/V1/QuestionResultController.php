<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuestionResultController extends Controller
{
    use ApiResponse;

    /**
     * Obtener el detalle de UN resultado específico
     */
    public function getResults(Request $request, $id)
    {
        try {
            $user = $request->user();

            // 1. Buscamos el intento en exam_attempts usando student_id
            $attempt = DB::table('exam_attempts')
                ->where('id', $id)
                ->where('student_id', $user->id) 
                ->first();

            if (!$attempt) {
                return $this->ResponseError(null, 'No se encontró el resultado del examen.');
            }

            // 2. Traemos las preguntas usando los nombres reales de tu tabla (right_answer, question1)
            $details = DB::table('attempt_questions')
                ->join('question_banks', 'attempt_questions.question_id', '=', 'question_banks.id')
                ->where('attempt_questions.exam_attempt_id', $id)
                ->select(
                    'question_banks.question1 as prompt',
                    'question_banks.option_a',
                    'question_banks.option_b',
                    'question_banks.option_c',
                    'question_banks.option_d',
                    'question_banks.right_answer as correct_option', // Renombramos para el Frontend
                    'attempt_questions.selected_option_id'
                )
                ->get();

            return $this->ResponseSuccess([
                'score'      => $attempt->score,
                'percentage' => $attempt->correct_answers * 10, // Si son 10 preguntas
                'status'     => $attempt->status,
                'details'    => $details
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener la lista de TODOS los resultados del estudiante logueado
     */
    public function getAllResults(Request $request)
    {
        try {
            $user = $request->user();

            $history = DB::table('exam_attempts')
                ->join('quizzes', 'exam_attempts.quiz_id', '=', 'quizzes.id')
                ->where('exam_attempts.student_id', $user->id)
                ->select(
                    'exam_attempts.id',
                    'quizzes.title as quiz_name',
                    'exam_attempts.score',
                    'exam_attempts.status',
                    'exam_attempts.completed_at'
                )
                ->orderBy('exam_attempts.created_at', 'desc')
                ->get();

            return $this->ResponseSuccess($history);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}