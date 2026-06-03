<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            // Link to users table
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Link to careers table
            $table->foreignId('career_id')->constrained('careers');

            $table->string('saga_code')->nullable();
            $table->string('id_number')->nullable();
            $table->integer('semester')->default(1);

            // NEW: column for ID card picture
            $table->string('idcard_picture')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
