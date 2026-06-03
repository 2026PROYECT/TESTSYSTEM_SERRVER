<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('quiz_assignments', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable()->after('notification_sent');
        });
    }

    public function down()
    {
        Schema::table('quiz_assignments', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });
    }
};