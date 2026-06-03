<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptQuestion extends Model
{
    protected $fillable = [
        'exam_attempt_id', 'question_id', 
        'order_position', 'selected_option_id'
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    // La pregunta original (donde está el prompt/enunciado)
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_id');
    }

    // La opción que el alumno seleccionó (si ya marcó alguna)
    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'selected_option_id');
    }
    protected static function booted()
{
    static::addGlobalScope(new \App\Models\Scopes\LanguageScope);
}
}
