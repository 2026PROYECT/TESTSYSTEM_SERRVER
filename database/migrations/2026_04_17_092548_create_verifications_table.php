<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('verifications', function (Blueprint $ca) {
    $ca->id();
    // El identificador único que irá en la URL del QR
    $ca->uuid('uuid')->unique(); 
    
    // Polimorfismo: Permite que el QR apunte a cualquier modelo (Examen, Estudiante, etc.)
    $ca->nullableMorphs('verifiable'); 
    
    // Metadatos útiles
    $ca->string('type'); // ej: 'ORAL_EXAM', 'ATTENDANCE', 'CERTIFICATE'
    $ca->integer('scans_count')->default(0); // Para saber cuántas veces se escaneó
    $ca->timestamp('expires_at')->nullable(); // Por si el QR debe caducar
    $ca->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verifications');
    }
};
