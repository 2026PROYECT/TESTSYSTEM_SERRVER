<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'language_id',
        'title',
        'content',
        'audio',
        'picture',
        'type',
        'level',
        'duration_seconds'
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'language_id' => 'integer'
    ];

    // Relación con el idioma
    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    // Relación con las preguntas
    public function questions()
    {
        return $this->hasMany(ModuleQuestion::class, 'module_id');
    }
}