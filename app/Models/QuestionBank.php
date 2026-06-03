<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasLanguage; // <--- Importamos el Trait

class QuestionBank extends Model
{
    use HasFactory, HasLanguage; // <--- Activamos el Trait aquí

    protected $table = 'question_banks';

    /**
     * IMPORTANTE: Agregamos language_id al fillable. 
     * Sin esto, el QuestionBankSeeder dará error al intentar insertar.
     */
    protected $fillable = [
        'quiz_id',
        'language_id', // <--- Agregado para el nuevo sistema
        'question1',
        'question2',
        'question3',
        'picture',
        'sound',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'right_answer',
        'area',
    ];

    /**
     * Una pregunta pertenece a un Quiz.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }

    public function options()
    {
        return $this->hasMany(Option::class, 'question_id');
    }

    // ELIMINAMOS el método booted() manual.
    // El Trait HasLanguage ya hace este trabajo de forma limpia.
}