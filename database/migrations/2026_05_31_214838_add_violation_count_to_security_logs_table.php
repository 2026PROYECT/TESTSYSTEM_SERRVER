<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('security_logs', function (Blueprint $table) {
            $table->integer('violation_count')->default(1)->after('details');
            $table->index(['exam_attempt_id', 'event_type', 'created_at'], 'idx_security_violations');
        });
    }

    public function down()
    {
        Schema::table('security_logs', function (Blueprint $table) {
            $table->dropColumn('violation_count');
            $table->dropIndex('idx_security_violations');
        });
    }
};