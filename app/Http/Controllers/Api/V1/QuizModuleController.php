<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Module;
use Illuminate\Http\Request;

class QuizModuleController extends Controller
{
    public function index($quizId)
    {
        $quiz = Quiz::findOrFail($quizId);
        $modules = $quiz->modules()->orderBy('pivot_order_position')->get();
        return response()->json($modules);
    }

    public function attach($quizId, $moduleId)
    {
        $quiz = Quiz::findOrFail($quizId);
        $quiz->modules()->attach($moduleId, ['order_position' => $quiz->modules()->count() + 1]);
        return response()->json(['success' => true]);
    }

    public function detach($quizId, $moduleId)
    {
        $quiz = Quiz::findOrFail($quizId);
        $quiz->modules()->detach($moduleId);
        return response()->json(['success' => true]);
    }

    public function updateOrder(Request $request, $quizId)
    {
        $quiz = Quiz::findOrFail($quizId);
        foreach ($request->order as $item) {
            $quiz->modules()->updateExistingPivot($item['id'], ['order_position' => $item['order']]);
        }
        return response()->json(['success' => true]);
    }
}