<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0cm; }
        body { font-family: 'Helvetica', sans-serif; margin: 0; padding: 0; color: #1a202c; }
        .header { background: #111827; color: white; padding: 40px; text-align: center; }
        .header h1 { margin: 0; text-transform: uppercase; letter-spacing: 2px; font-size: 24px; }
        .container { padding: 40px; }
        .student-info { margin-bottom: 30px; border-bottom: 2px solid #f3f4f6; padding-bottom: 20px; }
        .score-card { background: #f9fafb; border-radius: 20px; padding: 30px; text-align: center; margin-bottom: 30px; }
        .level-badge { background: #4f46e5; color: white; padding: 8px 20px; border-radius: 10px; font-weight: bold; display: inline-block; }
        .score-value { font-size: 48px; font-weight: 900; margin: 10px 0; color: #111827; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th { background: #f8fafc; text-align: left; padding: 12px; font-size: 12px; color: #64748b; text-transform: uppercase; }
        .table td { padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #94a3b8; padding: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Evaluación</h1>
        <p>EmiSystem - Educational Management</p>
    </div>

    <div class="container">
        <div class="student-info">
            <h2 style="margin:0">{{ $result->name }} {{ $result->lastname }}</h2>
            <p style="color:#6366f1; font-weight:bold; margin:5px 0;">ID Asignación: #{{ $result->quiz_assignment_id }}</p>
        </div>

        <div class="score-card">
            <span class="level-badge">{{ $result->final_level }}</span>
            <div class="score-value">{{ number_format($result->final_score, 2) }}</div>
            <p style="margin:0; color:#64748b; font-weight:bold; text-transform:uppercase; font-size:10px;">Puntaje Final Obtenido</p>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Criterio de Evaluación</th>
                    <th style="text-align: right;">Puntaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($scores as $criterio => $puntos)
                <tr>
                    <td>{{ ucwords(str_replace('_', ' ', $criterio)) }}</td>
                    <td style="text-align: right; font-weight:bold;">{{ $puntos }}/10</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        Generado el {{ $date }} | Documento oficial emitido por EmiSystem
    </div>
</body>
</html>