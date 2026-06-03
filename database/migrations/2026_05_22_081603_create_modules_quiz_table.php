<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('modules_quiz', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->unsignedBigInteger('quiz_id');
            $table->integer('order_position')->default(0);
            $table->timestamps();
            
            $table->index('module_id');
            $table->index('quiz_id');
            $table->unique(['module_id', 'quiz_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('modules_quiz');
    }
};