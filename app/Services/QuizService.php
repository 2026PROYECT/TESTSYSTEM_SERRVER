<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuizAssignment;
use App\Models\QuestionResult;
use Illuminate\Http\Request;

final class QuizService
{
    public function allPaginate($request, $perPage)
    {
        $orderColumn = request('order_column', 'quiz_date');
        $orderDirection = request('order_direction', 'desc');

        $allowedColumns = ['id', 'title', 'duration_minutes', 'quiz_date', 'subject_name', 'total_mark', 'type', 'total_questions']; // 👈 AÑADE total_questions
        if (!in_array($orderColumn, $allowedColumns)) {
            $orderColumn = 'quiz_date';
        }

        return Quiz::query()
            ->withCount('questions') 
            ->when($request->query('type'), function ($query, $type) {
                $query->where('type', $type);
            })
            ->orderBy($orderColumn, $orderDirection)
            ->paginate($perPage);
    }

    public function all(Request $request)
    {
        return Quiz::withCount('questions')
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->get();
    }

    public function store($request)
    {
        // 👈 AÑADIR total_questions al crear
        return Quiz::create([
            'title' => $request->title,
            'duration_minutes' => $request->duration_minutes,
            'subject_name' => $request->subject_name,
            'quiz_date' => $request->quiz_date,
            'total_mark' => $request->total_mark,
            'pass_mark' => $request->pass_mark,
            'type' => $request->type,
            'total_questions' => $request->total_questions ?? 10, // 👈 AÑADE ESTA LÍNEA
        ]);
    }

    public function update($request, $id)
    {
        $quiz = $this->show($id);
        if ($quiz) {
            // 👈 AÑADIR total_questions al actualizar
            $quiz->update([
                'title' => $request->title,
                'duration_minutes' => $request->duration_minutes,
                'subject_name' => $request->subject_name,
                'quiz_date' => $request->quiz_date,
                'total_mark' => $request->total_mark,
                'pass_mark' => $request->pass_mark,
                'type' => $request->type,
                'total_questions' => $request->total_questions ?? 10, // 👈 AÑADE ESTA LÍNEA
            ]);
        }
        return $quiz;
    }

    public function show($id)
    {
        return Quiz::with(['questions'])->find($id);
    }

    public function delete($id)
    {
        $quiz = Quiz::find($id);
        
        if (!$quiz) {
            return null;
        }
        
        Question::where('quiz_id', $id)->delete();
        
        return $quiz;
    }
}