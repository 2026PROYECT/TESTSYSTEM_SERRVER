<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('modular_exam_attempts', function (Blueprint $table) {
            $table->integer('time_left')->default(0)->after('expires_at');
            $table->timestamp('last_sync_at')->nullable()->after('time_left');
            $table->json('played_audios')->nullable()->after('last_sync_at');
            $table->json('viewed_media')->nullable()->after('played_audios');
        });
    }

    public function down()
    {
        Schema::table('modular_exam_attempts', function (Blueprint $table) {
            $table->dropColumn(['time_left', 'last_sync_at', 'played_audios', 'viewed_media']);
        });
    }
};