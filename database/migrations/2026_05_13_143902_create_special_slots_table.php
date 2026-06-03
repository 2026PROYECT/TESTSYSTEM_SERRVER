<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('special_slots', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->enum('test_type', ['OralTest', 'CompTest']);
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('capacity')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->string('description', 255)->nullable();
            $table->timestamps();
            
            $table->unique(['date', 'test_type', 'start_time'], 'special_slot_unique');
            $table->index(['date', 'test_type'], 'special_slot_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_slots');
    }
};