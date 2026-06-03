<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    // Campos que permitimos llenar desde el controlador
    protected $fillable = [
        'user_id',
        'action',
        'ip_address',
        'user_agent'
    ];

    /**
     * Relación: Un log pertenece a un usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}