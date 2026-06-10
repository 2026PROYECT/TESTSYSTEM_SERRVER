<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Usuario Bloqueado</title>
    <style>
        @page { margin: 2cm; size: letter portrait; }
        body { font-family: 'Helvetica', Arial, sans-serif; color: #1e293b; font-size: 10px; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; }
        .info-box { background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; }
        th { background: #f1f5f9; font-size: 9px; text-transform: uppercase; color: #64748b; }
        .violation-row { border-left: 3px solid #111010; }
        .footer { margin-top: 30px; text-align: center; font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 15px; }
        h1 { font-size: 16px; margin: 0; color: #121111; }
        h2 { font-size: 12px; margin: 0 0 10px 0; color: #334155; }
        .text-muted { color: #64748b; font-size: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="text-align: left; border: none;">
                    <h1> REPORTE DE USUARIO BLOQUEADO</h1>
                    <p style="margin:5px 0; color: #64748b;">Sistema de Evaluación de Idiomas de la EMI</p>
                    <p class="text-muted">Generado por: {{ $generatedBy ?? 'Sistema' }} | {{ $generated_at->format('d/m/Y H:i:s') }}</p>
                </td>
                <td style="text-align: right; border: none; width: 100px;">
                    <img src="data:image/svg+xml;base64,{{ $qrCode }}" style="width: 75px;">
                    <p class="text-muted" style="margin-top: 5px;">Código: {{ $verificationId }}</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="info-box">
        <h2> Información del Usuario</h2>
        <p><strong>Nombre:</strong> {{ $user->name }} {{ $user->lastname ?? '' }}</p>
        <p><strong>Email:</strong> {{ $user->email }}</p>
        <p><strong>ID Usuario:</strong> {{ $user->id }}</p>
    </div>

    <div class="info-box">
        <h2>Exámenes Invalidados</h2>
        <table>
            <thead>
                <tr><th>ID Examen</th><th>Fecha</th><th>Estado</th><th>Violaciones</th></tr>
            </thead>
            <tbody>
                @forelse($invalidatedExams as $exam)
                <tr>
                    <td>{{ $exam->id }}</td>
                    <td>{{ \Carbon\Carbon::parse($exam->started_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $exam->status }}</td>
                    <td>{{ $exam->violations_count ?? 0 }}</td>
                </tr>
                @empty
                <td><td colspan="4">No hay exámenes invalidados</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="info-box">
        <h2> Historial de Violaciones</h2>
        <table>
            <thead>
                <tr><th>Fecha/Hora</th><th>Tipo</th><th>Detalles</th><th># Violación</th></tr>
            </thead>
            <tbody>
                @forelse($violations as $violation)
                <tr class="violation-row">
                    <td>{{ \Carbon\Carbon::parse($violation->created_at)->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $violation->event_type }}</td>
                    <td>{{ $violation->details ?? '-' }}</td>
                    <td>{{ $violation->violation_count }}</td>
                </tr>
                @empty
                <tr><td colspan="4">No hay registros de violaciones</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Documento generado automáticamente por EmiSystem - Reporte de Usuario Bloqueado</p>
        <p>Verifique la autenticidad de este documento en {{ url('/verify') }}/{{ $verificationId }}</p>
    </div>
</body>
</html>