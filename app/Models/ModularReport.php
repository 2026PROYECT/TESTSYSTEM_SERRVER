<?php
// app/Models/ModularReport.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModularReport extends Model
{
    protected $table = 'verifications'; // No tiene tabla propia
    
    protected $fillable = [];
    
    // Este modelo es solo para la relación polimórfica
    // No necesita tabla propia porque los datos están en metadata
}