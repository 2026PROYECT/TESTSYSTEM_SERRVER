<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentSummary extends Model
{
    // Nombre exacto de la vista SQL que creamos
    protected $table = 'student_test_summaries'; // <--- Revisa que coincida con el nombre en la DB
protected $keyType = 'int';
public $incrementing = false;
    // La vista no tiene created_at/updated_at propios
    public $timestamps = false;

    // Usamos el ID del estudiante como referencia
    protected $primaryKey = 'student_id';
}