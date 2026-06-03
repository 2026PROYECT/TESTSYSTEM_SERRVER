<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #4f46e5; color: white; padding: 10px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .header { text-align: center; margin-bottom: 30px; }
        .score { font-weight: bold; }
        .passed { color: #059669; }
        .failed { color: #dc2626; }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE CONSOLIDADO DE CALIFICACIONES</h1>
        <p>EmiSystem - Fecha: {{ $date }}</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Estudiante</th>
                <th>Carrera</th>
                <th>Oral Test</th>
                <th>Comp Test</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $s)
            <tr>
                <td>{{ $s['full_name'] }}</td>
                <td>{{ $s['career'] }}</td>
                <td class="score {{ is_numeric($s['oral_score']) && $s['oral_score'] >= 51 ? 'passed' : 'failed' }}">
                    {{ $s['oral_score'] }}
                </td>
                <td class="score {{ is_numeric($s['comp_score']) && $s['comp_score'] >= 51 ? 'passed' : 'failed' }}">
                    {{ $s['comp_score'] }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>