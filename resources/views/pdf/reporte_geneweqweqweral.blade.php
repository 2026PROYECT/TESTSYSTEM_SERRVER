<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte General</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h2>Consolidado General</h2>
    <p>Generado por: {{ $generatedBy }} | Fecha: {{ $generationDate }}</p>
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="data:image/svg+xml;base64,{{ $qrcode }}" width="70">
    </div>
    
    <tr>
        <thead>
            <tr>
                <th>Estudiante</th>
                <th>Carrera</th>
                <th>Idioma</th>
                <th>Oral</th>
                <th>Examen Modular</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $student)
            <tr>
                <td>{{ $student['full_name'] }}</td>
                <td>{{ $student['career_name'] }}</td>
                <td>{{ $student['language_name'] }}</td>
                <td>
                    @if($student['oral_score'])
                        {{ $student['oral_level'] }} - {{ $student['oral_score'] }}%
                        <br><small>{{ $student['oral_date'] }}</small>
                    @else
                        Sin registros
                    @endif
                 </td>
                <td>
                    @foreach($student['modular_attempts'] as $attempt)
                        {{ $attempt['score'] }}% 
                        <small>({{ $attempt['date'] }})</small><br>
                    @endforeach
                    @if(count($student['modular_attempts']) == 0)
                        Sin registros
                    @endif
                 </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>