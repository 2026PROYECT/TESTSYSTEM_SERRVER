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
    Schema::table('quiz_assignments', function (Blueprint $table) {
        // Por defecto false: el admin debe activarlo físicamente
        $table->boolean('is_unlocked')->default(false); 
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_assignments', function (Blueprint $table) {
            //
        });
    }
};
