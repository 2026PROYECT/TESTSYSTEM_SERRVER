<?php
// app/Services/TimeSlotService.php

namespace App\Services;

use App\Models\TimeSlotConfig;
use App\Models\TimeSlotBreak;
use App\Models\SpecialSlot;
use App\Models\TimeSlotException;
use App\Models\QuizAssignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB; // Añade esto si lo necesitas

class TimeSlotService
{
    /**
     * Obtener todos los slots disponibles para una fecha y tipo de examen
     */
    public function getAvailableSlots(string $date, string $testType, int $languageId): array
    {
        $carbonDate = Carbon::parse($date);
        $dayOfWeek = $carbonDate->dayOfWeek;
        $isToday = $carbonDate->isToday();
        $now = Carbon::now();
        
        // 1. Verificar excepciones
        $exception = TimeSlotException::where('date', $date)
            ->where('test_type', $testType)
            ->first();
        
        if ($exception && $exception->is_holiday) {
            return [];
        }
        
        // 2. Generar slots base
        if ($exception && $exception->custom_slots) {
            $slots = $this->generateSlotsFromCustomConfig($exception->custom_slots);
        } else {
            $config = TimeSlotConfig::where('test_type', $testType)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->first();
            
            if (!$config) {
                return [];
            }
            
            $slots = $this->generateSlotsFromConfig($config);
            
            // 3. Aplicar pausas
            $breaks = TimeSlotBreak::where('test_type', $testType)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->get();
            
            $slots = $this->applyBreaks($slots, $breaks);
        }
        
        // 4. Aplicar modificaciones especiales
        $specialSlots = SpecialSlot::where('date', $date)
            ->where('test_type', $testType)
            ->get();
        
        $slots = $this->applySpecialSlots($slots, $specialSlots);
        
        // 5. Obtener ocupación actual
        $occupancy = $this->getCurrentOccupancy($date, $testType, $languageId);
        
        // 6. Calcular disponibilidad
        return $this->calculateAvailability($slots, $occupancy, $isToday, $now);
    }
    
    /**
     * Generar slots desde configuración base
     */
    private function generateSlotsFromConfig(TimeSlotConfig $config): array
    {
        $slots = [];
        $start = Carbon::parse($config->start_time);
        $end = Carbon::parse($config->end_time);
        $duration = $config->slot_duration;
        
        while ($start < $end) {
            $slots[] = [
                'time' => $start->format('H:i:s'),
                'label' => $start->format('H:i'),
                'capacity' => $config->capacity,
                'duration' => $duration,
                'is_break' => false,
                'blocked' => false
            ];
            $start->addMinutes($duration);
        }
        
        return $slots;
    }
    
    /**
     * Generar slots desde configuración personalizada (excepciones)
     */
    private function generateSlotsFromCustomConfig(array $customConfig): array
    {
        $slots = [];
        
        foreach ($customConfig as $config) {
            $start = Carbon::parse($config['start_time']);
            $end = Carbon::parse($config['end_time']);
            $duration = $config['slot_duration'];
            
            while ($start < $end) {
                $slots[] = [
                    'time' => $start->format('H:i:s'),
                    'label' => $start->format('H:i'),
                    'capacity' => $config['capacity'],
                    'duration' => $duration,
                    'is_break' => false,
                    'blocked' => false,
                    'description' => $config['description'] ?? null
                ];
                $start->addMinutes($duration);
            }
        }
        
        return $slots;
    }
    
    /**
     * Aplicar pausas a los slots
     */
    private function applyBreaks(array $slots, Collection $breaks): array
    {
        if ($breaks->isEmpty()) {
            return $slots;
        }
        
        $filteredSlots = [];
        
        foreach ($slots as $slot) {
            $slotTime = Carbon::parse($slot['time']);
            $isInBreak = false;
            
            foreach ($breaks as $break) {
                $breakStart = Carbon::parse($break->break_start);
                $breakEnd = Carbon::parse($break->break_end);
                
                if ($slotTime >= $breakStart && $slotTime < $breakEnd) {
                    $isInBreak = true;
                    break;
                }
            }
            
            if (!$isInBreak) {
                $filteredSlots[] = $slot;
            }
        }
        
        return $filteredSlots;
    }
    
