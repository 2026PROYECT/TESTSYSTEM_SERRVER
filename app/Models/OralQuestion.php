<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasLanguage; // <--- Importamos el Trait

class OralQuestion extends Model
{
    use HasFactory, HasLanguage; // <--- USAMOS el Trait aquí

    protected $table = 'oral_questions';

    /**
     * IMPORTANTE: Agregamos language_id al fillable para que el Seeder 
     * y los formularios puedan guardar el idioma.
     */
    protected $fillable = [
        'question_text',
        'level',
        'category',
        'active',
        'language_id', // <--- Agregado
    ];

    protected $casts = [
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scopes de búsqueda (estos están perfectos)
     */
    public function scopeByLevel($query, $level)
    {
        if ($level && $level !== 'all') {
            return $query->where('level', $level);
        }
        return $query;
    }

    public function scopeSearch($query, $term)
    {
        if ($term) {
            return $query->where('question_text', 'like', "%{$term}%");
        }
        return $query;
    }

    // ELIMINAMOS el método booted() manual. 
    // El Trait HasLanguage ya se encarga de aplicar el GlobalScope por ti.
}