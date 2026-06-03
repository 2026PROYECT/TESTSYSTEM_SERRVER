<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasLanguage;
use Carbon\Carbon;

class QuizAssignment extends Model
{
    protected $table = 'quiz_assignments';
    protected $fillable = [
        'student_id',
        'quiz_id',  // 👈 IMPORTANTE: Asegurar que quiz_id está en fillable
        'test_type',
        'start_at',
        'active',
        'language_id',
        'attended',
        'passed'
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'active'   => 'boolean',
        'passed'   => 'boolean'
    ];

    // Relación con estudiante
    public function student() {
        return $this->belongsTo(User::class, 'student_id');
    }

    // 🔥 NUEVA RELACIÓN CON QUIZ - AÑADIR ESTO
    public function quiz() {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }

    // Relación con resultado oral
    public function oralResult()
    {
        return $this->hasOne(OralResult::class, 'quiz_assignment_id');
    }

    // Relación con intento de examen
    public function examAttempt()
    {
        //return $this->hasOne(ExamAttempt::class, 'quiz_assignment_id');
    }

    // Relación con idioma
    public function language() {
        return $this->belongsTo(Language::class, 'language_id');
    }

    protected static function booted()
    {
        static::addGlobalScope(new \App\Models\Scopes\LanguageScope);
    }
}