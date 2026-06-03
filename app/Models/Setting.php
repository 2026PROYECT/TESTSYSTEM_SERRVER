<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    // Permitimos que Laravel guarde estos campos mediante Mass Assignment
    protected $fillable = [
        'key',
        'value',
        'description',
    ];
}