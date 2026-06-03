<?php
// app/Models/ModularExamAttempt.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModularExamAttempt extends Model
{
    protected $table = 'modular_exam_attempts';
    
    protected $fillable = [
        'assignment_id',
        'student_id',
        'current_module_index',
        'status',
        'started_at',
        'completed_at',
        'expires_at',
        'answers',
        'total_score',
        'total_percentage',
        'results_data'
    ];
    
    protected $casts = [
        'answers' => 'array',
        'results_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime'
    ];
    
    // Relación con el estudiante
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
    
    // Relación con la asignación
    public function assignment()
    {
        return $this->belongsTo(QuizAssignment::class, 'assignment_id');
    }
}