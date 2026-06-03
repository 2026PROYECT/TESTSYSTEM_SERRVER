<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('lastname');
            $table->string('surname')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('student'); 
            $table->string('picture')->nullable();
            
            // Suspension tracking for no-shows
            $table->timestamp('penalty_until')->nullable(); 
            
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('users');
    }
};