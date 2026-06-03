<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consolidado Oral - EmiSystem</title>
    <style>
        @page { margin: 2.5cm; size: letter portrait; }
        body { font-family: 'Helvetica', Arial, sans-serif; color: #1e293b; font-size: 10px; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: center; text-transform: uppercase; font-size: 9px; color: #64748b; padding: 10px; border-bottom: 2px solid #f1f5f9; }
        td { padding: 12px 8px; border-bottom: 1px solid #f8fafc; text-align: center; }
        .student-name { font-size: 11px; font-weight: bold; color: #000; display: block; text-align: left; }
        .student-career { font-size: 8px; color: #4f46e5; text-transform: uppercase; font-weight: bold; display: block; text-align: left; }
        .lang-tag { display: inline-block; padding: 3px 8px; border-radius: 10px; background-color: #eff6ff; color: #2563eb; font-weight: bold; border: 1px solid #dbeafe; }
        .text-emerald { color: #059669; font-weight: bold; }
        .text-rose { color: #e11d48; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="text-align: left; border: none;">
                    <h2 style="margin:0; font-size: 16px;">Consolidado de Exámenes Orales</h2>
                    <p style="margin:5px 0; color: #64748b;">Reporte exclusivo de entrevistas y nivelación oral</p>
                    <p style="font-size: 8px; color: #94a3b8;">Generado por: {{ $generatedBy }} | {{ $generationDate }}</p>
                </td>
                <td style="text-align: right; border: none; width: 100px;">
                    <img src="data:image/svg+xml;base64,{{ $qrcode }}" style="width: 75px;">
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th width="45%" style="text-align: left;">Estudiante / Carrera</th>
                <th width="15%">Idioma</th>
                <th width="25%">Intentos Orantes (Nivel - Nota)</th>
                <th width="15%">Resultado Final</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $row)
            <tr>
                <td style="text-align: left;">
                    <span class="student-name">{{ $row->full_name }}</span>
                    <span class="student-career">{{ $row->career_name }}</span>
                </td>
                <td><span class="lang-tag">{{ $row->language_name }}</span></td>
                <td>
                    @foreach($row->oral_all_attempts as $att)
                        <div style="margin-bottom: 5px;">
                            <span class="{{ (in_array(strtoupper($att->level), ['B2','C1','C2']) && $att->score >= 60) ? 'text-emerald' : 'text-rose' }}">
                                {{ $att->level }} - {{ number_format($att->score, 2) }}
                            </span><br>
                            <small style="color: #94a3b8;">{{ $att->date }}</small>
                        </div>
                    @endforeach
                </td>
                <td>
                    @php
                        $passed = false;
                        foreach($row->oral_all_attempts as $att) {
                            if(in_array(strtoupper($att->level), ['B2','C1','C2']) && $att->score >= 60) { $passed = true; break; }
                        }
                    @endphp
                    <span class="{{ $passed ? 'text-emerald' : 'text-rose' }}" style="font-size: 9px;">
                        {{ $passed ? 'APROBADO (ORAL)' : 'PENDIENTE' }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>