<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('q_r_codes', function (Blueprint $table) {
            $table->id();
            $table->string('title');                    // Título del QR
            $table->text('content');                    // Contenido/URL
            $table->text('description')->nullable();    // Descripción opcional
            $table->integer('size')->default(300);      // Tamaño en píxeles
            $table->integer('scans')->default(0);       // Contador de escaneos
            $table->timestamps();                       // created_at, updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('q_r_codes');
    }
};