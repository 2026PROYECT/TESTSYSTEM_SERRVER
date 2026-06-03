<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'career_id',
        'semester',
        'saga_code',
        'id_number',
        'idcard_picture', // NEW
    ];

    protected $appends = ['idcard_picture_url'];
    /**
     * Relationship: A student belongs to a User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
public function getIdcardPictureUrlAttribute()
    {
        // Tu código actual usa Storage::url, asegúrate que APP_URL en .env sea correcta
        return $this->idcard_picture ? \Storage::url($this->idcard_picture) : null;
    }
    /**
     * Relationship: A student belongs to a Career.
     */
    public function career()
{
    // Relacionamos con el modelo Career usando la llave career_id
    return $this->belongsTo(\App\Models\Career::class, 'career_id');
}
    /**
     * Accessor: Return full URL for the ID card picture.
     */
   
}

