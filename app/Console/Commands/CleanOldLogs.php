<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SecurityLog;
use App\Models\AuditLog;
use Carbon\Carbon;

class CleanOldLogs extends Command
{
    protected $signature = 'backup:clean-logs {--days=365 : Días a mantener}';
    protected $description = 'Limpia logs antiguos de seguridad y auditoría';

    public function handle()
    {
        $days = $this->option('days');
        $date = Carbon::now()->subDays($days);
        
        $this->info('🗑️ Limpiando logs anteriores a ' . $date->format('Y-m-d'));
        
        // Limpiar SecurityLogs
        $deletedSecurity = SecurityLog::where('created_at', '<', $date)->delete();
        $this->info("   🔒 SecurityLogs eliminados: {$deletedSecurity}");
        
        // Limpiar AuditLogs
        $deletedAudit = AuditLog::where('created_at', '<', $date)->delete();
        $this->info("   📋 AuditLogs eliminados: {$deletedAudit}");
        
        $this->info('✅ Limpieza completada!');
        
        return Command::SUCCESS;
    }
}