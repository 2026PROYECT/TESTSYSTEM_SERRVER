<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_slot_exceptions', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->enum('test_type', ['OralTest', 'CompTest']);
            $table->json('custom_slots')->nullable();
            $table->boolean('is_holiday')->default(false);
            $table->string('reason', 255)->nullable();
            $table->timestamps();
            
            $table->unique(['date', 'test_type'], 'ts_exception_unique');
            $table->index('date', 'ts_exception_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_slot_exceptions');
    }
};