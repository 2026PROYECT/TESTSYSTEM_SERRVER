<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('modular_exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('quiz_assignments')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            // 🔥 Eliminamos quiz_id porque ModularTest no lo necesita
            // $table->foreignId('quiz_id')->constrained('quizzes')->onDelete('cascade');
            
            $table->integer('current_module_index')->default(0);
            $table->enum('status', ['in_progress', 'completed', 'expired'])->default('in_progress');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('answers')->nullable();  // ← Agregar campo answers
            $table->integer('total_score')->default(0);
            $table->integer('total_percentage')->default(0);
            $table->json('results_data')->nullable();
            $table->timestamps();
            
            // Índices para mejorar performance
            $table->index('assignment_id');
            $table->index('student_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modular_exam_attempts');
    }
};