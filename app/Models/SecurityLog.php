<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityLog extends Model
{
    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
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
     *
     * @var array
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
        'exam_invalidated' => 'Examen invalidado'
    ];
    
    /**
     * Límites de violaciones por tipo de evento
     */
    const EVENT_LIMITS = [
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
        'fullscreen_exit' => 3
    ];
    
    // ============================================================
    // RELACIONES
    // ============================================================
    
    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Relación con el intento de examen
     */
    public function examAttempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }
    
    // ============================================================
    // SCOPES
    // ============================================================
    
    /**
     * Scope para filtrar por examen
     */
    public function scopeForExam($query, $examAttemptId)
    {
        return $query->where('exam_attempt_id', $examAttemptId);
    }
    
    /**
     * Scope para filtrar por tipo de evento
     */
    public function scopeOfType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }
    
    /**
     * Scope para filtrar por usuario
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
    
    /**
     * Scope para violaciones críticas (las que pueden invalidar el examen)
     */
    public function scopeCriticalViolations($query)
    {
        return $query->whereIn('event_type', [
            'tab_switch',
            'devtools_opened',
            'screenshot_attempt',
            'mouse_leave',
            'copy_attempt'
        ]);
    }
    
    /**
     * Scope para eventos en los últimos minutos
     */
    public function scopeLastMinutes($query, $minutes = 30)
    {
        return $query->where('created_at', '>', now()->subMinutes($minutes));
    }
    
    // ============================================================
    // MÉTODOS ESTÁTICOS UTILITARIOS
    // ============================================================
    
    /**
     * Obtener conteo de violaciones por tipo para un examen
     */
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
    
    /**
     * Obtener conteo detallado de violaciones por tipo
     */
    public static function getViolationStats($examAttemptId)
    {
        return self::where('exam_attempt_id', $examAttemptId)
            ->select('event_type', \DB::raw('count(*) as total'), \DB::raw('MAX(violation_count) as max_count'))
            ->groupBy('event_type')
            ->get()
            ->pluck('total', 'event_type')
            ->toArray();
    }
    
    /**
     * Verificar si debe invalidar el examen según el tipo de violación
     */
    public static function shouldInvalidateExam($examAttemptId, $eventType = null)
    {
        if ($eventType) {
            $violationCount = self::where('exam_attempt_id', $examAttemptId)
                ->where('event_type', $eventType)
                ->count();
            
            $limit = self::EVENT_LIMITS[$eventType] ?? 3;
            return $violationCount >= $limit;
        }
        
        // Verificar cualquier violación crítica
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
    
    /**
     * Registrar un evento de seguridad
     */
    public static function logEvent($examAttemptId, $eventType, $details = null)
    {
        $user = auth()->user();
        
        if (!$user) {
            return null;
        }
        
        // Contar violaciones recientes del mismo tipo
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
    
    /**
     * Obtener el nombre legible del tipo de evento
     */
    public function getEventTypeNameAttribute(): string
    {
        return self::EVENT_TYPES[$this->event_type] ?? $this->event_type;
    }
    
    /**
     * Verificar si este evento es una violación crítica
     */
    public function isCriticalViolation(): bool
    {
        return in_array($this->event_type, [
            'tab_switch',
            'devtools_opened',
            'screenshot_attempt',
            'mouse_leave',
            'copy_attempt'
        ]);
    }
    
    /**
     * Obtener el límite de violaciones para este tipo de evento
     */
    public function getLimitAttribute(): int
    {
        return self::EVENT_LIMITS[$this->event_type] ?? 3;
    }
    
    /**
     * Verificar si ya se alcanzó el límite de violaciones
     */
    public function hasReachedLimit(): bool
    {
        return ($this->violation_count ?? 1) >= $this->limit;
    }
    
    /**
     * Obtener las advertencias restantes
     */
    public function getRemainingWarningsAttribute(): int
    {
        $remaining = $this->limit - ($this->violation_count ?? 1);
        return max(0, $remaining);
    }
    
    /**
     * Formatear la fecha de creación
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i:s');
    }
}