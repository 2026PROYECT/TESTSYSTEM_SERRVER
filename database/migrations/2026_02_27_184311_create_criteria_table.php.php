<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// database/migrations/2026_02_27_000004_create_criteria_table.php
return new class extends Migration {
    public function up(): void {
        Schema::create('criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g. Fluency, Accuracy
            $table->text('description')->nullable();
            $table->string('scale')->default('1-5');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('criteria');
    }
};
