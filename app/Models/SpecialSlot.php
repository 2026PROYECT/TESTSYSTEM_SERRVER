<?php
// app/Models/SpecialSlot.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SpecialSlot extends Model
{
    use HasFactory;

    protected $table = 'special_slots';

    protected $fillable = [
        'date',
        'test_type',
        'start_time',
        'end_time',
        'capacity',
        'is_blocked',
        'description'
    ];

    protected $casts = [
        'is_blocked' => 'boolean',
        'date' => 'date',
    ];

    public function scopeByDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeByTestType($query, $testType)
    {
        return $query->where('test_type', $testType);
    }
}