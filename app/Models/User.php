<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute; // IMPORTANTE: Falta esta línea
use App\Models\Student;
use App\Models\QuizResult; 
use App\Models\QuizAssignment;


class User extends Authenticatable
{
    
 use Notifiable; // ✅ Debe estar presente
use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'lastname',
        'surname',
        'email',
        'password',
        'role',
        'picture',
        'penalty_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'penalty_until' => 'datetime',
    ];

    // Incluimos estos campos automáticamente en el JSON de la API
    protected $appends = ['picture_url', 'full_name'];

    /**
     * Helper: Check if student is currently suspended.
     */
    public function isBanned()
    {
        return $this->penalty_until && $this->penalty_until->isFuture();
    }

    /**
     * Accessor para la URL de la foto de perfil.
     * Esto corrige el error de carga de imagen en el Edit.vue
     */
    protected function pictureUrl(): Attribute
    {
        return Attribute::get(fn () => $this->picture 
            ? asset('storage/' . $this->picture) 
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=random');
    }

    /**
     * Accessor para el nombre completo.
     */
    public function getFullNameAttribute()
    {
        return "{$this->name} {$this->lastname} " . ($this->surname ?? '');
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function isStudent()
    {
        return $this->role === 'student';
    }

    public function results()
    {
        return $this->hasMany(QuizResult::class, 'user_id');
    }

    public function quizAssignments()
    {
        return $this->hasMany(QuizAssignment::class, 'student_id');
    }

    public function career()
    {
        return $this->belongsTo(\App\Models\Career::class, 'career_id');
    }

    public function studentProfile()
    {
        return $this->hasOne(\App\Models\Student::class, 'user_id');
    }
}