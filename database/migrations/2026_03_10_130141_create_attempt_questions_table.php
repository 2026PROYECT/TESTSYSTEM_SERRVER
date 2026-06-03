<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempt_questions', function (Blueprint $table) {
            $table->bigIncrements('id'); // PRIMARY KEY auto_increment

            $table->unsignedBigInteger('exam_attempt_id');   // FK -> exam_attempts.id
            $table->unsignedBigInteger('question_id');       // FK -> question_banks.id
            $table->integer('order_position');               // índice junto con exam_attempt_id
            $table->unsignedBigInteger('selected_option_id')->nullable(); // FK opcional

            $table->timestamps();

            // Índices
            $table->index(['exam_attempt_id', 'order_position'], 'attempt_questions_exam_attempt_id_order_position_index');
            $table->index('selected_option_id', 'attempt_questions_selected_option_id_foreign');
            $table->index('question_id', 'attempt_questions_question_id_foreign');

            // Claves foráneas
            $table->foreign('exam_attempt_id', 'attempt_questions_exam_attempt_id_foreign')
                  ->references('id')->on('exam_attempts')
                  ->onDelete('cascade');

            $table->foreign('question_id', 'attempt_questions_question_id_foreign')
                  ->references('id')->on('question_banks')
                  ->onDelete('cascade');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_questions');
    }
};
