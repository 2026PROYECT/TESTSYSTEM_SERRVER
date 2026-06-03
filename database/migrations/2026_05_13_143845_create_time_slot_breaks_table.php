<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_slot_breaks', function (Blueprint $table) {
            $table->id();
            $table->enum('test_type', ['OralTest', 'CompTest']);
            $table->tinyInteger('day_of_week');
            $table->time('break_start');
            $table->time('break_end');
            $table->string('description', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['test_type', 'day_of_week'], 'ts_breaks_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_slot_breaks');
    }
};