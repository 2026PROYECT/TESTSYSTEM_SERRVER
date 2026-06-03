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
    Schema::create('exam_attempts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
        
        // Cambiamos a nullable() para evitar el error 1067 en MySQL
        $table->timestamp('started_at')->nullable(); 
        $table->timestamp('expires_at')->nullable(); 
        $table->timestamp('completed_at')->nullable(); 
        
        $table->integer('score')->nullable();
        $table->integer('correct_answers')->default(0);
        
        $table->enum('status', ['in_progress', 'completed', 'timed_out'])->default('in_progress');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
