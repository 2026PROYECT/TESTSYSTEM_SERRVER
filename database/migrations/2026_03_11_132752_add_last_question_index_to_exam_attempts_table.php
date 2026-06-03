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
    Schema::table('exam_attempts', function (Blueprint $table) {
        // Añadimos la columna como entero, por defecto 0
        $table->integer('last_question_index')->default(0)->after('status');
    });
}

public function down(): void
{
    Schema::table('exam_attempts', function (Blueprint $table) {
        $table->dropColumn('last_question_index');
    });
}
};
