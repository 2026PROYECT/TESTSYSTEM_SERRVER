<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial Académico - {{ $student->full_name }}</title>
    <style>
        /* CSS Nativo compatible con dompdf */
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .title {
            font-size: 22px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 5px;
        }
        .subtitle {
            font-size: 14px;
            color: #4f46e5; /* Indigo-600 */
            font-weight: bold;
            text-transform: uppercase;
        }
        .info-grid {
            margin-bottom: 30px;
        }
        h3 {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-left: 4px solid #4f46e5;
            padding-left: 10px;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #f8fafc;
            color: #64748b;
            font-size: 10px;
            text-transform: uppercase;
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        td {
            padding: 12px 10px;
            font-size: 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .nota {
            font-weight: bold;
            font-family: 'Courier', monospace;
            font-size: 14px;
        }
        .aprobado { color: #059669; } /* Emerald-600 */
        .reprobado { color: #e11d48; } /* Rose-600 */
        
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="title">{{ $student->full_name }}</div>
        <div class="subtitle">{{ $student->career_name }}</div>
        <p style="font-size: 10px; color: #64748b; margin-top: 5px;">
            Documento generado el: {{ date('d/m/Y H:i') }}
        </p>
    </div>

    <h3>Historial de Examen Oral</h3>
    <table>
        <thead>
            <tr>
                <th>Fecha de Evaluación</th>
                <th style="text-align: center;">Calificación</th>
                <th style="text-align: center;">Resultado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($oral as $item)
            <tr>
                <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') }}</td>
                <td style="text-align: center;" class="nota {{ $item->final_score >= 60 ? 'aprobado' : 'reprobado' }}">
                    {{ $item->final_score }}
                </td>
                <td style="text-align: center;">
                    {{ $item->final_score >= 60 ? 'APROBADO' : 'PENDIENTE' }}
                </td>
            </tr>
            @empty
            <tr><td colspan="3" style="text-align: center; color: #94a3b8;">Sin registros previos.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3>Historial de Computarizado (CompTest)</h3>
    <table>
        <thead>
            <tr>
                <th>Fecha de Intento</th>
                <th style="text-align: center;">Calificación</th>
                <th style="text-align: center;">Resultado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($comp as $item)
            <tr>
                <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') }}</td>
                <td style="text-align: center;" class="nota {{ $item->score >= 60 ? 'aprobado' : 'reprobado' }}">
                    {{ $item->score }}
                </td>
                <td style="text-align: center;">
                    {{ $item->score >= 60 ? 'APROBADO' : 'PENDIENTE' }}
                </td>
            </tr>
            @empty
            <tr><td colspan="3" style="text-align: center; color: #94a3b8;">Sin intentos registrados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Este documento es un reporte informativo del sistema de calificaciones. 
        Mínimo aprobatorio: 60 puntos.
    </div>

</body>
</html>