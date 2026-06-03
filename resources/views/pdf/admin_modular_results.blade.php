<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Examen Modular</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
        }
        .info {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #f0f0f0;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            text-align: center;
            font-size: 8px;
            margin-top: 30px;
        }
        .qr-code {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE EXAMEN MODULAR</h1>
        <p>{{ $language_name }}</p>
    </div>

    <div class="info">
        <p><strong>Estudiante:</strong> {{ $student_name }}</p>
        <p><strong>Email:</strong> {{ $student_email }}</p>
        <p><strong>Carrera:</strong> {{ $career }}</p>
        <p><strong>Fecha:</strong> {{ $date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nivel</th>
                <th>Puntaje</th>
                <th>Porcentaje</th>
            </tr>
        </thead>
        <tbody>
            @foreach($levels as $level => $data)
            <tr>
                <td class="text-center"><strong>{{ $level }}</strong></td>
                <td class="text-center">{{ $data['score'] }}/{{ $data['total'] }}</td>
                <td class="text-center">{{ $data['percentage'] }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th>Habilidad</th>
                <th>Puntaje</th>
                <th>Porcentaje</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>🎧 Listening</td>
                <td class="text-center">{{ $by_type['listening']['score'] ?? 0 }}/{{ $by_type['listening']['total'] ?? 0 }}</td>
                <td class="text-center">{{ $by_type['listening']['percentage'] ?? 0 }}%</td>
            </tr>
            <tr>
                <td>📖 Reading</td>
                <td class="text-center">{{ $by_type['reading']['score'] ?? 0 }}/{{ $by_type['reading']['total'] ?? 0 }}</td>
                <td class="text-center">{{ $by_type['reading']['percentage'] ?? 0 }}%</td>
            </tr>
        </tbody>
    </table>

    <div class="info">
        <p><strong>Puntaje Total:</strong> {{ $total_score }}/{{ $total_points }} ({{ $total_percentage }}%)</p>
        <p><strong>Estado:</strong> {{ $passed ? 'APROBADO' : 'REPROBADO' }}</p>
    </div>

    <div class="qr-code">
        @if($qrCode)
            <img src="data:image/svg+xml;base64,{{ $qrCode }}" width="80" height="80">
            <p>Validación Electrónica</p>
        @endif
    </div>

    <div class="footer">
        <p>Documento oficial de EMI System - Sistema de Evaluación de Idiomas</p>
        <p>Generado por: {{ $teacher_name }}</p>
    </div>
</body>
</html>