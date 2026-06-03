<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('module_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->longText('question_text');
            $table->string('option_a');
            $table->string('option_b');
            $table->string('option_c');
            $table->string('option_d');
            $table->tinyInteger('right_answer');
            $table->integer('points')->default(1);
            $table->integer('order_position')->default(0);
            $table->timestamps();
            
            // Índice sin foreign key por ahora
            $table->index('module_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('module_questions');
    }
};