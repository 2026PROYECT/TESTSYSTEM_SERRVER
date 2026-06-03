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
    Schema::create('languages', function (Blueprint $table) {
        $table->id();
        $table->string('name');          // Ej: "Inglés", "Francés"
        $table->string('code')->unique(); // Ej: "en", "fr", "pt" (útil para iconos o banderas)
        $table->string('slug')->unique(); // Ej: "ingles", "frances" (para las URLs)
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
