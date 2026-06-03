<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sessions', function (Blueprint $table) {
            // Session ID
            $table->string('id')->primary();

            // Link to user (optional, can be null if guest)
            $table->foreignId('user_id')->nullable()
                  ->constrained('users')
                  ->cascadeOnDelete();

            // Session metadata
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload'); // serialized session data
            $table->integer('last_activity'); // timestamp (int)

            // No timestamps here, Laravel uses last_activity
        });
    }

    public function down(): void {
        Schema::dropIfExists('sessions');
    }
};
