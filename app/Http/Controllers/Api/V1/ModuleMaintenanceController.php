<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModuleQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ModuleMaintenanceController extends Controller
{
    /**
     * IMPORTAR SQL DIRECTO (MÉTODO PRINCIPAL MEJORADO)
     */
    public function uploadSql(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:sql,txt',
                'language_id' => 'required|exists:languages,id',
                'type' => 'sometimes|in:modules,questions,auto'
            ]);

            $sql = file_get_contents($request->file('file')->getRealPath());
            
            if (empty($sql)) {
                return response()->json([
                    'success' => false,
                    'message' => '❌ El archivo está vacío'
                ], 422);
            }

            // Determinar automáticamente el tipo si no se especifica
            $type = $request->get('type', 'auto');
            if ($type === 'auto') {
                if (stripos($sql, 'INSERT INTO modules') !== false) {
                    $type = 'modules';
                } elseif (stripos($sql, 'INSERT INTO module_questions') !== false || stripos($sql, 'INSERT INTO questions') !== false) {
                    $type = 'questions';
                }
            }
            
            // Para preguntas, verificar que los módulos existan
            if ($type === 'questions') {
                return $this->importQuestionsWithValidation($sql, $request->language_id);
            }
            
            // Para módulos, importar directamente
            if ($type === 'modules') {
                return $this->importModulesWithValidation($sql, $request->language_id);
            }
            
            // Si es automático y no se detectó, intentar importar todo
            return $this->importGenericSql($sql, $request->language_id);
            
        } catch (\Exception $e) {
            Schema::enableForeignKeyConstraints();
            
            Log::error('Error en importación SQL', [
                'error' => $e->getMessage(),
                'file' => $request->file('file')->getClientOriginalName(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '❌ Error en la importación: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Importar módulos con validación
     */
    private function importModulesWithValidation($sql, $languageId)
    {
        try {
            // Reemplazar placeholders
            $sql = str_replace(':language_id', $languageId, $sql);
            $sql = str_replace('__LANG_ID__', $languageId, $sql);
            
            // Limpiar fechas inválidas
            $sql = $this->cleanDates($sql);
            
            // Extraer sentencias INSERT individuales
            $inserts = $this->extractInsertStatements($sql, 'modules');
            
            if (empty($inserts)) {
                return response()->json([
                    'success' => false,
                    'message' => '❌ No se encontraron sentencias INSERT para la tabla modules'
                ], 422);
            }
            
            DB::beginTransaction();
            $imported = 0;
            $errors = [];
            $duplicates = [];
            
            foreach ($inserts as $insert) {
                try {
                    // Validar que no exista un módulo con el mismo título e idioma
                    if (preg_match("/VALUES\s*\(\s*[^,]*,\s*'([^']+)'/i", $insert, $matches)) {
                        $title = $matches[1];
                        $existing = Module::where('title', $title)
                            ->where('language_id', $languageId)
                            ->first();
                        
                        if ($existing) {
                            $duplicates[] = $title;
                            continue;
                        }
                    }
                    
                    DB::statement($insert);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
            
            DB::commit();
            
            $message = "✅ Se importaron {$imported} módulos correctamente";
            if (!empty($duplicates)) {
                $message .= ". ⚠️ Módulos duplicados omitidos: " . count($duplicates);
            }
            if (!empty($errors)) {
                $message .= ". ❌ Errores: " . count($errors);
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'imported_count' => $imported,
                'duplicates' => $duplicates,
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Importar preguntas con validación de módulos existentes
     */
    private function importQuestionsWithValidation($sql, $languageId)
    {
        try {
            // Limpiar fechas
            $sql = $this->cleanDates($sql);
            
            // Extraer sentencias INSERT
            $inserts = $this->extractInsertStatements($sql, 'module_questions');
            
            if (empty($inserts)) {
                return response()->json([
                    'success' => false,
                    'message' => '❌ No se encontraron sentencias INSERT para la tabla module_questions'
                ], 422);
            }
            
            DB::beginTransaction();
            $imported = 0;
            $errors = [];
            $missingModules = [];
            
            foreach ($inserts as $insert) {
                try {
                    // Extraer module_id de la sentencia
                    if (preg_match("/VALUES\s*\(\s*(\d+)/i", $insert, $matches)) {
                        $moduleId = intval($matches[1]);
                        
                        // Verificar que el módulo existe en el idioma actual
                        $moduleExists = Module::where('id', $moduleId)
                            ->where('language_id', $languageId)
                            ->exists();
                        
                        if (!$moduleExists) {
                            $missingModules[] = $moduleId;
                            continue;
                        }
                    }
                    
                    DB::statement($insert);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
            
            DB::commit();
            
            $message = "✅ Se importaron {$imported} preguntas";
            if (!empty($missingModules)) {
                $uniqueMissing = array_unique($missingModules);
                $message .= " ⚠️ Módulos no encontrados: " . implode(', ', $uniqueMissing);
                $message .= ". Importe primero los módulos correspondientes.";
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'imported_count' => $imported,
                'missing_modules' => array_unique($missingModules),
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Importación genérica (modo legacy)
     */
    private function importGenericSql($sql, $languageId)
    {
        // Deshabilitar restricciones
        Schema::disableForeignKeyConstraints();
        
        try {
            // Reemplazar placeholders
            $sql = str_replace(':language_id', $languageId, $sql);
            $sql = str_replace('__LANG_ID__', $languageId, $sql);
            
            // Limpiar fechas
            $sql = $this->cleanDates($sql);
            
            // Contar registros antes de importar
            $insertCountModules = substr_count(strtoupper($sql), 'INSERT INTO MODULES');
            $insertCountQuestions = substr_count(strtoupper($sql), 'INSERT INTO MODULE_QUESTIONS');
            
            // Ejecutar SQL
            DB::unprepared($sql);
            
            Schema::enableForeignKeyConstraints();
            
            $message = "✅ Importación completada. ";
            if ($insertCountModules > 0) {
                $message .= "Módulos: {$insertCountModules}. ";
            }
            if ($insertCountQuestions > 0) {
                $message .= "Preguntas: {$insertCountQuestions}. ";
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'imported_count' => $insertCountModules + $insertCountQuestions,
                'modules_count' => $insertCountModules,
                'questions_count' => $insertCountQuestions
            ]);
            
        } catch (\Exception $e) {
            Schema::enableForeignKeyConstraints();
            throw $e;
        }
    }
    
    /**
     * Limpiar fechas inválidas del SQL
     */
    private function cleanDates($sql)
    {
        // Reemplazar fechas '0000-00-00 00:00:00' con NOW()
        $sql = preg_replace("/'0000-00-00\s+00:00:00'/i", "NOW()", $sql);
        $sql = preg_replace("/'0000-00-00'/i", "NOW()", $sql);
        
        // Reemplazar comillas dobles por simples
        $sql = str_replace('"', "'", $sql);
        
        // Normalizar fechas formato US (MM/DD/YYYY HH:ii:ss)
        $sql = preg_replace_callback("/(['\"])(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}):(\d{1,2}):(\d{1,2})(['\"])/", function($matches) {
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $day = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
            $year = $matches[4];
            $hour = str_pad($matches[5], 2, '0', STR_PAD_LEFT);
            $minute = str_pad($matches[6], 2, '0', STR_PAD_LEFT);
            $second = str_pad($matches[7], 2, '0', STR_PAD_LEFT);
            
            return "'{$year}-{$month}-{$day} {$hour}:{$minute}:{$second}'";
        }, $sql);
        
        // Normalizar fechas solo fecha (MM/DD/YYYY)
        $sql = preg_replace_callback("/(['\"])(\d{1,2})\/(\d{1,2})\/(\d{4})(['\"])/", function($matches) {
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $day = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
            $year = $matches[4];
            
            return "'{$year}-{$month}-{$day} 00:00:00'";
        }, $sql);
        
        return $sql;
    }
    
    /**
     * Extraer sentencias INSERT individuales
     */
    private function extractInsertStatements($sql, $tableName)
    {
        $pattern = "/INSERT\s+INTO\s+`?{$tableName}`?\s*\([^)]+\)\s*VALUES\s*\([^;]+\)/is";
        preg_match_all($pattern, $sql, $matches);
        return $matches[0];
    }

    /**
     * VACIAR TABLAS (TRUNCATE) - Conserva la estructura
     */
    public function truncateModulesAndQuestions(Request $request)
    {
        try {
            $request->validate([
                'confirm' => 'required|in:YES'
            ]);
            
            // Deshabilitar foreign keys temporalmente
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            $truncatedTables = [];
            
            // Vaciar preguntas primero (tabla hija)
            if (Schema::hasTable('module_questions')) {
                DB::table('module_questions')->truncate();
                $truncatedTables[] = 'module_questions';
            }
            
            // Vaciar módulos después (tabla padre)
            if (Schema::hasTable('modules')) {
                DB::table('modules')->truncate();
                $truncatedTables[] = 'modules';
            }
            
            // Volver a habilitar foreign keys
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
            return response()->json([
                'success' => true,
                'message' => "✅ Contenido eliminado de: " . implode(', ', $truncatedTables),
                'truncated_tables' => $truncatedTables
            ]);
            
        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
            return response()->json([
                'success' => false,
                'message' => '❌ Error al vaciar las tablas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * VACIAR TODAS LAS TABLAS RELACIONADAS
     */
    public function truncateAllRelatedTables(Request $request)
    {
        try {
            $request->validate([
                'confirm' => 'required|in:YES'
            ]);
            
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            $tables = [
                'module_questions',
                'module_results',
                'user_module_progress', 
                'user_answers',
                'user_exam_attempts',
                'modules'
            ];
            
            $truncated = [];
            
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                    $truncated[] = $table;
                }
            }
            
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
            return response()->json([
                'success' => true,
                'message' => "✅ Se vaciaron " . count($truncated) . " tablas correctamente",
                'truncated_tables' => $truncated
            ]);
            
        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
            return response()->json([
                'success' => false,
                'message' => '❌ Error: ' . $e->getMessage()
            ], 500);
        }
    }
}