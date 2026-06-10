<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('security_logs', function (Blueprint $table) {
            // Eliminar la foreign key existente
            $table->dropForeign(['exam_attempt_id']);
        });
    }

    public function down()
    {
        Schema::table('security_logs', function (Blueprint $table) {
            // Recrear la foreign key (solo si es necesario)
            $table->foreign('exam_attempt_id')
                  ->references('id')
                  ->on('exam_attempts')
                  ->onDelete('set null');
        });
    }
};