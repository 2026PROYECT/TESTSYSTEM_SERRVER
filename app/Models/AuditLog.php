<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    
    protected $fillable = [
        'user_id', 
        'action', 
        'entity_type', 
        'entity_id',
        'old_data', 
        'new_data', 
        'ip_address', 
        'user_agent', 
        'severity'
    ];
    
    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Relación con el usuario que realizó la acción
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    /**
     * Scope para filtrar por severidad
     */
    public function scopeSeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }
    
    /**
     * Scope para filtrar por acción
     */
    public function scopeAction($query, $action)
    {
        return $query->where('action', 'like', "%{$action}%");
    }
    
    /**
     * Scope para filtrar por entidad
     */
    public function scopeEntity($query, $entityType, $entityId = null)
    {
        $query->where('entity_type', $entityType);
        if ($entityId) {
            $query->where('entity_id', $entityId);
        }
        return $query;
    }
    
    /**
     * Método helper para registrar logs fácilmente
     */
    public static function log($action, $data = [])
    {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'old_data' => $data['old_data'] ?? null,
            'new_data' => $data['new_data'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'severity' => $data['severity'] ?? 'info'
        ]);
    }
}