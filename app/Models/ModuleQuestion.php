<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleQuestion extends Model
{
    protected $fillable = [
        'module_id',
        'question_text',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'right_answer',
        'points',
        'order_position'
    ];

    // Relación con el módulo
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    // Obtener la opción correcta como letra
    public function getCorrectLetterAttribute()
    {
        $letters = ['A', 'B', 'C', 'D'];
        return $letters[$this->right_answer - 1] ?? 'A';
    }

    // Obtener todas las opciones en array
    public function getOptionsAttribute()
    {
        return [
            'A' => $this->option_a,
            'B' => $this->option_b,
            'C' => $this->option_c,
            'D' => $this->option_d,
        ];
    }
}