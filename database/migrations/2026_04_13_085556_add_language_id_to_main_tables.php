<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Lista de tablas remarcadas en tu imagen
    protected $tables = [
        'attempt_questions',
        'exam_attempts',
        'exam_sessions',
        'oral_exams',
        'oral_questions',
        'oral_results',
        'question_banks',
        'questions',
        'quiz_assignments',
        'quiz_attempts',
        'quiz_results',
        'quizzes'
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            // Verificamos si la tabla existe y si NO tiene ya la columna
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'language_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    // La creamos como nullable para no romper datos existentes
                    $table->foreignId('language_id')
                          ->nullable()
                          ->after('id') // La pone justo después del ID
                          ->constrained('languages')
                          ->onDelete('cascade');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasColumn($tableName, 'language_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['language_id']);
                    $table->dropColumn('language_id');
                });
            }
        }
    }
};