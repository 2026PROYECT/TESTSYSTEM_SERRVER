<?php
// app/Http/Controllers/Api/V1/TimeSlotController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TimeSlotService;
use App\Models\TimeSlotConfig;
use App\Models\TimeSlotBreak;
use App\Models\SpecialSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TimeSlotController extends Controller
{
    protected $timeSlotService;

    public function __construct(TimeSlotService $timeSlotService)
    {
        $this->timeSlotService = $timeSlotService;
    }

    /**
     * Obtener slots disponibles para una fecha
     * GET /api/v1/admin/get-available-slots
     */
    public function getAvailableSlots(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'test_type' => 'required|in:OralTest,CompTest',
            'language_id' => 'required|exists:languages,id'
        ]);

        $slots = $this->timeSlotService->getAvailableSlots(
            $request->date,
            $request->test_type,
            $request->language_id
        );

        return response()->json([
            'status' => 'success',
            'slots' => $slots
        ]);
    }

    /**
     * Obtener toda la configuración actual
     * GET /api/v1/admin/time-slots-config
     */
    public function getConfiguration(Request $request)
    {
        $request->validate([
            'test_type' => 'required|in:OralTest,CompTest'
        ]);

        $configs = TimeSlotConfig::where('test_type', $request->test_type)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
            
        $breaks = TimeSlotBreak::where('test_type', $request->test_type)
            ->where('is_active', true)
            ->get();
            
        $specialSlots = SpecialSlot::where('test_type', $request->test_type)
            ->where('date', '>=', now()->startOfMonth())
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'status' => 'success',
            'configs' => $configs,
            'breaks' => $breaks,
            'special_slots' => $specialSlots
        ]);
    }

    /**
     * Guardar configuración de horarios
     * POST /api/v1/admin/time-slots-config
     */
    public function saveConfiguration(Request $request)
    {
        try {
            Log::info('saveConfiguration llamado', $request->all());
            
            // Validación con campos opcionales (CORREGIDO)
            $validated = $request->validate([
                'test_type' => 'required|in:OralTest,CompTest',
                'configs' => 'sometimes|array',
                'configs.*.day_of_week' => 'sometimes|required|integer|between:0,6',
                'configs.*.start_time' => 'sometimes|required|date_format:H:i',
                'configs.*.end_time' => 'sometimes|required|date_format:H:i',
                'configs.*.slot_duration' => 'sometimes|required|integer|min:15',
                'configs.*.capacity' => 'sometimes|required|integer|min:1',
                'configs.*.is_active' => 'sometimes|boolean',
                'breaks' => 'sometimes|array',
                'special_slots' => 'sometimes|array'
            ]);

            DB::beginTransaction();
            
            // Caso 1: Guardar configuración normal (desde TimeSlotManager)
            if ($request->has('configs') && !empty($request->configs)) {
                $this->timeSlotService->saveConfiguration(
                    $request->test_type,
                    $request->configs,
                    $request->breaks ?? [],
                    []
                );
            }
            
            // Caso 2: Guardar horarios especiales (desde SpecialSlotManager)
            if ($request->has('special_slots') && !empty($request->special_slots)) {
                foreach ($request->special_slots as $slot) {
                    if (empty($slot['date']) || empty($slot['start_time'])) {
                        continue;
                    }
                    
                    $isBlocked = $slot['is_blocked'] ?? false;
                    
                    SpecialSlot::updateOrCreate(
                        [
                            'date' => $slot['date'],
                            'test_type' => $request->test_type,
                            'start_time' => $slot['start_time'] . ':00'
                        ],
                        [
                            'end_time' => ($slot['end_time'] ?? $slot['start_time']) . ':00',
                            'capacity' => $isBlocked ? null : ($slot['capacity'] ?? 4),
                            'is_blocked' => $isBlocked,
                            'description' => $slot['description'] ?? null
                        ]
                    );
                }
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Configuración guardada correctamente'
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en saveConfiguration: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error al guardar la configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener horarios especiales para un rango de fechas
     * GET /api/v1/admin/special-slots
     */
    public function getSpecialSlots(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'test_type' => 'nullable|in:OralTest,CompTest'
        ]);

        $query = SpecialSlot::whereBetween('date', [$request->start_date, $request->end_date]);
        
        if ($request->has('test_type')) {
            $query->where('test_type', $request->test_type);
        }
        
        $specialSlots = $query->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'status' => 'success',
            'special_slots' => $specialSlots
        ]);
    }

    /**
     * Eliminar un horario especial
     * DELETE /api/v1/admin/special-slots/{id}
     */
    public function deleteSpecialSlot($id)
    {
        try {
            $specialSlot = SpecialSlot::findOrFail($id);
            $specialSlot->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Horario especial eliminado correctamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar el horario especial'
            ], 500);
        }
    }
}