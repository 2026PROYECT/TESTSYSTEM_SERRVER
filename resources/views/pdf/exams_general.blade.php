<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0.8cm; }
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            font-size: 9pt; 
            line-height: 1.2;
        }
        .header { text-align: center; border-bottom: 2px solid #000; margin-bottom: 15px; padding-bottom: 10px; }
        .institution { font-size: 14pt; font-weight: bold; margin: 0; }
        .report-title { font-size: 12pt; text-decoration: underline; font-weight: bold; margin: 5px 0; text-transform: uppercase; }
        
        .table-data { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table-data th { 
            background-color: #f2f2f2; 
            border: 1px solid #000; 
            padding: 6px; 
            font-size: 8pt;
            text-transform: uppercase;
        }
        .table-data td { border: 1px solid #000; padding: 5px; text-align: center; }
        .text-left { text-align: left !important; padding-left: 8px !important; }
        
        .result-passed { color: #15803d; font-weight: bold; }
        .result-failed { color: #b91c1c; font-weight: bold; }
        
        .footer { position: fixed; bottom: 0; width: 100%; font-size: 7pt; text-align: right; border-top: 1px solid #ccc; padding-top: 5px; }
        .summary { margin-top: 15px; font-weight: bold; font-size: 9pt; }
    </style>
</head>
<body>

    <div class="header">
        <div class="institution">EMI - SISTEMA DE EVALUACIÓN</div>
        <div class="report-title">{{ $title }}</div>
        <div style="font-size: 8pt;">Fecha de reporte: {{ $date }}</div>
    </div>

    <table class="table-data">
        <thead>
            <tr>
                <th width="4%">N°</th>
                <th class="text-left" width="28%">Estudiante</th>
                <th class="text-left" width="20%">Carrera</th>
                <th width="18%">Examen</th>
                <th width="10%">Fecha</th>
                <th width="6%">Hits</th>
                <th width="7%">Nota</th>
                <th width="10%">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attempts as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="text-left">{{ strtoupper($item->student->name) }}</td>
                <td class="text-left">{{ strtoupper($item->student->career->name ?? 'Sistemas') }}</td>
                <td>{{ $item->quiz->title }}</td>
                <td>{{ $item->completed_at->format('d/m/Y') }}</td>
                <td>{{ $item->correct_answers }}</td>
                <td style="font-weight: bold;">{{ number_format($item->score, 2) }}%</td>
                <td class="{{ $item->score >= 60 ? 'result-passed' : 'result-failed' }}">
                    {{ $item->score >= 60 ? 'APROBADO' : 'REPROBADO' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        Total de registros en este reporte: {{ count($attempts) }}
    </div>

    <div class="footer">
        Planilla generada por el Sistema de Gestión Académica - Página 1
    </div>

</body>
</html>