<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DatabaseBackup extends Command
{
    protected $signature = 'backup:database 
                            {--compress : Comprimir el backup en ZIP}
                            {--email= : Enviar backup por email}
                            {--keep=7 : Días a mantener los backups}';

    protected $description = 'Realiza un backup completo de la base de datos (modo PHP puro)';

    public function handle()
    {
        $this->info('🚀 Iniciando backup de base de datos...');
        
        try {
            $dbName = config('database.connections.mysql.database');
            $date = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "backup_{$dbName}_{$date}.sql";
            $backupPath = storage_path("app/backups/{$filename}");
            
            if (!is_dir(storage_path('app/backups'))) {
                mkdir(storage_path('app/backups'), 0755, true);
            }
            
            $this->info('📀 Generando backup con PHP puro...');
            
            // Obtener todas las tablas
            $tables = DB::select('SHOW TABLES');
            
            // Obtener el nombre de la columna de tablas (puede variar)
            $tableKey = array_keys((array)$tables[0])[0];
            
            $sql = "-- Backup de {$dbName}\n";
            $sql .= "-- Fecha: " . Carbon::now()->format('Y-m-d H:i:s') . "\n";
            $sql .= "-- Generado por: EmiSystem Backup System\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            foreach ($tables as $table) {
                $tableName = $table->$tableKey;
                $this->line("   Respaldando tabla: {$tableName}");
                
                // Obtener estructura de la tabla
                $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                $createTableSql = null;
                
                // Extraer el CREATE TABLE de forma compatible
                $createRow = (array)$createTable[0];
                if (isset($createRow['Create Table'])) {
                    $createTableSql = $createRow['Create Table'];
                } elseif (isset($createRow['Create Table'])) {
                    $createTableSql = $createRow['Create Table'];
                } else {
                    $createTableSql = reset($createRow);
                }
                
                $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                $sql .= $createTableSql . ";\n\n";
                
                // Obtener datos
                $rows = DB::table($tableName)->get();
                if ($rows->count() > 0) {
                    $columns = array_keys((array)$rows[0]);
                    $columnsEscaped = array_map(function($col) {
                        return "`{$col}`";
                    }, $columns);
                    $sql .= "INSERT INTO `{$tableName}` (" . implode(", ", $columnsEscaped) . ") VALUES \n";
                    
                    $valuesArray = [];
                    foreach ($rows as $row) {
                        $rowArray = (array)$row;
                        $values = array_map(function($value) {
                            if ($value === null) return 'NULL';
                            if (is_numeric($value)) return $value;
                            return "'" . addslashes($value) . "'";
                        }, $rowArray);
                        $valuesArray[] = "(" . implode(", ", $values) . ")";
                    }
                    $sql .= implode(",\n", $valuesArray) . ";\n\n";
                }
            }
            
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            // Guardar archivo
            file_put_contents($backupPath, $sql);
            
            $fileSize = round(filesize($backupPath) / 1024 / 1024, 2);
            $this->info("✅ Backup creado: {$filename}");
            $this->info("📊 Tamaño: {$fileSize} MB");
            
            // Comprimir si se solicita
            if ($this->option('compress')) {
                $this->info('🗜️ Comprimiendo archivo...');
                $zipPath = str_replace('.sql', '.zip', $backupPath);
                $zip = new \ZipArchive();
                if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
                    $zip->addFile($backupPath, $filename);
                    $zip->close();
                    unlink($backupPath);
                    $zipSize = round(filesize($zipPath) / 1024 / 1024, 2);
                    $this->info("📊 Tamaño comprimido: {$zipSize} MB");
                }
            }
            
            // Limpiar backups antiguos
            $keepDays = $this->option('keep');
            $this->cleanOldBackups($keepDays);
            $this->info("🗑️ Limpiando backups de más de {$keepDays} días");
            
            // Enviar por email si se solicita
            if ($this->option('email')) {
                $this->sendBackupByEmail($backupPath, $filename, $this->option('email'));
                $this->info('📧 Backup enviado por email');
            }
            
            $this->info('🎉 Backup completado exitosamente!');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('Backup fallido: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    private function cleanOldBackups($keepDays)
    {
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) return;
        
        $files = glob($backupDir . '/*.{sql,zip}', GLOB_BRACE);
        $now = Carbon::now();
        
        foreach ($files as $file) {
            $fileDate = Carbon::createFromTimestamp(filemtime($file));
            if ($fileDate->diffInDays($now) > $keepDays) {
                unlink($file);
                $this->line("   Eliminado antiguo: " . basename($file));
            }
        }
    }
    
    private function sendBackupByEmail($backupPath, $filename, $email)
    {
        try {
            \Illuminate\Support\Facades\Mail::raw(
                "Backup de base de datos del sistema EmiSystem\n\n" .
                "Fecha: " . Carbon::now()->format('d/m/Y H:i:s') . "\n" .
                "Archivo: {$filename}\n" .
                "Tamaño: " . round(filesize($backupPath) / 1024 / 1024, 2) . " MB\n\n" .
                "Este es un backup automático del sistema.",
                function ($message) use ($backupPath, $filename, $email) {
                    $message->to($email)
                            ->subject('[EmiSystem] Backup de Base de Datos - ' . Carbon::now()->format('d/m/Y'))
                            ->attach($backupPath);
                }
            );
        } catch (\Exception $e) {
            Log::error('Error enviando email: ' . $e->getMessage());
            $this->warn('⚠️ No se pudo enviar el email');
        }
    }
}