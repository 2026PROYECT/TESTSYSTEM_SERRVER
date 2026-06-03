<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->string('audio')->nullable();
            $table->string('picture')->nullable();
            $table->enum('type', ['reading', 'listening', 'mixed'])->default('reading');
            $table->integer('duration_seconds')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('modules');
    }
};