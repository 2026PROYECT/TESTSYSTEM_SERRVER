<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingUser extends Model
{
    protected $fillable = [
        'name',
        'lastname',
        'surname',
        'email',
        'password',
        'id_number',
        'saga_code',
        'career_id',
        'ci_front',
        'ci_back',
        'user_photo',
        'verification_token',
        'email_verified'
    ];
    
    protected $hidden = ['password'];
    
    // Relación con Career
    public function career()
    {
        return $this->belongsTo(Career::class, 'career_id');
    }
}