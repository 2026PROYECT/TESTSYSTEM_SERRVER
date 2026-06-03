<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->boolean('security_alert')->default(false)->after('status');
            $table->timestamp('invalidated_at')->nullable()->after('security_alert');
            $table->text('invalidation_reason')->nullable()->after('invalidated_at');
        });
    }

    public function down()
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn(['security_alert', 'invalidated_at', 'invalidation_reason']);
        });
    }
};