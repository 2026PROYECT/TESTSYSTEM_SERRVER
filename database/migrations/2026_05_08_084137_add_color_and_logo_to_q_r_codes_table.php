<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('q_r_codes', function (Blueprint $table) {
            $table->string('color')->default('#000000')->after('size');
            $table->string('background')->default('#FFFFFF')->after('color');
            $table->string('logo')->nullable()->after('background');
        });
    }

    public function down(): void
    {
        Schema::table('q_r_codes', function (Blueprint $table) {
            $table->dropColumn(['color', 'background', 'logo']);
        });
    }
};
