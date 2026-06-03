<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    public function up()
{
    Schema::create('options', function (Blueprint $table) {
        $table->id();
        // Relación con la pregunta
        $table->foreignId('question_id')->constrained()->onDelete('cascade');
        $table->text('option_text');
        $table->boolean('is_correct')->default(false); // <--- Clave para calificar
        $table->timestamps();
    });
}
// En app/Models/Option.php
public function question()
{
    return $this->belongsTo(Question::class, 'exam_question_id');
}
}
