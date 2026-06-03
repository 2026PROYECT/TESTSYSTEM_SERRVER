<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// database/migrations/2026_02_27_000006_create_responses_table.php
return new class extends Migration {
    public function up(): void {
        Schema::create('responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('test_assignments')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('response_text')->nullable();
            $table->string('recording_url')->nullable(); // optional audio file
            $table->unsignedInteger('score')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('responses');
    }
};
