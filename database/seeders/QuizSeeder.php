<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Quiz;
use App\Enums\QuizType;
use Carbon\Carbon;

class QuizSeeder extends Seeder
{
    public function run(): void
    {
        $quizzes = [
            [
                'title'        => 'Quiz de Geografía',
                'subject_name' => 'Geografía',
                'quiz_date'    => Carbon::now(),
                'total_mark'   => 100,
                'pass_mark'    => 50,
                'language_id'  => 1,
                'type'         => 'normal', // ← agregar
                'duration_minutes' => 60,   // ← agregar
                'total_questions' => 20,    // ← agregar
            ],
            [
                'title'        => 'Quiz de Matemáticas',
                'subject_name' => 'Matemáticas',
                'quiz_date'    => Carbon::now()->addDays(1),
                'total_mark'   => 100,
                'pass_mark'    => 60,
                'language_id'  => 1,
                'type'         => 'normal',
                'duration_minutes' => 60,
                'total_questions' => 20,
            ],
            [
                'title'        => 'Quiz de Historia',
                'subject_name' => 'Historia',
                'quiz_date'    => Carbon::now()->addDays(2),
                'total_mark'   => 100,
                'pass_mark'    => 40,
                'language_id'  => 1,
                'type'         => 'normal',
                'duration_minutes' => 60,
                'total_questions' => 20,
            ],
            [
                'title'        => 'Quiz de Programación',
                'subject_name' => 'Informática',
                'quiz_date'    => Carbon::now()->addDays(3),
                'total_mark'   => 100,
                'pass_mark'    => 70,
                'language_id'  => 1,
                'type'         => 'normal',
                'duration_minutes' => 60,
                'total_questions' => 20,
            ],
            // ✅ EXAMEN MODULAR (agregar este)
            [
                'title'        => 'Examen Modular de Inglés',
                'subject_name' => 'Reading & Listening',
                'quiz_date'    => Carbon::now()->addDays(30),
                'total_mark'   => 100,
                'pass_mark'    => 60,
                'language_id'  => 1,
                'type'         => QuizType::MODULAR, // o 'modular'
                'duration_minutes' => 90,
                'total_questions' => 20,
            ],
        ];

        foreach ($quizzes as $quiz) {
            Quiz::create($quiz);
        }
    }
}