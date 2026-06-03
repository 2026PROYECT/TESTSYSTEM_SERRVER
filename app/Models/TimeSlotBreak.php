<?php
// app/Models/TimeSlotBreak.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TimeSlotBreak extends Model
{
    use HasFactory;

    protected $table = 'time_slot_breaks';

    protected $fillable = [
        'test_type',
        'day_of_week',
        'break_start',
        'break_end',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByTestType($query, $testType)
    {
        return $query->where('test_type', $testType);
    }
}