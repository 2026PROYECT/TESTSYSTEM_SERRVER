<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oral_questions', function (Blueprint $table) {
            $table->id();
            $table->text('question_text'); // La pregunta en sí
            $table->enum('level', ['A1', 'A2', 'B1', 'B2', 'C1', 'C2']); // Niveles MCER
            $table->string('category')->default('General'); // Para organizar por temas
            $table->boolean('active')->default(true); // Para poder "borrar" sin perder datos
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oral_questions');
    }
};