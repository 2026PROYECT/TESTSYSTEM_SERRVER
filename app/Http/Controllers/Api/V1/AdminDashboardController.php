<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OralResult;
use App\Models\QuestionBank;
use App\Models\QuizAssignment;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function getSummary()
    {
        // 1. Total questions in question_banks
        $totalQuestions = QuestionBank::count();

        // 2. Oral Results average score
        $avgOralScore = OralResult::avg('score') ?? 0;

        // 3. Quiz Assignments: Total tests and Avg time (in minutes)
        // Assuming you have 'started_at' and 'completed_at' columns
        $assignmentStats = QuizAssignment::whereNotNull('completed_at')
            ->select(
                DB::raw('COUNT(*) as total_completed'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as avg_time')
            )->first();

        return response()->json([
            'stats' => [
                ['label' => 'Banco de Preguntas', 'value' => $totalQuestions, 'trend' => 5],
                ['label' => 'Promedio Oral', 'value' => number_format($avgOralScore, 1), 'trend' => 2],
                ['label' => 'Tests Finalizados', 'value' => $assignmentStats->total_completed ?? 0, 'trend' => 8],
                ['label' => 'Tiempo Promedio', 'value' => round($assignmentStats->avg_time ?? 0) . 'm', 'trend' => -3]
            ],
            // Data for the Bar Chart (Example: exams per day for the last 7 days)
            'chart' => $this->getWeeklyActivity()
        ]);
    }

    private function getWeeklyActivity()
    {
        return QuizAssignment::select(DB::raw('DATE_FORMAT(created_at, "%D") as day'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('day')
            ->get();
    }
}