<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QRCode extends Model
{
    use HasFactory;

    protected $table = 'q_r_codes';
    
    protected $fillable = [
        'title',
        'content',
        'description',
        'size',
        'scans',
        'color',        // 👈 Nuevo campo
        'background',   // 👈 Nuevo campo
        'logo'          // 👈 Nuevo campo (ruta del logo)
    ];
    
    protected $casts = [
        'scans' => 'integer',
        'size' => 'integer'
    ];
}