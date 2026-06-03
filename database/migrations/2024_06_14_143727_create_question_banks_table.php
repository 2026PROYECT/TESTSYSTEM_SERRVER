<?php

use App\Models\Quiz;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();

            // Relación con la tabla quizzes
            $table->foreignIdFor(Quiz::class)
                  ->constrained()
                  ->cascadeOnDelete();

            // Textos de la pregunta (soporta hasta 3 partes)
            $table->text('question1')->nullable();
            $table->text('question2')->nullable();
            $table->text('question3')->nullable();

            // Rutas de archivos multimedia
            $table->string('picture', 255)->nullable();
            $table->string('sound', 255)->nullable();

            // Opciones de respuesta
            $table->text('option_a');
            $table->text('option_b');
            $table->text('option_c');
            $table->text('option_d');

            /** * CAMBIO CLAVE: right_answer ahora es numérico.
             * 1 = A, 2 = B, 3 = C, 4 = D
             */
            $table->unsignedTinyInteger('right_answer');

            // Área del examen (L: Listening, R: Reading, GEN: General, etc.)
            $table->string('area', 10);

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('question_banks');
    }
};