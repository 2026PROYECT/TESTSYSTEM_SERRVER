<?php
// app/Models/TimeSlotException.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TimeSlotException extends Model
{
    use HasFactory;

    protected $table = 'time_slot_exceptions';

    protected $fillable = [
        'date',
        'test_type',
        'custom_slots',
        'is_holiday',
        'reason'
    ];

    protected $casts = [
        'is_holiday' => 'boolean',
        'date' => 'date',
        'custom_slots' => 'array', // Laravel automáticamente convierte JSON a array
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