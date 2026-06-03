<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();

            // Link to assignment
            $table->foreignId('assignment_id')
                  ->constrained('quiz_assignments')
                  ->cascadeOnDelete();

            // Attempt data
            $table->integer('score')->nullable();
            $table->boolean('finished')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('time_taken')->nullable(); // seconds
            $table->json('questions')->nullable();     // store chosen questions or answers

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('quiz_attempts');
    }
};

