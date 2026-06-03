<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne; // 1. Nueva Importación
use Illuminate\Support\Str; // 2. Nueva Importación para el UUID

class OralResult extends Model
{
    use HasFactory;

    protected $table = 'oral_results';

    protected $fillable = [
        'quiz_assignment_id',
        'student_id',
        'language_id',
        'teacher_id',
        'final_level',
        'attended',
        'final_score',
        'detailed_scores',
        'teacher_feedback'
    ];

    protected $casts = [
        'detailed_scores' => 'array',
        'final_score' => 'float',
        'created_at' => 'datetime',
    ];

    /**
     * RELACIÓN POLIMÓRFICA CON VERIFICACIONES
     * Esto permite conectar este examen con la tabla 'verifications'
     */
    public function verification(): MorphOne
    {
        return $this->morphOne(Verification::class, 'verifiable');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(QuizAssignment::class, 'quiz_assignment_id');
    }

    protected static function booted()
    {
        // Mantenemos tu Scope actual para no afectar el filtrado por idiomas
        static::addGlobalScope(new \App\Models\Scopes\LanguageScope);

        // AÑADIMOS: Generación automática de UUID al crear el registro
        static::created(function ($oralResult) {
            $oralResult->verification()->create([
                'uuid' => (string) Str::uuid(),
                'type' => 'ORAL_EXAM',
            ]);
        });
    }
}