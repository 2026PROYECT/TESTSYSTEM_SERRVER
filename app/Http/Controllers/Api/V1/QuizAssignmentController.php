<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\QuizAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class QuizAssignmentController extends Controller
{
    /**
     * 1. Consolidated Data for Admin Dashboard
     */
    public function getConsolidatedData()
    {
        return User::where('role', 'student')
            ->with(['student.career'])
            ->get()
            ->map(function ($user) {
                $oralTests = $user->quizAssignments->where('test_type', 'OralTest');
                $compTests = $user->quizAssignments->where('test_type', 'CompTest');
                $modularTests = $user->quizAssignments->where('test_type', 'ModularTest');

                $latestOral = $oralTests->sortByDesc('created_at')->first();
                $latestComp = $compTests->sortByDesc('created_at')->first();
                $latestModular = $modularTests->sortByDesc('created_at')->first();

                $status = 'pending';
                if ($latestComp && $latestComp->passed == 1) {
                    $status = 'certified';
                } elseif ($latestModular && $latestModular->passed == 1) {
                    $status = 'certified';
                } elseif ($latestOral && $latestOral->passed == 1) {
                    $status = 'oral_passed';
                }

                return [
                    'id'            => $user->id,
                    'full_name'     => trim(($user->lastname ?? '') . ' ' . ($user->name ?? '')),
                    'career'        => $user->student?->career?->name ?? 'N/A',
                    'oral_passed'   => $latestOral?->passed ?? 0,
                    'comp_passed'   => $latestComp?->passed ?? 0,
                    'modular_passed'=> $latestModular?->passed ?? 0,
                    'status'        => $status,
                    'is_certified'  => in_array($status, ['certified'])
                ];
            });
    }

    /**
     * 2. Lista de asignaciones
     */
    public function index(Request $request)
    {
        try {
            $query = QuizAssignment::with(['student', 'language']);
            
            if ($request->has('student_id')) {
                $query->where('student_id', $request->student_id);
            }
            
            if ($request->has('language_id')) {
                $query->where('language_id', $request->language_id);
            }
            
            $assignments = $query->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $assignments
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error en quiz-assignments index: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 3. Mostrar una asignación específica
     */
    public function show($id)
    {
        try {
            $assignment = QuizAssignment::with('student')->findOrFail($id);
            
            return response()->json([
                'data' => $assignment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Asignación no encontrada'
            ], 404);
        }
    }

    /**
     * 4. Crear autorización
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id'  => 'required|exists:users,id',
            'test_type'   => 'required|in:OralTest,CompTest,ModularTest',
            'start_at'    => 'required|date|after:now',
            'active'      => 'required|boolean',
            'language_id' => 'required|integer' 
        ]);

        return DB::transaction(function () use ($validated) {
            
            $isCertified = QuizAssignment::where('student_id', $validated['student_id'])
                ->whereIn('test_type', ['CompTest', 'ModularTest'])
                ->where('passed', 1)
                ->exists();

            if ($isCertified) {
                return response()->json([
                    'errors' => [
                        'student_id' => ['El estudiante ya aprobó el examen final y no requiere nuevas autorizaciones.']
                    ]
                ], 422);
            }

            if (in_array($validated['test_type'], ['CompTest', 'ModularTest'])) {
                $hasOralPassed = QuizAssignment::where('student_id', $validated['student_id'])
                    ->where('test_type', 'OralTest')
                    ->where('passed', 1)
                    ->exists();
                
                if (!$hasOralPassed) {
                    return response()->json([
                        'errors' => [
                            'test_type' => ['El estudiante debe aprobar el examen Oral primero.']
                        ]
                    ], 422);
                }
            }

            $start = Carbon::parse($validated['start_at']);
            $duration = ($validated['test_type'] === 'OralTest') ? 30 : 70;
            $end = $start->copy()->addMinutes($duration);

            $conflict = QuizAssignment::where('language_id', $validated['language_id'])
                ->where('test_type', $validated['test_type'])
                ->where('active', 1)
                ->where(function ($q) use ($start, $end) {
                    $q->where('start_at', '<', $end)
                      ->whereRaw('DATE_ADD(start_at, INTERVAL (CASE WHEN test_type = "OralTest" THEN 30 ELSE 70 END) MINUTE) > ?', [$start->toDateTimeString()]);
                })
                ->first();

            if ($conflict) {
                $horaOcupada = Carbon::parse($conflict->start_at)->format('H:i');
                return response()->json([
                    'errors' => [
                        'start_at' => ["El horario está ocupado por una sesión a las $horaOcupada."]
                    ]
                ], 422);
            }

            QuizAssignment::where('student_id', $validated['student_id'])
                ->where('test_type', $validated['test_type'])
                ->where('active', true)
                ->update(['active' => false]);

            $assignment = QuizAssignment::create($validated);
            
            return response()->json(['data' => $assignment], 201);
        });
    }

    /**
     * 5. ACTUALIZAR asignación - Con validación de passed y fechas pasadas
     */
    public function update(Request $request, $id)
    {
        $assignment = QuizAssignment::findOrFail($id);
        
        // VALIDACIÓN 1: No editar si está aprobado
        if ($assignment->passed == 1) {
            return response()->json([
                'success' => false,
                'message' => '❌ No se puede modificar un examen ya aprobado.',
                'can_edit' => false
            ], 422);
        }
        
        $validated = $request->validate([
            'student_id'  => 'sometimes|exists:users,id',
            'test_type'   => 'sometimes|in:OralTest,CompTest,ModularTest',
            'start_at'    => 'sometimes|date',
            'active'      => 'sometimes|boolean',
            'language_id' => 'sometimes|integer' 
        ]);

        // VALIDACIÓN 2: Si la fecha actual es pasada, forzar cambio a fecha futura
        if (isset($assignment->start_at)) {
            $currentStartAt = Carbon::parse($assignment->start_at);
            if ($currentStartAt->isPast() && !$assignment->attended) {
                if (!isset($validated['start_at'])) {
                    return response()->json([
                        'success' => false,
                        'message' => '⚠️ La fecha del examen ya pasó y no fue atendido. Debes seleccionar una nueva fecha futura.',
                        'requires_new_date' => true
                    ], 422);
                }
                
                $newStartAt = Carbon::parse($validated['start_at']);
                if (!$newStartAt->isFuture()) {
                    return response()->json([
                        'success' => false,
                        'message' => '⚠️ Debes seleccionar una fecha y hora FUTURA para reprogramar el examen.'
                    ], 422);
                }
            }
        }

        // Validación de colisión
        if (isset($validated['start_at'])) {
            $start = Carbon::parse($validated['start_at']);
            $testType = $validated['test_type'] ?? $assignment->test_type;
            $duration = ($testType === 'OralTest') ? 30 : 70;
            $end = $start->copy()->addMinutes($duration);
            $languageId = $validated['language_id'] ?? $assignment->language_id;

            $conflict = QuizAssignment::where('language_id', $languageId)
                ->where('test_type', $testType)
                ->where('id', '!=', $id)
                ->where('active', 1)
                ->where(function ($q) use ($start, $end) {
                    $q->where('start_at', '<', $end)
                      ->whereRaw('DATE_ADD(start_at, INTERVAL (CASE WHEN test_type = "OralTest" THEN 30 ELSE 70 END) MINUTE) > ?', [$start->toDateTimeString()]);
                })
                ->first();

            if ($conflict) {
                $hora = Carbon::parse($conflict->start_at)->format('H:i');
                return response()->json([
                    'success' => false,
                    'message' => "Conflicto de horario con examen programado a las {$hora}."
                ], 422);
            }
        }

        $assignment->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Asignación actualizada correctamente',
            'data' => $assignment
        ]);
    }

    /**
     * 6. Verificar elegibilidad para editar
     */
    public function checkEditEligibility($studentId, $assignmentId)
    {
        try {
            $assignment = QuizAssignment::findOrFail($assignmentId);
            
            // Caso 1: Ya está aprobado
            if ($assignment->passed == 1) {
                return response()->json([
                    'eligible' => false,
                    'can_edit' => false,
                    'reason' => ($assignment->test_type === 'OralTest') ? 'examen_oral_aprobado' : 'curso_completado',
                    'can_create_next' => ($assignment->test_type === 'OralTest'),
                    'message' => ($assignment->test_type === 'OralTest') 
                        ? '✅ Este examen oral ya está APROBADO. Debes crear la Fase 2 (CompTest o ModularTest).'
                        : '🏆 ¡Curso completado! El estudiante ya aprobó todas las fases.'
                ]);
            }
            
            // Caso 2: Fecha pasada y no atendido - PERMITIR edición con advertencia
            $warning = null;
            if ($assignment->start_at && Carbon::parse($assignment->start_at)->isPast() && !$assignment->attended) {
                $warning = '⚠️ La fecha del examen ya pasó y no fue atendido. Debes reprogramarlo a una fecha futura.';
            }
            
            return response()->json([
                'eligible' => true,
                'can_edit' => true,
                'warning' => $warning,
                'message' => $warning ?? 'Puede editar esta asignación'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'eligible' => false,
                'can_edit' => false,
                'message' => 'Error al verificar elegibilidad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 7. Crear siguiente fase después de OralTest aprobado
     */
   /**
 * Crear siguiente fase o redirigir a la existente
 */
public function createNextPhase($oralTestId)
{
    try {
        DB::beginTransaction();
        
        $oralTest = QuizAssignment::findOrFail($oralTestId);
        
        // Validar que sea OralTest
        if ($oralTest->test_type !== 'OralTest') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se puede crear siguiente fase desde un examen oral'
            ], 422);
        }
        
        // Validar que esté aprobado
        if ($oralTest->passed !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'El examen oral debe estar aprobado para crear la siguiente fase'
            ], 422);
        }
        
        // 🔥 BUSCAR SI YA EXISTE UNA FASE 2
        $existingPhase2 = QuizAssignment::where('student_id', $oralTest->student_id)
            ->where('language_id', $oralTest->language_id)
            ->whereIn('test_type', ['CompTest', 'ModularTest'])
            ->first();
            
        // ✅ Si ya existe, redirigir a editar esa fase
        if ($existingPhase2) {
            DB::commit();
            return response()->json([
                'success' => true,
                'already_exists' => true,
                'data' => $existingPhase2,
                'message' => 'Ya existe una fase 2 para este estudiante',
                'redirect_to' => $existingPhase2->id
            ]);
        }
        
        // 🆕 Si no existe, crear una nueva fase 2 aleatoria
        $phase2Types = ['CompTest', 'ModularTest'];
        $randomType = $phase2Types[array_rand($phase2Types)];
        
        $newAssignment = QuizAssignment::create([
            'language_id' => $oralTest->language_id,
            'student_id' => $oralTest->student_id,
            'test_type' => $randomType,
            'active' => 0,
            'passed' => 0,
            'attended' => 0,
            'start_at' => null,
            'is_unlocked' => 0,
            'notification_sent' => 0
        ]);
        
        DB::commit();
        
        return response()->json([
            'success' => true,
            'already_exists' => false,
            'data' => $newAssignment,
            'random_type' => $randomType,
            'message' => "Fase 2 creada exitosamente: {$randomType}"
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}
    /**
     * 8. Bloqueo Masivo
     */
    public function bulkDisable(Request $request)
    {
        try {
            $typeToLock = $request->input('test_type');
            $languageId = $request->input('language_id');
            
            $query = QuizAssignment::query();
            
            if ($typeToLock && $typeToLock !== 'all') {
                $query->where('test_type', $typeToLock);
            }
            
            if ($languageId) {
                $query->where('language_id', $languageId);
            }
            
            $count = $query->update(['is_unlocked' => 0]);
            
            return response()->json([
                'success' => true,
                'message' => "Se han bloqueado {$count} accesos correctamente",
                'count' => $count
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al bloquear los accesos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 9. Eliminación Masiva
     */
    public function bulkDelete(Request $request)
    {
        try {
            $query = QuizAssignment::where('language_id', $request->language_id);

            if ($request->has('test_type') && $request->test_type !== 'all') {
                $query->where('test_type', $request->test_type);
            }

            $deletedCount = $query->delete();

            return response()->json([
                'message' => "Se han eliminado $deletedCount registros con éxito."
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo vaciar la lista',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 10. Exportar PDF
     */
    public function exportPdf(Request $request)
    {
        $languageId = $request->query('language_id');

        $assignments = QuizAssignment::where('language_id', $languageId)
            ->with(['student']) 
            ->get();

        $pdf = Pdf::loadView('pdf.assignments', compact('assignments'));

        return $pdf->download('Reporte_Autorizaciones.pdf');
    }

    /**
     * 11. Eliminar una asignación
     */
    public function destroy($id)
    {
        try {
            $assignment = QuizAssignment::findOrFail($id);
            $assignment->delete();
            
            return response()->json([
                'message' => 'Asignación eliminada correctamente',
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la asignación: ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}