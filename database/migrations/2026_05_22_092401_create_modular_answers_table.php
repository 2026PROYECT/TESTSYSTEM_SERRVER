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
        Schema::create('modular_answers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('attempt_id')->constrained('modular_exam_attempts')->onDelete('cascade');
    $table->foreignId('module_id')->constrained('modules')->onDelete('cascade');
    $table->foreignId('question_id')->constrained('module_questions')->onDelete('cascade');
    $table->string('selected_option', 1);
    $table->boolean('is_correct')->default(false);
    $table->timestamps();
    
    $table->unique(['attempt_id', 'question_id']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modular_answers');
    }
};
