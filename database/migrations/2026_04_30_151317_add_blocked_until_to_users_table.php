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
    Schema::table('users', function (Blueprint $table) {
        // Almacena la fecha de fin del bloqueo. Es nullable porque 
        // la mayoría de los usuarios no estarán bloqueados.
        $table->timestamp('blocked_until')->nullable()->after('email');
    });
}

    /**
     * Reverse the migrations.
     */
   public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('blocked_until');
    });
}
};
