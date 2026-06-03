<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 1.5cm; }
        body { font-family: 'Helvetica', sans-serif; color: #1a202c; line-height: 1.5; }
        
        /* Encabezado */
        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 30px; }
        .title { font-size: 22px; font-weight: bold; color: #111827; margin: 0; }
        .subtitle { font-size: 10px; color: #4f46e5; text-transform: uppercase; letter-spacing: 2px; font-weight: bold; }
        
        /* Info del Reporte */
        .report-info { margin-bottom: 20px; font-size: 11px; color: #64748b; }

        /* Tabla Estilizada */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #f8fafc; color: #475569; text-transform: uppercase; font-size: 8px; font-weight: bold; padding: 10px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #f1f5f9; font-size: 10px; vertical-align: middle; }
        
        /* Status Badges */
        .badge { 
            padding: 3px 8px; 
            border-radius: 4px; 
            font-weight: bold; 
            font-size: 8px; 
            text-transform: uppercase;
            display: inline-block;
        }
        .level-badge { background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .passed-badge { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .failed-badge { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .absent-badge { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; } /* Ámbar para inasistencia */
        .present-badge { background-color: #ecfdf5; color: #065f46; border: 1px solid #d1fae5; }
        
        /* Puntaje resaltado */
        .score { font-weight: bold; color: #111827; font-size: 11px; }
        .score-low { color: #dc2626; } 
        
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #94a3b8; padding-top: 20px; border-top: 1px solid #f1f5f9; }
    </style>
</head>
<body>
    <div class="header">
        <div class="subtitle">EmiSystem - Educational Management</div>
        <h1 class="title">Historial General de Evaluaciones Orales</h1>
    </div>

    <div class="report-info">
        Generado el: <strong>{{ $date }}</strong><br>
        Total de estudiantes registrados: <strong>{{ count($results) }}</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Estudiante</th>
                <th style="text-align: center;">Idioma</th>
                <th style="width: 15%; text-align: center;">Asistencia</th>
                <th style="width: 12%; text-align: center;">Nivel</th>
                <th style="width: 12%; text-align: center;">Puntaje</th>
                <th style="width: 18%; text-align: center;">Resultado</th>                
                <th style="width: 18%; text-align: right;">Fecha</th>
            </tr>
        </thead>
        <tbody>
            @foreach($results as $res)
                @php
                    $attended = $res->attended ?? 1; // 1: Presente, 0: Inasistencia
                    // Solo aprueba si asistió Y tiene nivel B2+ Y nota >= 60
                    $isPassed = ($attended == 1) && in_array(strtoupper($res->final_level), ['B2', 'C1', 'C2']) && $res->final_score >= 60;
                @endphp
                <tr>
                    <td>
                        <div style="font-weight: bold; color: #111827;">{{ $res->name }} {{ $res->lastname }}</div>
                    </td>
                    <td style="text-align: center;">
                <span style="font-size: 10px; font-weight: bold; color: #475569; text-transform: uppercase;">
                    {{ $res->language_name ?? 'N/A' }}
                </span>
            </td>
                    <td style="text-align: center;">
                        <span class="badge {{ $attended == 1 ? 'present-badge' : 'absent-badge' }}">
                            {{ $attended == 1 ? 'Presente' : 'Inasistente' }}
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge level-badge">{{ $res->final_level ?? '0' }}</span>
                    </td>
                    <td style="text-align: center;" class="score {{ ($res->final_score < 60 || $attended == 0) ? 'score-low' : '' }}">
                        {{ number_format($res->final_score, 2) }}%
                    </td>
                    <td style="text-align: center;">
                        @if($attended == 0)
                            <span class="badge failed-badge" style="background-color: #f3f4f6; color: #374151; border: 1px solid #d1d5db;">
                                Sin Nota
                            </span>
                        @else
                            <span class="badge {{ $isPassed ? 'passed-badge' : 'failed-badge' }}">
                                {{ $isPassed ? 'Aprobado' : 'Reprobado' }}
                            </span>
                        @endif
                    </td>
                    
                    <td style="text-align: right; color: #64748b; font-size: 10px;">
                        {{ \Carbon\Carbon::parse($res->created_at)->format('d/m/Y') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Este documento es un registro oficial de calificaciones generado por EmiSystem el {{ $date }}.
    </div>
</body>
</html>