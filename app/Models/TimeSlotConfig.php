<?php
// app/Models/TimeSlotConfig.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TimeSlotConfig extends Model
{
    use HasFactory;

    protected $table = 'time_slot_configs';

    protected $fillable = [
        'test_type',
        'day_of_week',
        'start_time',
        'end_time',
        'slot_duration',
        'capacity',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    const TEST_TYPES = [
        'OralTest' => 'Oral Test',
        'CompTest' => 'Comp Test'
    ];

    const DAYS_OF_WEEK = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByTestType($query, $testType)
    {
        return $query->where('test_type', $testType);
    }

    public function getDayNameAttribute()
    {
        return self::DAYS_OF_WEEK[$this->day_of_week] ?? 'Desconocido';
    }
}