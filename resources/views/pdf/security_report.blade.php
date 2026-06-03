<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Seguridad</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #1e293b;
            margin: 0;
            font-size: 18px;
        }
        .header p {
            color: #64748b;
            margin: 5px 0 0;
            font-size: 9px;
        }
        .stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 10px;
        }
        .stat-box {
            flex: 1;
            background: #f1f5f9;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-box .number {
            font-size: 20px;
            font-weight: bold;
            color: #4f46e5;
        }
        .stat-box .label {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #e2e8f0;
            padding: 6px;
            text-align: left;
        }
        th {
            background: #f8fafc;
            font-weight: bold;
            font-size: 9px;
        }
        td {
            font-size: 8px;
        }
        .event-tab_switch { color: #f59e0b; }
        .event-devtools_opened { color: #ef4444; }
        .event-screenshot_attempt { color: #ef4444; }
        .event-exam_invalidated { color: #000000; font-weight: bold; }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Violaciones de Seguridad</h1>
        <p>Generado: {{ $generated_at->format('d/m/Y H:i:s') }}</p>
        @if($filters['start_date'] || $filters['end_date'] || $filters['student_id'])
            <p>
                Filtros: 
                @if($filters['start_date']) Desde: {{ $filters['start_date'] }} @endif
                @if($filters['end_date']) Hasta: {{ $filters['end_date'] }} @endif
                @if($filters['student_id']) Estudiante ID: {{ $filters['student_id'] }} @endif
            </p>
        @endif
    </div>

    <div class="stats">
        <div class="stat-box">
            <div class="number">{{ $stats['total'] }}</div>
            <div class="label">Total Violaciones</div>
        </div>
        @foreach($stats['by_type'] as $type => $count)
            <div class="stat-box">
                <div class="number">{{ $count }}</div>
                <div class="label">{{ str_replace('_', ' ', ucfirst($type)) }}</div>
            </div>
        @endforeach
    </div>

    <h3>Top 10 Estudiantes con más violaciones</h3>
    <table>
        <thead>
            <tr>
                <th>Estudiante</th>
                <th>Total Violaciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stats['top_students'] as $student)
            <tr>
                <td>{{ $student['name'] }}</td>
                <td>{{ $student['count'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Detalle de Violaciones</h3>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Estudiante</th>
                <th>Examen ID</th>
                <th>Tipo de Evento</th>
                <th>Detalles</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            <tr>
                <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                <td>{{ $log->user ? $log->user->full_name : 'N/A' }}</td>
                <td>{{ $log->exam_attempt_id ?? 'N/A' }}</td>
                <td class="event-{{ str_replace('_', '-', $log->event_type) }}">{{ $log->event_type }}</td>
                <td>{{ $log->details ?? '-' }}</td>
                <td>{{ $log->ip_address ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Este reporte es generado automáticamente por el sistema de seguridad de EmiSystem.
        Cualquier intento de manipulación del examen queda registrado.
    </div>
</body>
</html>