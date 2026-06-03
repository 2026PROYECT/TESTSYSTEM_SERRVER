<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    /**
     * Registrar una acción en el log de auditoría
     */
    protected function logActivity($action, $data = [])
    {
        // Solo registrar si el usuario está autenticado
        if (!auth()->check()) {
            return;
        }
        
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'old_data' => $data['old_data'] ?? null,
            'new_data' => $data['new_data'] ?? null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'severity' => $data['severity'] ?? 'info'
        ]);
    }
    
    /**
     * Registrar cambios en un modelo antes/después de actualizar
     */
    protected function logModelUpdate($model, $oldData, $newData)
    {
        $changes = [];
        foreach ($newData as $key => $value) {
            if (isset($oldData[$key]) && $oldData[$key] != $value) {
                $changes[$key] = [
                    'old' => $oldData[$key],
                    'new' => $value
                ];
            }
        }
        
        if (!empty($changes)) {
            $this->logActivity('Actualización de ' . class_basename($model), [
                'entity_type' => class_basename($model),
                'entity_id' => $model->id,
                'old_data' => $oldData,
                'new_data' => $newData,
                'severity' => 'warning'
            ]);
        }
    }
}