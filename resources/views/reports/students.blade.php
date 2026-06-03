<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Estudiantes</title>
    <style>
        @page { margin: 100px 25px; }
        header { position: fixed; top: -60px; left: 0px; right: 0px; height: 50px; text-align: center; line-height: 35px; }
        footer { position: fixed; bottom: -60px; left: 0px; right: 0px; height: 50px; text-align: center; line-height: 35px; font-size: 10px; color: #7f8c8d; }
        
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; }
        .title-container { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #4f46e5; padding-bottom: 10px; }
        .title { font-size: 22px; font-weight: bold; color: #1e293b; margin: 0; }
        .subtitle { font-size: 14px; color: #64748b; margin: 5px 0; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; table-layout: fixed; }
        th { background-color: #4f46e5; color: white; padding: 12px 8px; text-align: left; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px; }
        td { padding: 10px 8px; border-bottom: 1px solid #e2e8f0; word-wrap: break-word; }
        tr:nth-child(even) { background-color: #f8fafc; }
        
        .badge { padding: 4px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; background-color: #e2e8f0; color: #475569; }
        .text-bold { font-weight: bold; color: #334155; }
        .info-footer { margin-top: 30px; font-size: 11px; }
    </style>
</head>
<body>
    <header>
        <strong>Institución Académica</strong> | Sistema de Gestión de Inscripciones
    </header>

    <footer>
        Reporte generado automáticamente - Página <script type="text/php">if (isset($pdf)) { $text = "{PAGE_NUM} de {PAGE_COUNT}"; $font = null; $size = 10; $word_space = 0.0; $char_space = 0.0; $angle = 0.0; $pdf->page_text($font, $size, $text, $word_space, $char_space, $angle); }</script>
    </footer>

    <main>
        <div class="title-container">
            <h1 class="title">{{ $title }}</h1>
            <p class="subtitle">Fecha de emisión: {{ $date }}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="25%">Estudiante</th>
                    <th width="15%">C.I. / ID</th>
                    <th width="25%">Carrera</th>
                    <th width="10%">Sem.</th>
                    <th width="25%">Contacto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($students as $user)
                <tr>
                    <td>
                        <div class="text-bold">{{ $user->name }} {{ $user->lastname }}</div>
                        <div style="font-size: 9px; color: #64748b;">Cod: {{ $user->student->saga_code ?? 'N/A' }}</div>
                    </td>
                    <td>{{ $user->student->id_number ?? 'N/A' }}</td>
                    <td>{{ $user->student->career->name ?? 'Sin carrera' }}</td>
                    <td style="text-align: center;">
                        <span class="badge">{{ $user->student->semester }}°</span>
                    </td>
                    <td>
                        <div>{{ $user->email }}</div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="info-footer">
            <p><strong>Total de estudiantes registrados:</strong> {{ $total }}</p>
            <p style="color: #94a3b8; font-style: italic;">Este documento es un reporte oficial del sistema.</p>
        </div>
    </main>
</body>
</html>