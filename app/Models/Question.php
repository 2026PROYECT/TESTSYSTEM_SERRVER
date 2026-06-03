<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
protected $table = 'question_banks';    
// Añadimos 'prompt' que es tu campo de texto de la pregunta
    protected $fillable = ['section_id', 'prompt', 'difficulty_level', 'order'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    // NUEVO: Estas son las opciones (A, B, C, D) que verá el alumno
    public function options(): HasMany
    {
        return $this->hasMany(Option::class, 'exam_question_id');
    }

    // Estas son las respuestas enviadas por los alumnos
    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }
    protected static function booted()
{
    static::addGlobalScope(new \App\Models\Scopes\LanguageScope);
}
public function quiz()
{
    return $this->belongsTo(Quiz::class);
}
}