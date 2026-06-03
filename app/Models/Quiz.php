<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // ✅ AGREGAR ESTA LÍNEA
use App\Traits\HasLanguage; 
use App\Enums\QuizType;

class Quiz extends Model
{
    use HasFactory, HasLanguage; 

    protected $guarded = ['id'];

    protected $casts = [
        'type' => QuizType::class,
        'total_questions' => 'integer',
    ];

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuestionBank::class, 'quiz_id');
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'quiz_assignments');
    }

    public function results()
    {
        return $this->hasMany(QuestionResult::class);
    }
    
    // ✅ NUEVA RELACIÓN: Módulos (para tipo modular)
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'modules_quiz')
                    ->withPivot('order_position')
                    ->withTimestamps();
    }

    // Verificar si es modular
    public function isModular()
    {
        return $this->type === 'modular';
    }

    // Verificar si es normal
    public function isNormal()
    {
        return $this->type === 'normal';
    }
}