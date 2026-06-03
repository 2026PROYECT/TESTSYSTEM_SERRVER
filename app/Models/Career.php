<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Career extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * Relationship: A Career has many Students.
     */
    public function students()
    {
        return $this->hasMany(Student::class);
    }
}