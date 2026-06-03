<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\QuizAssignment;
use App\Models\User;
use App\Models\AuditLog;
use App\Notifications\MissedExamNotification;
use App\Notifications\CustomNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminLabController extends Controller
{
    /**
     * Listar asignaciones pendientes de habilitación
     */
    public function getPendingUnlocks(Request $request)
    {
        $date = $request->query('date', now()->format('Y-m-d'));

        $results = DB::table('quiz_assignments as qa')
            ->join('users as u', 'qa.student_id', '=', 'u.id')
            ->leftJoin('oral_results as or', 'u.id', '=', 'or.student_id')
            ->select(
                'u.id as student_id',
                DB::raw("CONCAT(u.name, ' ', u.lastname) as name"),
                DB::raw('COALESCE(or.final_level, "S/N") as level'),
                DB::raw('COALESCE(or.final_score, 0) as oral_score'),
                'qa.is_unlocked',
                'qa.id as assignment_id',
                'qa.start_at',
                'qa.test_type',
                'qa.active',
                'qa.attended',
                'u.penalty_until'
            )
            ->whereDate('qa.start_at', $date)
            ->get();

        return response()->json($results);
    }

    /**
     * Verificar elegibilidad del estudiante para nueva asignación
     */
    public function checkStudentEligibility($studentId, $excludeAssignmentId = null)
    {
        try {
            $query = DB::table('quiz_assignments')
                ->where('student_id', $studentId)
                ->where('active', 1);

            if ($excludeAssignmentId) {
                $query->where('id', '!=', $excludeAssignmentId);
            }

            $activeAssignment = $query->first();

            if ($activeAssignment) {
                $examDate = Carbon::parse($activeAssignment->start_at);
                return response()->json([
                    'eligible' => false,
                    'reason' => "El estudiante YA TIENE una cita pendiente para el " . $examDate->format('d/m/Y H:i')
                ]);
            }

            $user = DB::table('users')->where('id', $studentId)->first();

            if ($user && $user->penalty_until && Carbon::parse($user->penalty_until) > now()) {
                $daysLeft = Carbon::now()->diffInDays(Carbon::parse($user->penalty_until));
                return response()->json([
                    'eligible' => false,
                    'reason' => "El estudiante está sancionado. Días restantes: {$daysLeft}. Fecha de habilitación: " . Carbon::parse($user->penalty_until)->format('d/m/Y')
                ]);
            }

            return response()->json([
                'eligible' => true,
                'reason' => ''
            ]);

        } catch (\Exception $e) {
            Log::error("Error en checkStudentEligibility: " . $e->getMessage());
            return response()->json([
                'eligible' => true,
                'reason' => ''
            ]);
        }
    }

    /**
     * Obtener horarios disponibles
     */
    public function getAvailableSchedules(Request $request)
    {
        try {
            $date = $request->query('date', now()->format('Y-m-d'));
            $testType = $request->query('test_type');
            $languageId = $request->query('language_id', 1);

            if ($date < now()->format('Y-m-d')) {
                return response()->json(['error' => 'No se pueden modificar fechas pasadas'], 400);
            }

            if (!in_array($testType, ['OralTest', 'CompTest', 'ModularTest'])) {
                return response()->json(['error' => 'Tipo de examen no válido'], 422);
            }

            // Obtener configuración de horarios desde la tabla time_slots_config
            $config = DB::table('time_slots_config')->first();
            
            if (!$config) {
                // Configuración por defecto
                $startHour = 8;
                $endHour = 18;
                $intervalMinutes = ($testType === 'OralTest') ? 30 : 60;
                $capacity = ($testType === 'OralTest') ? 1 : 4;
            } else {
                $startHour = $config->start_hour ?? 8;
                $endHour = $config->end_hour ?? 18;
                $intervalMinutes = ($testType === 'OralTest') ? ($config->oral_interval ?? 30) : ($config->comp_interval ?? 60);
                $capacity = ($testType === 'OralTest') ? ($config->oral_capacity ?? 1) : ($config->comp_capacity ?? 4);
            }

            // Obtener asignaciones existentes para esta fecha
            $existingAssignments = DB::table('quiz_assignments')
                ->whereDate('start_at', $date)
                ->where('test_type', $testType)
                ->where('active', 1)
                ->get();

            $now = Carbon::now();
            $isToday = ($date == $now->format('Y-m-d'));

            $availableSlots = [];
            $startTime = Carbon::createFromTime($startHour, 0, 0);
            $endTime = Carbon::createFromTime($endHour, 0, 0);

            while ($startTime < $endTime) {
                $timeString = $startTime->format('H:i:s');
                $label = $startTime->format('H:i');

                // Verificar si es horario pasado
                $isPastTime = false;
                if ($isToday) {
                    $isPastTime = $startTime->copy()->addMinutes($intervalMinutes)->lt($now);
                }

                // Contar ocupados en este horario
                $occupiedCount = 0;
                foreach ($existingAssignments as $assignment) {
                    $assignmentTime = Carbon::parse($assignment->start_at);
                    $diffInMinutes = abs($startTime->diffInMinutes($assignmentTime));
                    if ($diffInMinutes < $intervalMinutes) {
                        $occupiedCount++;
                    }
                }

                $isAvailable = !$isPastTime && $occupiedCount < $capacity;

                $availableSlots[] = [
                    'time' => $timeString,
                    'label' => $label,
                    'capacity' => $capacity,
                    'occupied' => $occupiedCount,
                    'available' => $capacity - $occupiedCount,
                    'is_available' => $isAvailable,
                    'is_past' => $isPastTime,
                    'blocked' => false
                ];

                $startTime->addMinutes($intervalMinutes);
            }

            return response()->json([
                'status' => 'success',
                'slots' => $availableSlots
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getAvailableSchedules: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Validar si un horario específico está disponible
     */
    public function validateTimeSlot($date, $time, $assignmentId = null)
    {
        $newTime = Carbon::parse($date . ' ' . $time);

        $query = DB::table('quiz_assignments')
            ->whereDate('start_at', $date)
            ->where('active', 1);

        if ($assignmentId) {
            $query->where('id', '!=', $assignmentId);
        }

        $existingAssignments = $query->get();

        foreach ($existingAssignments as $assignment) {
            $existingTime = Carbon::parse($assignment->start_at);
            $diffInMinutes = abs($newTime->diffInMinutes($existingTime));

            if ($diffInMinutes < 30) {
                return [
                    'available' => false,
                    'conflict_time' => $existingTime->format('H:i'),
                    'message' => "Ya hay un examen programado a las {$existingTime->format('H:i')}. Debe haber al menos 30 minutos de diferencia."
                ];
            }
        }

        return ['available' => true];
    }

    /**
     * Activar/Desactivar desbloqueo de examen por assignment_id
     */
    public function toggleUnlockById(Request $request, $assignmentId)
    {
        try {
            $assignment = DB::table('quiz_assignments')
                ->where('id', $assignmentId)
                ->first();

            if (!$assignment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Asignación no encontrada'
                ], 404);
            }

            $isUnlocked = $request->is_unlocked ? 1 : 0;
            
            $updated = DB::table('quiz_assignments')
                ->where('id', $assignmentId)
                ->update([
                    'is_unlocked' => $isUnlocked,
                    'updated_at' => now()
                ]);

            if ($updated && $isUnlocked == 1) {
                $student = User::find($assignment->student_id);
                if ($student) {
                    $student->notify(new CustomNotification(
                        'exam_unlocked',
                        '🔓 Examen Desbloqueado',
                        "Tu examen {$assignment->test_type} ha sido desbloqueado. Ya puedes comenzar.",
                        [
                            'assignment_id' => $assignmentId,
                            'test_type' => $assignment->test_type,
                            'start_at' => $assignment->start_at
                        ]
                    ));
                    Log::info("Notificación de desbloqueo enviada a: {$student->email}");
                }
            }

            return response()->json([
                'status' => $updated ? 'success' : 'error',
                'message' => $updated ? 'Estado actualizado' : 'No se pudo actualizar'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar fecha/hora de asignación
     */
    public function updateAssignmentDateTime(Request $request, $assignmentId)
    {
        try {
            $request->validate([
                'start_at' => 'required|date',
                'test_type' => 'required|string'
            ]);

            $newDateTime = Carbon::parse($request->start_at);
            $date = $newDateTime->format('Y-m-d');
            $time = $newDateTime->format('H:i:s');
            $hour = (int)$newDateTime->format('H');
            $minute = (int)$newDateTime->format('i');

            $currentAssignment = DB::table('quiz_assignments')
                ->where('id', $assignmentId)
                ->first();

            if (!$currentAssignment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Asignación no encontrada'
                ], 404);
            }

            if ($request->test_type === 'CompTest') {
                $oralPassed = DB::table('quiz_assignments')
                    ->where('student_id', $currentAssignment->student_id)
                    ->where('test_type', 'OralTest')
                    ->where('passed', 1)
                    ->exists();

                if (!$oralPassed) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No se puede cambiar a CompTest. El estudiante debe aprobar el examen Oral primero.'
                    ], 400);
                }
            }

            if ($date < now()->format('Y-m-d')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se puede asignar una fecha pasada'
                ], 400);
            }

            if ($date == now()->format('Y-m-d')) {
                $currentHour = (int)now()->format('H');
                $currentMinute = (int)now()->format('i');
                $currentTimeInMinutes = $currentHour * 60 + $currentMinute;
                $selectedTimeInMinutes = $hour * 60 + $minute;

                if ($selectedTimeInMinutes <= $currentTimeInMinutes + 5) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No se puede asignar un horario pasado o muy cercano a la hora actual.'
                    ], 400);
                }
            }

            if ($hour < 8 || $hour >= 18) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El horario debe estar entre 08:00 y 18:00'
                ], 400);
            }

            $validation = $this->validateTimeSlot($date, $time, $assignmentId);

            if (!$validation['available']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validation['message']
                ], 400);
            }

            $maxCapacity = ($request->test_type === 'OralTest') ? 1 : 4;
            $occupied = DB::table('quiz_assignments')
                ->where('id', '!=', $assignmentId)
                ->where('test_type', $request->test_type)
                ->whereDate('start_at', $date)
                ->whereTime('start_at', $time)
                ->where('active', 1)
                ->count();

            if ($occupied >= $maxCapacity) {
                return response()->json([
                    'status' => 'error',
                    'message' => "No hay cupos disponibles para {$request->test_type} a las {$newDateTime->format('H:i')}. Capacidad máxima: {$maxCapacity}"
                ], 400);
            }

            $updated = DB::table('quiz_assignments')
                ->where('id', $assignmentId)
                ->update([
                    'start_at' => $newDateTime,
                    'test_type' => $request->test_type,
                    'updated_at' => now()
                ]);

            if ($updated) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Fecha y hora actualizadas correctamente'
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo actualizar'
            ], 400);

        } catch (\Exception $e) {
            Log::error("Error en updateAssignmentDateTime: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar como inasistencia y aplicar sanción de 14 días
     */
    public function markAsMissed($studentId)
    {
        try {
            $assignment = QuizAssignment::where('student_id', $studentId)
                ->where('active', 1)
                ->where('attended', 0)
                ->first();

            if (!$assignment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Este estudiante no puede ser sancionado'
                ], 400);
            }

            $student = User::find($studentId);

            if (!$student) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Estudiante no encontrado'
                ], 404);
            }

            DB::transaction(function () use ($assignment, $student) {
                $assignment->update([
                    'active' => 0,
                    'attended' => 2,
                    'updated_at' => now(),
                    'notification_sent' => true
                ]);

                $student->update([
                    'penalty_until' => now()->addDays(14)
                ]);

                try {
                    $student->notify(new MissedExamNotification($assignment, $student, 14));
                    Log::info('Notificación enviada a: ' . $student->email);
                } catch (\Exception $e) {
                    Log::error('Error enviando notificación: ' . $e->getMessage());
                }
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Sanción aplicada por 14 días y notificación enviada'
            ]);

        } catch (\Exception $e) {
            Log::error('Error en markAsMissed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo aplicar la sanción: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar penalización activa
     */
    public function removePenalty($studentId)
    {
        try {
            $user = DB::table('users')->where('id', $studentId)->first();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Estudiante no encontrado'
                ], 404);
            }

            if (!$user->penalty_until || $user->penalty_until <= now()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Este estudiante no tiene una sanción activa'
                ], 400);
            }

            DB::transaction(function () use ($studentId) {
                DB::table('users')
                    ->where('id', $studentId)
                    ->update([
                        'penalty_until' => null,
                        'updated_at' => now()
                    ]);

                DB::table('quiz_assignments')
                    ->where('student_id', $studentId)
                    ->where('attended', 2)
                    ->update([
                        'active' => 1,
                        'attended' => 0,
                        'is_unlocked' => 0,
                        'updated_at' => now()
                    ]);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Sanción eliminada y asignación reactivada correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error("Error en removePenalty: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo eliminar la sanción'
            ], 500);
        }
    }

    /**
     * Limpiar registro de inasistencia y reactivar asignación
     */
    public function clearMissed($studentId)
    {
        try {
            DB::transaction(function () use ($studentId) {
                DB::table('quiz_assignments')
                    ->where('student_id', $studentId)
                    ->where('attended', 2)
                    ->update([
                        'active' => 1,
                        'attended' => 0,
                        'is_unlocked' => 0,
                        'updated_at' => now()
                    ]);

                DB::table('users')
                    ->where('id', $studentId)
                    ->where('penalty_until', '>', now())
                    ->update([
                        'penalty_until' => null,
                        'updated_at' => now()
                    ]);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Asignación reactivada correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error("Error en clearMissed: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo reactivar la asignación'
            ], 500);
        }
    }

    /**
     * Crear nueva asignación (desde el panel de administrador)
     */
    public function createAssignment(Request $request)
    {
        try {
            // Validaciones básicas
            $request->validate([
                'student_id' => 'required|exists:users,id',
                'start_at' => 'required|date',
                'test_type' => 'required|in:OralTest,CompTest,ModularTest',
                'language_id' => 'required|exists:languages,id'
            ]);

            $studentId = $request->student_id;
            $newDateTime = Carbon::parse($request->start_at);
            $date = $newDateTime->format('Y-m-d');
            $time = $newDateTime->format('H:i:s');
            $hour = (int)$newDateTime->format('H');
            $minute = (int)$newDateTime->format('i');

            // ========== VALIDACIÓN PARA COMP TEST ==========
            if ($request->test_type === 'CompTest') {
                $hasCompQuiz = DB::table('quizzes')
                    ->where('type', 'normal')
                    ->where('language_id', $request->language_id)
                    ->exists();
                
                if (!$hasCompQuiz) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No hay exámenes computarizados disponibles en este idioma.'
                    ], 400);
                }
            }

            // ========== VALIDACIÓN PARA MODULAR TEST (CORREGIDA) ==========
            if ($request->test_type === 'ModularTest') {
                // Verificar que existan MÓDULOS (NO quizzes)
                $hasModules = DB::table('modules')
                    ->where('language_id', $request->language_id)
                    ->exists();
                
                if (!$hasModules) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No hay módulos disponibles para este idioma.'
                    ], 400);
                }
                
                // Verificar niveles requeridos
                $requiredLevels = ['A1', 'A2', 'B1', 'B2'];
                $missingLevels = [];
                
                foreach ($requiredLevels as $level) {
                    $hasLevel = DB::table('modules')
                        ->where('language_id', $request->language_id)
                        ->where('level', $level)
                        ->exists();
                    
                    if (!$hasLevel) {
                        $missingLevels[] = $level;
                    }
                }
                
                if (!empty($missingLevels)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Faltan módulos para los niveles: ' . implode(', ', $missingLevels) . '. Contacte al administrador.'
                    ], 400);
                }
            }

            // Validación de fecha
            if ($date < now()->format('Y-m-d')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se puede asignar una fecha pasada'
                ], 400);
            }

            // Validación de horario (8:00 a 18:00)
            if ($hour < 8 || $hour >= 18) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El horario debe estar entre 08:00 y 18:00'
                ], 400);
            }

            // Verificar si el estudiante ya tiene cita activa
            $existingActiveAssignment = DB::table('quiz_assignments')
                ->where('student_id', $studentId)
                ->where('active', 1)
                ->first();

            if ($existingActiveAssignment) {
                $examDate = Carbon::parse($existingActiveAssignment->start_at);
                return response()->json([
                    'status' => 'error',
                    'message' => "El estudiante YA TIENE una cita pendiente para el " . $examDate->format('d/m/Y H:i')
                ], 400);
            }

            // Verificar sanción activa
            $user = DB::table('users')->where('id', $studentId)->first();

            if ($user && $user->penalty_until && Carbon::parse($user->penalty_until) > now()) {
                $daysLeft = Carbon::now()->diffInDays(Carbon::parse($user->penalty_until));
                return response()->json([
                    'status' => 'error',
                    'message' => "El estudiante está sancionado. Días restantes: {$daysLeft}"
                ], 400);
            }

            // Prerrequisito Oral para CompTest y ModularTest
            if (in_array($request->test_type, ['CompTest', 'ModularTest'])) {
                $oralPassed = DB::table('quiz_assignments')
                    ->where('student_id', $studentId)
                    ->where('test_type', 'OralTest')
                    ->where('passed', 1)
                    ->exists();

                if (!$oralPassed) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'El estudiante debe aprobar el examen Oral antes de programar este examen.'
                    ], 400);
                }
            }

            // ========== VALIDACIÓN DE CAPACIDAD ==========
            $maxCapacity = ($request->test_type === 'OralTest') ? 1 : 4;
            
            $startTime = $newDateTime->copy()->startOfHour();
            $endTime = $startTime->copy()->addHour();
            
            if ($request->test_type === 'OralTest') {
                if ($minute >= 30) {
                    $startTime = $newDateTime->copy()->startOfHour()->addMinutes(30);
                    $endTime = $startTime->copy()->addMinutes(30);
                } else {
                    $startTime = $newDateTime->copy()->startOfHour();
                    $endTime = $startTime->copy()->addMinutes(30);
                }
            }
            
            $occupied = DB::table('quiz_assignments')
                ->whereDate('start_at', $date)
                ->where('start_at', '>=', $startTime)
                ->where('start_at', '<', $endTime)
                ->where('test_type', $request->test_type)
                ->where('active', 1)
                ->count();

            if ($occupied >= $maxCapacity) {
                $timeDisplay = $startTime->format('H:i');
                return response()->json([
                    'status' => 'error',
                    'message' => "No hay cupos disponibles para {$request->test_type} a las {$timeDisplay}. Capacidad: {$maxCapacity}, Ocupados: {$occupied}"
                ], 400);
            }

            // ========== CREAR LA ASIGNACIÓN ==========
            $assignmentData = [
                'student_id' => $studentId,
                'start_at' => $newDateTime,
                'test_type' => $request->test_type,
                'language_id' => $request->language_id,
                'active' => 1,
                'attended' => 0,
                'is_unlocked' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ];

            $assignmentId = DB::table('quiz_assignments')->insertGetId($assignmentData);

            // ========== CREAR LOG DE AUDITORÍA ==========
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'Creación de asignación',
                'entity_type' => 'quiz_assignment',
                'entity_id' => $assignmentId,
                'new_data' => json_encode($assignmentData),
                'severity' => 'info'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Asignación creada correctamente',
                'assignment_id' => $assignmentId,
                'occupied_count' => $occupied + 1,
                'remaining_capacity' => $maxCapacity - ($occupied + 1)
            ]);

        } catch (\Exception $e) {
            \Log::error("Error en createAssignment: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}