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
    Schema::table('quizzes', function (Blueprint $row) {
        // Agregamos la duración. Por defecto ponemos 60 minutos.
        $row->integer('duration_minutes')->default(60)->after('title');
    });
}

public function down()
{
    Schema::table('quizzes', function (Blueprint $row) {
        $row->dropColumn('duration_minutes');
    });
}
};
