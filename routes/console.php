<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

// ============================================================
// COMANDO EXISTENTE (NO BORRAR)
// ============================================================
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// ============================================================
// SCHEDULERS PARA BACKUPS
// ============================================================

// Backup diario a las 2:00 AM
Schedule::command('backup:database --compress --keep=30')
    ->dailyAt('02:00')
    ->before(function () {
        Log::info('🚀 Iniciando backup automático');
    })
    ->after(function () {
        Log::info('✅ Backup automático completado');
    });

// Backup semanal con envío por email (los domingos a las 3:00 AM)
Schedule::command('backup:database --compress --email=admin@emi.edu.bo --keep=60')
    ->weeklyOn(7, '03:00')
    ->after(function () {
        Log::info('📧 Backup semanal completado y enviado por email');
    });

// Limpiar logs antiguos cada mes (día 1 a las 4:00 AM)
Schedule::command('backup:clean-logs --days=90')
    ->monthlyOn(1, '04:00')
    ->after(function () {
        Log::info('🧹 Logs antiguos limpiados');
    });

// ============================================================
// SCHEDULERS PARA RECORDATORIOS DE EXÁMENES
// ============================================================

// --- Recordatorios 24 horas antes (para TODOS los exámenes) ---
Schedule::command('exams:send-reminders --hours=24')
    ->hourly()
    ->after(function () {
        Log::info('⏰ Recordatorios de 24 horas enviados');
    });

// --- Recordatorios 2 horas antes (para TODOS los exámenes) ---
Schedule::command('exams:send-reminders --hours=2')
    ->everyThirtyMinutes()
    ->after(function () {
        Log::info('⏰ Recordatorios de 2 horas enviados');
    });

// --- Recordatorios específicos para EXÁMENES ORALES (diario a las 8:00 AM) ---
Schedule::command('exams:send-reminders --hours=24 --test-type=OralTest')
    ->dailyAt('08:00')
    ->after(function () {
        Log::info('🗣️ Recordatorios de exámenes ORALES enviados');
    });

// --- Recordatorios específicos para EXÁMENES COMPUTARIZADOS (diario a las 9:00 AM) ---
Schedule::command('exams:send-reminders --hours=24 --test-type=CompTest')
    ->dailyAt('08:10')
    ->after(function () {
        Log::info('💻 Recordatorios de exámenes COMPUTARIZADOS enviados');
    });

// ============================================================
// SCHEDULERS OPCIONALES (comentados por defecto)
// ============================================================

// Backup cada 6 horas
// Schedule::command('backup:database --compress --keep=7')
//     ->everySixHours()
//     ->after(function () {
//         Log::info('⚡ Backup rápido completado');
//     });

// Recordatorio 1 hora antes (para exámenes importantes)
// Schedule::command('exams:send-reminders --hours=1')
//     ->everyFifteenMinutes()
//     ->after(function () {
//         Log::info('⚠️ Recordatorios de última hora enviados');
//     });

// Limpieza semanal de backups antiguos
// Schedule::command('backup:clean-logs --days=30')
//     ->weekly()
//     ->after(function () {
//         Log::info('🧹 Limpieza semanal de backups completada');
//     });