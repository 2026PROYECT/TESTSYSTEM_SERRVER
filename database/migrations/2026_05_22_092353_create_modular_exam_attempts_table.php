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
            
            // Control de progreso
            $table->integer('current_module_index')->default(0);
            $table->enum('status', ['in_progress', 'completed', 'expired', 'invalidated'])->default('in_progress');
            
            // Tiempos
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // Campos adicionales (sin ->after)
            $table->integer('time_left')->default(0);
            $table->timestamp('last_sync_at')->nullable();
            
            // Control de medios
            $table->json('played_audios')->nullable();
            $table->json('viewed_media')->nullable();
            
            // Módulos asignados
            $table->json('assigned_modules')->nullable();
            
            // Respuestas y resultados
            $table->json('answers')->nullable();
            $table->integer('total_score')->default(0);
            $table->integer('total_percentage')->default(0);
            $table->json('results_data')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index('assignment_id');
            $table->index('student_id');
            $table->index('status');
            $table->index('expires_at');
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
