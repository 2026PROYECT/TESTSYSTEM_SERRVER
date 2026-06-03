<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimeSlotBreaksSeeder extends Seeder
{
    public function run(): void
    {
        $days = [1, 2, 3, 4, 5];
        
        foreach ($days as $day) {
            // Pausa para OralTest
            DB::table('time_slot_breaks')->updateOrInsert(
                [
                    'test_type' => 'OralTest',
                    'day_of_week' => $day,
                    'break_start' => '13:30:00',
                ],
                [
                    'break_end' => '14:20:00',
                    'description' => 'Almuerzo',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            
            // Pausa para CompTest
            DB::table('time_slot_breaks')->updateOrInsert(
                [
                    'test_type' => 'CompTest',
                    'day_of_week' => $day,
                    'break_start' => '13:30:00',
                ],
                [
                    'break_end' => '14:20:00',
                    'description' => 'Almuerzo',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        
        $this->command->info('✅ Pausas creadas exitosamente!');
    }
}