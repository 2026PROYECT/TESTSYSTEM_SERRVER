<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExamAttempt extends Model
{
    protected $fillable = [
        'student_id', 'quiz_id', 'started_at', 
        'expires_at', 'completed_at', 'score', 
        'correct_answers', 'status', 'last_question_index'
    ];

    // Relación con el alumno (original)
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    // ✅ AGREGAR ESTA RELACIÓN (alias para 'user')
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    // Relación con el examen original (Quiz)
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    // Relación con las preguntas sorteables de este intento
    public function attemptQuestions(): HasMany
    {
        return $this->hasMany(AttemptQuestion::class, 'exam_attempt_id');
    }

    // Relación con la sesión de rastreo (donde se quedó el alumno)
    public function session(): HasOne
    {
        return $this->hasOne(ExamSession::class);
    }
    
    protected static function booted()
    {
        static::addGlobalScope(new \App\Models\Scopes\LanguageScope);
    }
}