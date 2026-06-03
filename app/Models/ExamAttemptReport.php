<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamAttemptReport extends Model
{
    use HasFactory;

    // Indicamos la tabla real en la base de datos
    protected $table = 'exam_attempts';

    /**
     * Atributos asignables.
     */
    protected $fillable = [
        'student_id',
        'quiz_id',
        'started_at',
        'expires_at',
        'completed_at',
        'score',
        'correct_answers',
        'status',
        'last_question_index'
    ];

    /**
     * Casts de tipos para fechas y números.
     * Esto permite que en Vue las fechas lleguen con formato ISO estándar.
     */
    protected $casts = [
        'started_at'   => 'datetime',
        'expires_at'   => 'datetime',
        'completed_at' => 'datetime',
        'score'        => 'integer',
        'correct_answers' => 'integer',
    ];

    /**
     * Relación con el Estudiante.
     * Asumiendo que usas el modelo User para los estudiantes.
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Relación con el Examen.
     * Asumiendo que tu modelo de exámenes se llama Quiz.
     */
    public function quiz()
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }

    /**
     * Scope para filtrar solo exámenes finalizados (opcional pero útil para reportes)
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
    public function questions()
{
    // Relación con la tabla attempt_questions
    return $this->hasMany(AttemptQuestion::class, 'exam_attempt_id');
}
protected static function booted()
{
    static::addGlobalScope(new \App\Models\Scopes\LanguageScope);
}
}