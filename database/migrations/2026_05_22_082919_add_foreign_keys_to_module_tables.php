<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Agregar foreign key a module_questions
        Schema::table('module_questions', function (Blueprint $table) {
            $table->foreign('module_id')->references('id')->on('modules')->onDelete('cascade');
        });
        
        // Agregar foreign keys a modules_quiz
        Schema::table('modules_quiz', function (Blueprint $table) {
            $table->foreign('module_id')->references('id')->on('modules')->onDelete('cascade');
            $table->foreign('quiz_id')->references('id')->on('quizzes')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('module_questions', function (Blueprint $table) {
            $table->dropForeign(['module_id']);
        });
        
        Schema::table('modules_quiz', function (Blueprint $table) {
            $table->dropForeign(['module_id']);
            $table->dropForeign(['quiz_id']);
        });
    }
};