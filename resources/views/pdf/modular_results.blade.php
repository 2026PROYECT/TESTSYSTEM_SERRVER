<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado de Evaluación Modular - Parte 2/2</title>
    <style>
        @page { margin: 1.5cm; }
        body {
            font-family: 'Helvetica', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .subtitle {
            font-size: 10px;
            color: #666;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 8px;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            margin: 10px 0;
            border-radius: 4px;
        }
        .info {
            width: 100%;
            margin: 15px 0;
            border-collapse: collapse;
        }
        .info td {
            padding: 5px;
            border: none;
        }
        .info td:first-child {
            font-weight: bold;
            width: 100px;
        }
        .score-box {
            text-align: center;
            background: #f5f5f5;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .score-number {
            font-size: 42px;
            font-weight: bold;
            color: #059669;
        }
        .failed {
            color: #dc2626;
        }
        .levels-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .levels-table th {
            background: #f0f0f0;
            padding: 8px;
            text-align: center;
            border: 1px solid #ddd;
            font-weight: bold;
        }
        .levels-table td {
            padding: 6px;
            text-align: center;
            border: 1px solid #ddd;
        }
        .levels-table td:first-child {
            text-align: left;
            font-weight: bold;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .summary-table td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
        }
        .summary-table td:first-child {
            font-weight: bold;
        }
        .summary-table td:last-child {
            text-align: right;
        }
        .total-row {
            background: #f0fdf4;
            font-weight: bold;
        }
        .qr {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
        .qr img {
            width: 70px;
            height: 70px;
        }
        .qr-text {
            font-size: 7px;
            color: #999;
            margin-top: 3px;
        }
        .footer {
            text-align: center;
            font-size: 8px;
            color: #999;
            margin-top: 20px;
        }
        .part-info {
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            margin: 10px 0;
            color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Resultado de Evaluación Modular</div>
        <div class="subtitle">{{ $language_name }}</div>
    </div>

    <!-- Indicador de Parte 2/2 -->
    <div class="part-info">
         PARTE 2 DE 2 
    </div>

    <!-- ADVERTENCIA IMPORTANTE -->
    <div class="warning">
        IMPORTANTE: Este documento corresponde únicamente a la SEGUNDA PARTE del examen modular. 
        No se considera válido ni finalizado sin la aprobación de la PRIMERA PARTE. 
        El certificado completo será emitido únicamente al finalizar exitosamente ambas partes.
    </div>

    <table class="info">
        <tr>
            <td>Estudiante:</td>
            <td><strong>{{ $student_name }}</strong></td>
        </tr>
        <tr>
            <td>Fecha:</td>
            <td>{{ $date }}</td>
        </tr>
        <tr>
            <td>Carrera:</td>
            <td>{{ $career }}</td>
        </tr>
    </table>

    <div class="score-box">
        <div>PUNTAJE TOTAL - PARTE 2</div>
        <div class="score-number">{{ $total_percentage }}%</div>
        <div>{{ $total_score }}/{{ $total_points }} puntos</div>
        <div style="font-weight: bold; margin-top: 5px;">
            {{ $passed ? 'APROBADO (PARTE 2)' : 'REPROBADO (PARTE 2)' }}
        </div>
    </div>

    <!-- Tabla de resultados por nivel -->
    <h4>Resultados por Nivel (Parte 2)</h4>
    <table class="levels-table">
        <thead>
            <tr>
                <th>Nivel</th>
                <th>Puntaje</th>
                <th>Total Puntos</th>
                <th>Porcentaje</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalScoreByLevel = 0;
                $totalPointsByLevel = 0;
            @endphp
            
            @foreach($levels as $levelName => $levelData)
                @if(($levelData['total'] ?? 0) > 0)
                    @php
                        $totalScoreByLevel += $levelData['score'];
                        $totalPointsByLevel += $levelData['total'];
                    @endphp
                    <tr>
                        <td><strong>{{ $levelName }}</strong></td>
                        <td>{{ $levelData['score'] ?? 0 }}</td>
                        <td>{{ $levelData['total'] ?? 0 }}</td>
                        <td style="color: {{ ($levelData['percentage'] ?? 0) >= 60 ? '#059669' : '#dc2626' }}; font-weight: bold;">
                            {{ $levelData['percentage'] ?? 0 }}%
                        </td>
                        <td style="color: {{ ($levelData['percentage'] ?? 0) >= 60 ? '#059669' : '#dc2626' }};">
                            {{ ($levelData['percentage'] ?? 0) >= 60 ? 'Aprobado' : 'Reprobado' }}
                        </td>
                    </tr>
                @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td style="background: #f0fdf4;"><strong>TOTAL GENERAL PARTE 2</strong></td>
                <td style="background: #f0fdf4;"><strong>{{ $total_score }}</strong></td>
                <td style="background: #f0fdf4;"><strong>{{ $total_points }}</strong></td>
                <td style="background: #f0fdf4; color: #059669; font-weight: bold;">
                    <strong>{{ $total_percentage }}%</strong>
                </td>
                <td style="background: #f0fdf4;">&nbsp;</td>
            </tr>
        </tfoot>
    </table>

    <!-- Resumen por habilidad -->
    <h4>Resumen por Habilidad (Parte 2)</h4>
    <table class="summary-table">
        <tr>
            <td>Comprensión Auditiva (Listening) - Parte 2</td>
            <td>{{ $by_type['listening']['score'] ?? 0 }}/{{ $by_type['listening']['total'] ?? 0 }} 
                ({{ $by_type['listening']['percentage'] ?? 0 }}%)</td>
        </tr>
        <tr>
            <td>Comprensión de Lectura (Reading) - Parte 2</td>
            <td>{{ $by_type['reading']['score'] ?? 0 }}/{{ $by_type['reading']['total'] ?? 0 }} 
                ({{ $by_type['reading']['percentage'] ?? 0 }}%)</td>
        </tr>
    </table>

    <!-- Nota final importante -->
    <div class="warning" style="background: #e8f4f8; border-color: #2196f3; color: #0c5460; margin-top: 20px;">
        NOTA: Este documento certifica únicamente la finalización de la SEGUNDA PARTE del examen modular.
        Para obtener el certificado final aprobatorio, es necesario completar y aprobar AMBAS partes del examen.
        El estudiante deberá presentar ambos documentos (Parte 1 y Parte 2) como evidencia completa.
    </div>

    <div class="qr">
        <img src="data:image/svg+xml;base64,{{ $qrCode }}">
        <div class="qr-text">Documento Electrónico de Validación - Parte 2/2</div>
    </div>

    <div class="footer">
        Generado por Sistema de Evaluación de Idiomas | Documento válido solo con la Parte 1
    </div>
</body>
</html>