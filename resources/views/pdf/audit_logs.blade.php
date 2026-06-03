{{-- resources/views/pdf/audit_logs.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Auditoría</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
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
        .stats {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .stat-box {
            flex: 1;
            min-width: 80px;
            background: #f1f5f9;
            padding: 8px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-box .number {
            font-size: 18px;
            font-weight: bold;
            color: #4f46e5;
        }
        .stat-box .label {
            font-size: 7px;
            color: #64748b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #e2e8f0;
            padding: 5px;
            text-align: left;
        }
        th {
            background: #f8fafc;
            font-weight: bold;
            font-size: 8px;
        }
        td {
            font-size: 7px;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 7px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Auditoría del Sistema</h1>
        <p>Generado: {{ $generated_at->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="stats">
        <div class="stat-box">
            <div class="number">{{ $stats['total'] }}</div>
            <div class="label">Total Acciones</div>
        </div>
        @foreach($stats['by_severity'] as $severity => $count)
            <div class="stat-box">
                <div class="number">{{ $count }}</div>
                <div class="label">{{ ucfirst($severity) }}</div>
            </div>
        @endforeach
    </div>

    <h3>Detalle de Logs</h3>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Usuario</th>
                <th>Acción</th>
                <th>Entidad</th>
                <th>Severidad</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            <tr>
                <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                <td>{{ $log->user ? $log->user->name . ' ' . ($log->user->lastname ?? '') : 'Sistema' }}</td>
                <td>{{ $log->action }}</td>
                <td>{{ $log->entity_type ?? '-' }}</td>
                <td>{{ $log->severity }}</td>
                <td>{{ $log->ip_address ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Este reporte contiene el registro de todas las acciones realizadas en el sistema.
    </div>
</body>
</html>