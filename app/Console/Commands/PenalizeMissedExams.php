<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PenalizeMissedExams extends Command
{
    protected $signature = 'exams:penalize-missed';
    protected $description = 'Sanciona con 14 días a quienes no asistieron hoy';

    public function handle()
    {
        $missed = DB::table('quiz_assignments')
            ->where('active', 1)
            ->where('attended', 0)
            ->whereDate('start_at', '<=', now()) 
            ->get();

        foreach ($missed as $assignment) {
            DB::transaction(function () use ($assignment) {
                // 1. Marcar como inasistencia[cite: 2]
                DB::table('quiz_assignments')
                    ->where('id', $assignment->id)
                    ->update([
                        'active' => 0,
                        'attended' => 2, // Inasistencia
                        'updated_at' => now()
                    ]);

                // 2. Aplicar bloqueo de 14 días con el nombre de columna correcto[cite: 1, 2]
                DB::table('users')
                    ->where('id', $assignment->student_id)
                    ->update([
                        'penalty_until' => now()->addDays(14),
                        'updated_at' => now()
                    ]);
            });
        }

        $this->info("Sanciones aplicadas correctamente.");
    }
}