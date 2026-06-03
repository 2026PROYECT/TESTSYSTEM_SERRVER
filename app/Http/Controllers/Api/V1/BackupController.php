<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Notifications\CustomNotification;

class BackupController extends Controller
{
    /**
     * Obtener lista de backups disponibles (con hash seguro)
     */
    public function index()
    {
        try {
            // ✅ Verificar autenticación
            if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'staff'])) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
            
            $backupDir = storage_path('app/backups');
            $backups = [];
            $totalSize = 0;
            
            if (!is_dir($backupDir)) {
                return response()->json([
                    'success' => true,
                    'backups' => [],
                    'stats' => ['total_backups' => 0, 'total_size' => 0, 'last_backup' => null]
                ]);
            }
            
            $files = scandir($backupDir);
            
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                
                $filePath = $backupDir . '/' . $file;
                if (!is_file($filePath)) continue;
                
                $size = @filesize($filePath);
                if ($size === false) continue;
                
                $filetime = @filemtime($filePath);
                if ($filetime === false) continue;
                
                $totalSize += $size;
                
                $backups[] = [
                    'name' => $file,
                    'size' => round($size / 1024 / 1024, 2),
                    'size_bytes' => $size,
                    'date' => date('Y-m-d H:i:s', $filetime),
                    'timestamp' => $filetime,
                    'type' => pathinfo($file, PATHINFO_EXTENSION),
                    'download_hash' => md5($file . env('APP_KEY') . $filetime)
                ];
            }
            
            // Ordenar por fecha descendente
            usort($backups, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });
            
            $stats = [
                'total_backups' => count($backups),
                'total_size' => round($totalSize / 1024 / 1024, 2),
                'last_backup' => $backups[0]['date'] ?? null,
                'last_backup_name' => $backups[0]['name'] ?? null,
            ];
            
            return response()->json([
                'success' => true,
                'backups' => $backups,
                'stats' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en index backups: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }
    
    /**
     * Descarga segura usando hash (sin autenticación)
     */
    public function secureDownload($hash)
    {
        Log::info('Intento de descarga segura', ['hash' => $hash]);
        
        $backupDir = storage_path('app/backups');
        
        if (!is_dir($backupDir)) {
            Log::warning('Directorio de backups no existe');
            return response()->json(['error' => 'No hay backups disponibles'], 404);
        }
        
        $files = glob($backupDir . '/*.{sql,zip}', GLOB_BRACE);
        
        foreach ($files as $file) {
            $filename = basename($file);
            $expectedHash = md5($filename . env('APP_KEY') . filemtime($file));
            
            if ($hash === $expectedHash) {
                Log::info('Descarga exitosa', ['filename' => $filename]);
                return response()->download($file, $filename, [
                    'Content-Type' => 'application/octet-stream',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"'
                ]);
            }
        }
        
        Log::warning('Hash no encontrado', ['hash' => $hash]);
        return response()->json(['error' => 'Enlace inválido o expirado'], 404);
    }
    
    /**
     * Crear un nuevo backup
     */
    public function store(Request $request)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        
        try {
            $compress = $request->input('compress', true);
            
            $params = ['--keep' => 30];
            if ($compress) {
                $params['--compress'] = true;
            }
            
            Artisan::call('backup:database', $params);
            
            $output = Artisan::output();
            
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'Creación de backup manual',
                'entity_type' => 'system',
                'new_data' => ['compressed' => $compress],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'severity' => 'info'
            ]);
            
            $backupDir = storage_path('app/backups');
            $extension = $compress ? 'zip' : 'sql';
            $files = glob($backupDir . "/*.{$extension}");
            $latestFile = null;
            foreach ($files as $file) {
                if ($latestFile === null || filemtime($file) > filemtime($latestFile)) {
                    $latestFile = $file;
                }
            }
            
            // ✅ NOTIFICACIÓN AL ADMIN QUE EJECUTÓ EL BACKUP
            $admin = auth()->user();
            if ($admin && in_array($admin->role, ['admin', 'staff'])) {
                $admin->notify(new CustomNotification(
                    'backup_completed',
                    '💾 Backup Completado',
                    "El backup de la base de datos se ha completado exitosamente. Archivo: " . basename($latestFile),
                    [
                        'filename' => basename($latestFile),
                        'size_mb' => round(filesize($latestFile) / 1024 / 1024, 2),
                        'compressed' => $compress,
                        'date' => now()->format('Y-m-d H:i:s')
                    ]
                ));
                
                Log::info("Notificación de backup enviada a: {$admin->email}");
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Backup creado exitosamente',
                'filename' => $latestFile ? basename($latestFile) : null,
                'compressed' => $compress,
                'output' => $output
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear backup: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Eliminar un backup
     */
    public function destroy($filename)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        
        $filePath = storage_path("app/backups/{$filename}");
        
        if (!file_exists($filePath)) {
            return response()->json(['error' => 'Archivo no encontrado'], 404);
        }
        
        $size = round(filesize($filePath) / 1024 / 1024, 2);
        unlink($filePath);
        
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'Eliminación de backup',
            'entity_type' => 'system',
            'old_data' => ['filename' => $filename, 'size_mb' => $size],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'severity' => 'warning'
        ]);
        
        // ✅ NOTIFICACIÓN AL ADMIN
        $admin = auth()->user();
        if ($admin && in_array($admin->role, ['admin', 'staff'])) {
            $admin->notify(new CustomNotification(
                'backup_deleted',
                '🗑️ Backup Eliminado',
                "Se ha eliminado el backup: {$filename} ({$size} MB)",
                [
                    'filename' => $filename,
                    'size_mb' => $size,
                    'date' => now()->format('Y-m-d H:i:s')
                ]
            ));
            
            Log::info("Notificación de eliminación de backup enviada a: {$admin->email}");
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Backup eliminado correctamente'
        ]);
    }
    
    /**
     * Restaurar un backup
     */
    public function restore(Request $request)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        
        $filename = $request->input('filename');
        
        if (!$filename) {
            return response()->json(['error' => 'Nombre de archivo requerido'], 422);
        }
        
        try {
            $filePath = storage_path("app/backups/{$filename}");
            
            if (!file_exists($filePath)) {
                return response()->json(['error' => 'Archivo no encontrado'], 404);
            }
            
            if (pathinfo($filePath, PATHINFO_EXTENSION) === 'zip') {
                $zip = new \ZipArchive();
                if ($zip->open($filePath) !== true) {
                    return response()->json(['error' => 'No se pudo abrir el archivo ZIP'], 500);
                }
                $tempSql = storage_path('app/backups/temp_' . time() . '.sql');
                $zip->extractTo(dirname($tempSql));
                $zip->close();
                $sql = file_get_contents($tempSql);
                unlink($tempSql);
            } else {
                $sql = file_get_contents($filePath);
            }
            
            if (empty($sql)) {
                return response()->json(['error' => 'El archivo de backup está vacío'], 422);
            }
            
            $sentences = explode(";\n", $sql);
            $executed = 0;
            $errors = 0;
            
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
                    $errors++;
                }
            }
            
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'Restauración de backup',
                'entity_type' => 'system',
                'new_data' => [
                    'filename' => $filename,
                    'executed' => $executed,
                    'errors' => $errors
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'severity' => 'danger'
            ]);
            
            // ✅ NOTIFICACIÓN AL ADMIN
            $admin = auth()->user();
            if ($admin && in_array($admin->role, ['admin', 'staff'])) {
                $admin->notify(new CustomNotification(
                    'backup_restored',
                    '🔄 Backup Restaurado',
                    "Se ha restaurado la base de datos desde el backup: {$filename}. Sentencias ejecutadas: {$executed}, Errores: {$errors}",
                    [
                        'filename' => $filename,
                        'executed' => $executed,
                        'errors' => $errors,
                        'date' => now()->format('Y-m-d H:i:s')
                    ]
                ));
                
                Log::info("Notificación de restauración de backup enviada a: {$admin->email}");
            }
            
            return response()->json([
                'success' => true,
                'message' => "Backup restaurado correctamente. {$executed} sentencias ejecutadas, {$errors} errores.",
                'executed' => $executed,
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error en restore: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al restaurar backup',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}