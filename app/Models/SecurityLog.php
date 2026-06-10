<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityLog extends Model
{
    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'user_id',
        'exam_attempt_id',
        'event_type',
        'details',
        'violation_count',
        'ip_address',
        'user_agent'
    ];
    
    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'violation_count' => 'integer'
    ];
    
    /**
     * Tipos de eventos de seguridad disponibles
     */
    const EVENT_TYPES = [
        // Eventos originales
        'tab_switch' => 'Cambio de pestaña/ventana',
        'mouse_leave' => 'Salida del área del examen',
        'copy_attempt' => 'Intento de copiar',
        'devtools_opened' => 'Herramientas de desarrollo abiertas',
        'screenshot_attempt' => 'Intento de captura de pantalla',
        'reload_attempt' => 'Intento de recargar página',
        'tab_close_attempt' => 'Intento de cerrar pestaña',
        'view_source_attempt' => 'Intento de ver código fuente',
        'drag_attempt' => 'Intento de arrastrar',
        'drop_attempt' => 'Intento de soltar archivo',
        'window_resize' => 'Cambio de tamaño de ventana',
        'fullscreen_exit' => 'Salida de pantalla completa',
        'fullscreen_enabled' => 'Pantalla completa activada',
        'exam_invalidated' => 'Examen invalidado',
        
        // Eventos del frontend (bloqueos)
        'f12_blocked' => 'Tecla F12 bloqueada',
        'devtools_blocked' => 'Intento de abrir DevTools bloqueado',
        'view_source_blocked' => 'Intento de ver código fuente bloqueado',
        'reload_blocked' => 'Intento de recargar página bloqueado',
        'screenshot_blocked' => 'Intento de captura de pantalla bloqueado',
        'print_blocked' => 'Intento de imprimir bloqueado',
        'right_click_blocked' => 'Clic derecho bloqueado',
        'window_blur' => 'Pérdida de foco de ventana',
        'tab_switch_blocked' => 'Cambio de pestaña bloqueado',
    ];
    
    /**
     * Límites de violaciones por tipo de evento
     */
    const EVENT_LIMITS = [
        // Eventos originales
        'tab_switch' => 3,
        'mouse_leave' => 5,
        'copy_attempt' => 3,
        'devtools_opened' => 2,
        'screenshot_attempt' => 3,
        'reload_attempt' => 1,
        'tab_close_attempt' => 1,
        'view_source_attempt' => 2,
        'drag_attempt' => 5,
        'drop_attempt' => 3,
        'window_resize' => 10,
        'fullscreen_exit' => 3,
        
        // Eventos del frontend (bloqueos)
        'f12_blocked' => 2,
        'devtools_blocked' => 2,
        'view_source_blocked' => 2,
        'reload_blocked' => 1,
        'screenshot_blocked' => 3,
        'print_blocked' => 2,
        'right_click_blocked' => 5,
        'window_blur' => 3,
        'tab_switch_blocked' => 3,
    ];
    
    // ============================================================
    // RELACIONES
    // ============================================================
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function examAttempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }
    
    // ============================================================
    // SCOPES
    // ============================================================
    
    public function scopeForExam($query, $examAttemptId)
    {
        return $query->where('exam_attempt_id', $examAttemptId);
    }
    
    public function scopeOfType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }
    
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
    
    public function scopeCriticalViolations($query)
    {
        return $query->whereIn('event_type', [
            'tab_switch', 'devtools_opened', 'screenshot_attempt', 
            'mouse_leave', 'copy_attempt', 'f12_blocked', 'devtools_blocked'
        ]);
    }
    
    public function scopeLastMinutes($query, $minutes = 30)
    {
        return $query->where('created_at', '>', now()->subMinutes($minutes));
    }
    
    // ============================================================
    // MÉTODOS ESTÁTICOS
    // ============================================================
    
    public static function getViolationCount($examAttemptId, $eventType = null)
    {
        $query = self::where('exam_attempt_id', $examAttemptId);
        
        if ($eventType) {
            $query->where('event_type', $eventType);
        } else {
            $query->whereIn('event_type', array_keys(self::EVENT_TYPES));
        }
        
        return $query->count();
    }
    
    public static function getViolationStats($examAttemptId)
    {
        return self::where('exam_attempt_id', $examAttemptId)
            ->select('event_type', \DB::raw('count(*) as total'), \DB::raw('MAX(violation_count) as max_count'))
            ->groupBy('event_type')
            ->get()
            ->pluck('total', 'event_type')
            ->toArray();
    }
    
    public static function shouldInvalidateExam($examAttemptId, $eventType = null)
    {
        if ($eventType) {
            $violationCount = self::where('exam_attempt_id', $examAttemptId)
                ->where('event_type', $eventType)
                ->count();
            
            $limit = self::EVENT_LIMITS[$eventType] ?? 3;
            return $violationCount >= $limit;
        }
        
        $criticalViolations = self::where('exam_attempt_id', $examAttemptId)
            ->criticalViolations()
            ->select('event_type', \DB::raw('count(*) as total'))
            ->groupBy('event_type')
            ->get();
        
        foreach ($criticalViolations as $violation) {
            $limit = self::EVENT_LIMITS[$violation->event_type] ?? 3;
            if ($violation->total >= $limit) {
                return true;
            }
        }
        
        return false;
    }
    
    public static function logEvent($examAttemptId, $eventType, $details = null)
    {
        $user = auth()->user();
        
        if (!$user) {
            return null;
        }
        
        $violationCount = self::where('exam_attempt_id', $examAttemptId)
            ->where('event_type', $eventType)
            ->where('created_at', '>', now()->subMinutes(30))
            ->count() + 1;
        
        return self::create([
            'user_id' => $user->id,
            'exam_attempt_id' => $examAttemptId,
            'event_type' => $eventType,
            'details' => $details,
            'violation_count' => $violationCount,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }
    
    // ============================================================
    // MÉTODOS DE INSTANCIA
    // ============================================================
    
    public function getEventTypeNameAttribute(): string
    {
        return self::EVENT_TYPES[$this->event_type] ?? $this->event_type;
    }
    
    public function isCriticalViolation(): bool
    {
        return in_array($this->event_type, [
            'tab_switch', 'devtools_opened', 'screenshot_attempt', 
            'mouse_leave', 'copy_attempt', 'f12_blocked', 'devtools_blocked'
        ]);
    }
    
    public function getLimitAttribute(): int
    {
        return self::EVENT_LIMITS[$this->event_type] ?? 3;
    }
    
    public function hasReachedLimit(): bool
    {
        return ($this->violation_count ?? 1) >= $this->limit;
    }
    
    public function getRemainingWarningsAttribute(): int
    {
        $remaining = $this->limit - ($this->violation_count ?? 1);
        return max(0, $remaining);
    }
    
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i:s');
    }
}