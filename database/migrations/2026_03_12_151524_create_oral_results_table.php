<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oral_results', function (Blueprint $table) {
            $table->id();

            // Relación con la asignación
            $table->foreignId('quiz_assignment_id')
                  ->constrained('quiz_assignments')
                  ->onDelete('cascade');

            // CAMBIO AQUÍ: Apuntamos a 'users' para que acepte el ID 2
            $table->foreignId('student_id')
                  ->constrained('users') 
                  ->onDelete('cascade');

            $table->string('final_level'); 
            $table->decimal('final_score', 5, 2); 
            $table->json('detailed_scores'); 

            $table->text('teacher_feedback')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oral_results');
    }
};