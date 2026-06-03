<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimeSlotConfigSeeder extends Seeder
{
    public function run(): void
    {
        // Configuración para OralTest (Lunes a Viernes)
        $days = [1, 2, 3, 4, 5]; // Lunes=1, Martes=2, Miércoles=3, Jueves=4, Viernes=5
        
        foreach ($days as $day) {
            // Configuración OralTest
            DB::table('time_slot_configs')->insert([
                'test_type' => 'OralTest',
                'day_of_week' => $day,
                'start_time' => '08:00:00',
                'end_time' => '15:00:00',
                'slot_duration' => 30,
                'capacity' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Configuración CompTest
            DB::table('time_slot_configs')->insert([
                'test_type' => 'CompTest',
                'day_of_week' => $day,
                'start_time' => '08:00:00',
                'end_time' => '15:00:00',
                'slot_duration' => 60,
                'capacity' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Configuración para Sábado (día 6)
        DB::table('time_slot_configs')->insert([
            'test_type' => 'OralTest',
            'day_of_week' => 6,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'slot_duration' => 30,
            'capacity' => 1,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        DB::table('time_slot_configs')->insert([
            'test_type' => 'CompTest',
            'day_of_week' => 6,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'slot_duration' => 60,
            'capacity' => 4,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // ✅ Pausa de almuerzo MODIFICADA: 13:30 a 14:20 (Lunes a Viernes)
        foreach ($days as $day) {
            DB::table('time_slot_breaks')->insert([
                'test_type' => 'OralTest',
                'day_of_week' => $day,
                'break_start' => '13:30:00',
                'break_end' => '14:20:00',
                'description' => 'Almuerzo',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            DB::table('time_slot_breaks')->insert([
                'test_type' => 'CompTest',
                'day_of_week' => $day,
                'break_start' => '13:30:00',
                'break_end' => '14:20:00',
                'description' => 'Almuerzo',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // ✅ Opcional: Si también quieres pausa para Sábado
        // DB::table('time_slot_breaks')->insert([
        //     'test_type' => 'OralTest',
        //     'day_of_week' => 6,
        //     'break_start' => '13:30:00',
        //     'break_end' => '14:20:00',
        //     'description' => 'Almuerzo',
        //     'is_active' => false, // Desactivado porque sábado tiene horario reducido
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);
    }
}