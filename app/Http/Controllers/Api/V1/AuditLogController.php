<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class AuditLogController extends Controller
{
    /**
     * Obtener lista de logs de auditoría
     */
    public function index(Request $request)
    {
        // Verificar permisos (solo admin y staff)
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        
        $query = AuditLog::with('user')
            ->orderBy('created_at', 'desc');
        
        // Filtros
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        if ($request->has('action') && $request->action) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }
        
        if ($request->has('severity') && $request->severity) {
            $query->where('severity', $request->severity);
        }
        
        $perPage = $request->get('per_page', 50);
        $logs = $query->paginate($perPage);
        
        // Estadísticas
        $stats = [
            'total' => AuditLog::count(),
            'today' => AuditLog::whereDate('created_at', Carbon::today())->count(),
            'this_week' => AuditLog::whereDate('created_at', '>=', Carbon::now()->startOfWeek())->count(),
            'this_month' => AuditLog::whereDate('created_at', '>=', Carbon::now()->startOfMonth())->count(),
            'by_action' => AuditLog::select('action', DB::raw('count(*) as total'))
                ->groupBy('action')
                ->orderBy('total', 'desc')
                ->limit(10)
                ->get(),
            'by_severity' => AuditLog::select('severity', DB::raw('count(*) as total'))
                ->groupBy('severity')
                ->get()
        ];
        
        return response()->json([
            'logs' => $logs,
            'stats' => $stats,
            'filters' => $request->all()
        ]);
    }
    
    /**
     * Obtener un log específico
     */
    public function show($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        
        $log = AuditLog::with('user')->find($id);
        
        if (!$log) {
            return response()->json(['error' => 'Log no encontrado'], 404);
        }
        
        return response()->json($log);
    }
    
    /**
     * Registrar una acción manualmente
     */
    public function store(Request $request)
    {
        $request->validate([
            'action' => 'required|string|max:255',
            'entity_type' => 'nullable|string|max:255',
            'entity_id' => 'nullable|integer',
            'old_data' => 'nullable|array',
            'new_data' => 'nullable|array',
            'severity' => 'nullable|in:info,warning,danger,critical'
        ]);
        
        $log = AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $request->action,
            'entity_type' => $request->entity_type,
            'entity_id' => $request->entity_id,
            'old_data' => $request->old_data,
            'new_data' => $request->new_data,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'severity' => $request->severity ?? 'info'
        ]);
        
        return response()->json([
            'message' => 'Evento registrado correctamente',
            'log' => $log
        ], 201);
    }
    
    /**
     * Exportar logs a PDF
     */
    public function exportPdf(Request $request)
{
    try {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');
        
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        if ($request->has('action') && $request->action) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }
        
        if ($request->has('severity') && $request->severity) {
            $query->where('severity', $request->severity);
        }
        
        $logs = $query->limit(1000)->get();
        
        $stats = [
            'total' => $logs->count(),
            'by_action' => $logs->groupBy('action')->map->count(),
            'by_severity' => $logs->groupBy('severity')->map->count(),
        ];
        
        $pdf = Pdf::loadView('pdf.audit_logs', [
            'logs' => $logs,
            'stats' => $stats,
            'generated_at' => now(),
            'filters' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]
        ]);
        
        $pdf->setPaper('a4', 'landscape');
        
        return $pdf->download('auditoria_' . date('Y-m-d_His') . '.pdf');
        
    } catch (\Exception $e) {
        \Log::error('Error exportando PDF: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
    
    /**
     * Exportar logs a CSV
     */
    public function exportCsv(Request $request)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        
        $query = AuditLog::with('user')
            ->orderBy('created_at', 'desc');
        
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        $logs = $query->limit(5000)->get();
        
        $filename = 'auditoria_' . date('Y-m-d_His') . '.csv';
        $handle = fopen('php://temp', 'w+');
        
        // Cabeceras
        fputcsv($handle, [
            'ID', 'Fecha', 'Usuario', 'Email', 'Acción', 'Entidad', 'ID Entidad', 
            'Severidad', 'IP', 'User Agent', 'Datos Antiguos', 'Datos Nuevos'
        ]);
        
        // Datos
        foreach ($logs as $log) {
            fputcsv($handle, [
                $log->id,
                $log->created_at->format('d/m/Y H:i:s'),
                $log->user ? $log->user->name . ' ' . ($log->user->lastname ?? '') : 'Sistema',
                $log->user ? $log->user->email : '-',
                $log->action,
                $log->entity_type ?? '-',
                $log->entity_id ?? '-',
                $log->severity,
                $log->ip_address ?? '-',
                substr($log->user_agent ?? '-', 0, 100),
                json_encode($log->old_data),
                json_encode($log->new_data)
            ]);
        }
        
        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);
        
        return response($csvContent, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
    
    /**
     * Limpiar logs antiguos
     */
    public function cleanOldLogs(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        
        $days = $request->input('days', 30);
        $deleted = AuditLog::where('created_at', '<', now()->subDays($days))->delete();
        
        return response()->json([
            'message' => "Se eliminaron {$deleted} logs antiguos (más de {$days} días)",
            'deleted_count' => $deleted
        ]);
    }
}