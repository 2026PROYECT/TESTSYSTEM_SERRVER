<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimeSlotExceptionSeeder extends Seeder
{
    public function run(): void
    {
        // Ejemplo: Feriado de Navidad
        DB::table('time_slot_exceptions')->insert([
            'date' => date('Y') . '-12-25',
            'test_type' => 'OralTest',
            'custom_slots' => null,
            'is_holiday' => true,
            'reason' => 'Navidad',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        DB::table('time_slot_exceptions')->insert([
            'date' => date('Y') . '-12-25',
            'test_type' => 'CompTest',
            'custom_slots' => null,
            'is_holiday' => true,
            'reason' => 'Navidad',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}