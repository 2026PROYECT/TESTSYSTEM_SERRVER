<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quiz_assignments', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('student_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->enum('test_type', ['OralTest', 'CompTest', 'ModularTest']);
            $table->dateTime('start_at'); 
            $table->boolean('active')->default(true);
            $table->boolean('passed')->default(false);
            $table->boolean('attended')->nullable(); 
            
            $table->timestamps();

            // EXPLICACIÓN DEL CAMBIO:
            // Eliminamos la línea $table->unique(...)
            // En su lugar, si quieres que las búsquedas sean rápidas, usa un index normal:
            $table->index(['student_id', 'test_type']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('quiz_assignments');
    }
};