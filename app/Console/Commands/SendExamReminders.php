<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\QuizAssignment;
use App\Models\User;
use App\Notifications\CustomNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendExamReminders extends Command
{
    protected $signature = 'exams:send-reminders 
                            {--hours=24 : Horas antes para enviar recordatorio}
                            {--test-type= : Tipo de examen (OralTest/CompTest)}';
    
    protected $description = 'Envía recordatorios por email de exámenes programados';

    public function handle()
    {
        $hoursBefore = $this->option('hours');
        $testType = $this->option('test-type');
        
        $this->info("🚀 Enviando recordatorios de exámenes ({$hoursBefore} horas antes)...");
        
        // Calcular el rango de tiempo
        $startRange = Carbon::now()->addHours($hoursBefore - 1);
        $endRange = Carbon::now()->addHours($hoursBefore + 1);
        
        $query = QuizAssignment::where('active', 1)
            ->where('attended', 0)
            ->whereBetween('start_at', [$startRange, $endRange])
            ->with('student');
        
        if ($testType) {
            $query->where('test_type', $testType);
        }
        
        $assignments = $query->get();
        
        $sent = 0;
        $errors = 0;
        
        foreach ($assignments as $assignment) {
            if (!$assignment->student) {
                $this->warn("⚠️ Estudiante no encontrado para asignación ID: {$assignment->id}");
                continue;
            }
            
            try {
                $assignment->student->notify(new CustomNotification(
                    'exam_reminder',
                    '⏰ Recordatorio de Examen',
                    "Tienes un examen {$assignment->test_type} programado para el " . Carbon::parse($assignment->start_at)->format('d/m/Y H:i'),
                    [
                        'assignment_id' => $assignment->id,
                        'test_type' => $assignment->test_type,
                        'start_at' => $assignment->start_at,
                        'hours_before' => $hoursBefore
                    ]
                ));
                
                $sent++;
                $this->line("   ✅ Recordatorio enviado a: {$assignment->student->email}");
                
                // Marcar que se envió recordatorio
                $assignment->update(['reminder_sent_at' => now()]);
                
            } catch (\Exception $e) {
                $errors++;
                Log::error("Error enviando recordatorio: " . $e->getMessage());
                $this->error("   ❌ Error: {$assignment->student->email}");
            }
        }
        
        $this->info("✅ Recordatorios enviados: {$sent}");
        if ($errors > 0) {
            $this->warn("⚠️ Errores: {$errors}");
        }
        
        return Command::SUCCESS;
    }
}