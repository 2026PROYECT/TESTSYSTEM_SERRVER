<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('oral_results', function (Blueprint $table) {
        // Añadimos la columna después de student_id
        $table->unsignedBigInteger('teacher_id')->nullable()->after('student_id');
        
        // Opcional: Añadir la clave foránea
        $table->foreign('teacher_id')->references('id')->on('users')->onDelete('set null');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oral_results', function (Blueprint $table) {
            //
        });
    }
};
