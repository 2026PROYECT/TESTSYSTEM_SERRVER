<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseRestore extends Command
{
    protected $signature = 'backup:restore {filename? : Nombre del archivo a restaurar} 
                            {--list : Listar backups disponibles}
                            {--latest : Restaurar el backup más reciente}';

    protected $description = 'Restaura un backup de la base de datos';

    public function handle()
    {
        if ($this->option('list')) {
            return $this->listBackups();
        }
        
        $this->warn('⚠️ ADVERTENCIA: Esta acción sobrescribirá la base de datos actual.');
        
        if (!$this->confirm('¿Estás seguro de que deseas continuar?', false)) {
            $this->info('Operación cancelada.');
            return Command::SUCCESS;
        }
        
        try {
            $filename = $this->getBackupFile();
            
            if (!$filename) {
                $this->error('No se encontró ningún archivo de backup.');
                return Command::FAILURE;
            }
            
            $backupPath = storage_path("app/backups/{$filename}");
            
            if (!file_exists($backupPath)) {
                $this->error("El archivo {$filename} no existe.");
                return Command::FAILURE;
            }
            
            $this->info('📖 Leyendo archivo de backup...');
            $sql = file_get_contents($backupPath);
            
            if (empty($sql)) {
                $this->error('El archivo de backup está vacío.');
                return Command::FAILURE;
            }
            
            $this->info('🔄 Restaurando base de datos...');
            
            $sentences = explode(";\n", $sql);
            $total = count($sentences);
            $executed = 0;
            
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            foreach ($sentences as $sentence) {
                $sentence = trim($sentence);
                if (empty($sentence) || str_starts_with($sentence, '--')) {
                    continue;
                }
                
                try {
                    DB::statement($sentence);
                    $executed++;
                } catch (\Exception $e) {
                    // Ignorar errores de tablas temporales
                    if (!str_contains($e->getMessage(), 'student_test_summaries')) {
                        $this->warn("   ⚠️ " . substr($sentence, 0, 60));
                    }
                }
            }
            
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
            $this->info("✅ Restauración completada! {$executed} sentencias ejecutadas.");
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->error('❌ Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    private function listBackups()
    {
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            $this->info('No hay backups disponibles.');
            return Command::SUCCESS;
        }
        
        $files = glob($backupDir . '/*.{sql,zip}', GLOB_BRACE);
        
        if (empty($files)) {
            $this->info('No hay backups disponibles.');
            return Command::SUCCESS;
        }
        
        $rows = [];
        foreach ($files as $index => $file) {
            $rows[] = [
                $index + 1,
                basename($file),
                date('Y-m-d H:i:s', filemtime($file)),
                round(filesize($file) / 1024 / 1024, 2) . ' MB'
            ];
        }
        
        $this->table(['#', 'Archivo', 'Fecha', 'Tamaño'], $rows);
        
        return Command::SUCCESS;
    }
    
    private function getBackupFile()
    {
        if ($this->argument('filename')) {
            return $this->argument('filename');
        }
        
        if ($this->option('latest')) {
            $backupDir = storage_path('app/backups');
            $files = glob($backupDir . '/*.{sql,zip}', GLOB_BRACE);
            if (empty($files)) return null;
            usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
            return basename($files[0]);
        }
        
        $backupDir = storage_path('app/backups');
        $files = glob($backupDir . '/*.{sql,zip}', GLOB_BRACE);
        if (empty($files)) return null;
        
        $choices = array_map(fn($file) => basename($file), $files);
        return $this->choice('Selecciona un backup', $choices);
    }
}