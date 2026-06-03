<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('exam_attempt_id')->nullable()->constrained()->onDelete('set null');
            $table->string('event_type'); // screenshot_attempt, tab_switch, devtools_opened, mouse_leave, etc.
            $table->text('details')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            // Índices para búsquedas rápidas
            $table->index(['user_id', 'exam_attempt_id']);
            $table->index('event_type');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('security_logs');
    }
};