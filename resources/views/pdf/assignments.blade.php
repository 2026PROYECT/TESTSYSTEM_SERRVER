<!DOCTYPE html>
<html>
<head>
    <title>Reporte de Autorizaciones</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #eee; padding: 10px; text-align: left; }
        th { background-color: #4f46e5; color: white; text-transform: uppercase; font-size: 10px; }
        .badge { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 10px; }
        .oral { background-color: #fffbeb; color: #92400e; }
        .comp { background-color: #eef2ff; color: #3730a3; }
    </style>
</head>
<body>
    <div class="header">
        <h1>EMISYSTEM</h1>
        <p>Reporte de Estudiantes Autorizados para Examen</p>
        <p>Fecha: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Estudiante</th>
                <th>Email</th>
                <th>Modalidad</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($assignments as $asig)
            <tr>
                <<td>{{ $asig->student?->name }} {{ $asig->student?->lastname }}</td>
<td>{{ $asig->student?->email }}</td>
                <td>
                    <span class="badge {{ $asig->test_type === 'OralTest' ? 'oral' : 'comp' }}">
                        {{ $asig->test_type === 'OralTest' ? 'ORAL' : 'COMPUTER' }}
                    </span>
                </td>
                <td>{{ $asig->active ? 'ACTIVO' : 'BLOQUEADO' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>