    /**
     * Aplicar modificaciones de horarios especiales
     */
    private function applySpecialSlots(array $slots, Collection $specialSlots): array
    {
        if ($specialSlots->isEmpty()) {
            return $slots;
        }
        
        foreach ($specialSlots as $special) {
            $specialTime = Carbon::parse($special->start_time)->format('H:i:s');
            
            foreach ($slots as $index => $slot) {
                if ($slot['time'] === $specialTime) {
                    if ($special->is_blocked) {
                        $slots[$index]['blocked'] = true;
                    } elseif ($special->capacity !== null) {
                        $slots[$index]['capacity'] = $special->capacity;
                    }
                    
                    if ($special->description) {
                        $slots[$index]['special_description'] = $special->description;
                    }
                }
            }
        }
        
        return $slots;
    }
    
    /**
     * Obtener ocupación actual de los slots
     */
    private function getCurrentOccupancy(string $date, string $testType, int $languageId): array
    {
        $assignments = QuizAssignment::whereDate('start_at', $date)
            ->where('test_type', $testType)
            ->where('language_id', $languageId)
            ->where('active', 1)
            ->get();
        
        $occupancy = [];
        
        foreach ($assignments as $assignment) {
            $time = Carbon::parse($assignment->start_at)->format('H:i:s');
            $occupancy[$time] = ($occupancy[$time] ?? 0) + 1;
        }
        
        return $occupancy;
    }
    
    /**
     * Calcular disponibilidad final de los slots
     */
    private function calculateAvailability(array $slots, array $occupancy, bool $isToday, Carbon $now): array
    {
        $currentTimeMinutes = $now->hour * 60 + $now->minute;
        
        foreach ($slots as &$slot) {
            $occupied = $occupancy[$slot['time']] ?? 0;
            $slot['occupied'] = $occupied;
            $slot['available'] = $slot['capacity'] - $occupied;
            
            // Calcular si está en el pasado
            if ($isToday) {
                [$hour, $minute] = explode(':', $slot['time']);
                $slotTimeMinutes = (int)$hour * 60 + (int)$minute;
                $slot['is_past'] = $slotTimeMinutes <= $currentTimeMinutes - 5;
            } else {
                $slot['is_past'] = false;
            }
            
            // Determinar si está disponible
            $slot['is_available'] = !$slot['blocked'] && 
                                    !$slot['is_past'] && 
                                    $slot['available'] > 0;
        }
        
        return $slots;
    }
    
    /**
     * Guardar configuración completa
     */
    public function saveConfiguration(string $testType, array $configs, array $breaks, array $specialSlots): void
    {
        // Guardar configuraciones base
        foreach ($configs as $config) {
            TimeSlotConfig::updateOrCreate(
                [
                    'test_type' => $testType,
                    'day_of_week' => $config['day_of_week']
                ],
                [
                    'start_time' => $config['start_time'],
                    'end_time' => $config['end_time'],
                    'slot_duration' => $config['slot_duration'],
                    'capacity' => $config['capacity'],
                    'is_active' => $config['is_active']
                ]
            );
        }
        
        // Guardar pausas
        TimeSlotBreak::where('test_type', $testType)->delete();
        foreach ($breaks as $dayOfWeek => $dayBreaks) {
            foreach ($dayBreaks as $break) {
                if (!empty($break['break_start']) && !empty($break['break_end'])) {
                    TimeSlotBreak::create([
                        'test_type' => $testType,
                        'day_of_week' => $dayOfWeek,
                        'break_start' => $break['break_start'],
                        'break_end' => $break['break_end'],
                        'description' => $break['description'] ?? null,
                        'is_active' => true
                    ]);
                }
            }
        }
        
        // Guardar horarios especiales
        SpecialSlot::where('test_type', $testType)->delete();
        foreach ($specialSlots as $slot) {
            if (!empty($slot['date']) && !empty($slot['start_time'])) {
                SpecialSlot::create([
                    'date' => $slot['date'],
                    'test_type' => $testType,
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'capacity' => $slot['capacity'] ?? null,
                    'is_blocked' => $slot['is_blocked'] ?? false,
                    'description' => $slot['description'] ?? null
                ]);
            }
        }
    }
}