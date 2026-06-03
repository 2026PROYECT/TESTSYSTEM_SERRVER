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
    Schema::table('pending_users', function (Blueprint $table) {
        // Campos para el flujo de seguridad
        $table->boolean('email_verified')->default(false);
        $table->string('verification_token')->nullable();
        
        // Campos académicos que faltaban
        $table->string('lastname')->after('name');
        $table->string('surname')->nullable()->after('lastname');
        $table->string('id_number')->after('email');
        $table->string('saga_code')->after('id_number');
        $table->foreignId('career_id')->nullable()->after('saga_code');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pending_users', function (Blueprint $table) {
            //
        });
    }
};
