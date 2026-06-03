<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Resultado de Evaluación</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .qr-container { text-align: center; margin-top: 30px; }
        .score { font-size: 48px; font-weight: bold; color: #059669; }
    </style>
</head>
<body>
    <h1>Resultado de Evaluación</h1>
    <p><strong>Estudiante:</strong> {{ $student->name }}</p>
    <p><strong>Puntaje:</strong> <span class="score">{{ $score }}%</span></p>
    <p><strong>Fecha:</strong> {{ $result->completed_at ? \Carbon\Carbon::parse($result->completed_at)->format('d/m/Y') : now()->format('d/m/Y') }}</p>
    
    <div class="qr-container">
        <img src="data:image/svg+xml;base64,{{ $qrcode }}" width="120">
        <p>Escanee para verificar autenticidad</p>
        <p><small>ID: {{ substr($verification_uuid, 0, 8) }}</small></p>
    </div>
</body>
</html>