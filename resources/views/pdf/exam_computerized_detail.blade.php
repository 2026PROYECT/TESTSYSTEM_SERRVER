<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
       @page {
    margin-top: 5cm;
    margin-right: 2cm;
    margin-bottom: 2.5cm;
    margin-left: 2cm;
}
        body { font-family: 'Arial', sans-serif; font-size: 11px; line-height: 1.2; }
        
        .table-container { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .table-container th, .table-container td { border: 1px solid #000; padding: 5px; text-align: center; }
        
        .title-header { font-weight: bold; font-size: 15px; text-align: center; margin-bottom: 10px; text-transform: uppercase; }
        .section-label { text-align: left !important; font-size: 12px; font-weight: bold; border: none !important; padding: 10px 0 5px 0; }

        .bg-gray { background-color: #ffffff; font-weight: bold; font-size: 9px; }
        .full-name { font-size: 11px; font-weight: bold; text-transform: uppercase; }
        
        /* Nota Final en Recuadro Grande */
        .final-score-box { font-size: 28px; font-weight: bold; vertical-align: middle; }
        
        .signature-table { width: 100%; margin-top: 60px; border: none !important; }
        .signature-table td { border: none !important; text-align: center; width: 50%; vertical-align: bottom; }
        .sig-line { border-top: 1px solid #000; width: 80%; margin: 0 auto; padding-top: 5px; font-weight: bold; text-transform: uppercase; font-size: 9px; }
    </style>
</head>
<body>

    <div class="title-header">
        PLANILLA DE EVALUACIÓN<br>
        EXAMEN DIAGNÓSTICO - AUTOMATIZADO-ESCRITO
    </div>

    <div class="section-label">I. INFORMACIÓN PERSONAL DEL EVALUADO</div>
<table class="table-container">
    <tr>
        <td class="bg-gray" width="20%"><strong>NOMBRE:</strong></td>
        <td class="full-name" width="55%" style="text-align: left; padding-left: 10px;">
            {{ $attempt->student->name }} {{ $attempt->student->lastname }} {{ $attempt->student->surname }}        </td>
        <td width="12%" rowspan="2" class="bg-gray"><div align="right"><strong>NOTA FINAL</strong></div></td>
        <td class="final-score-box" width="13%" rowspan="2" style="font-size: 24px; background: #f8fafc;">
            {{ $attempt->score }}        </td>
    </tr>
    
    <tr>
        <td colspan="2" style="padding: 0;">
            <table width="100%" style="border: none; border-collapse: collapse;">
                <tr>
                    <td class="bg-gray" style="border: none; border-right: 1px solid #000;" width="25%"><strong>CARRERA:</strong></td>
                    <td style="border: none; border-right: 1px solid #000; text-align: left; padding-left: 10px;" width="40%">
                        {{ strtoupper($attempt->student->studentProfile->career->name ?? 'NO ASIGNADA') }}                    </td>
                    <td class="bg-gray" style="border: none; border-right: 1px solid #000;" width="15%"><strong>SEM:</strong></td>
                    <td style="border: none; text-align: center;" width="20%">
                        {{ $attempt->student->studentProfile->semester ?? 'N/A' }}                    </td>
                </tr>
            </table>        </td>
    </tr>
    
    <tr>
        <td class="bg-gray"><strong>FECHA EVALUACIÓN</strong></td>
        <td colspan="3" style="text-align: left; padding-left: 10px;">
            {{ \Carbon\Carbon::parse($attempt->completed_at)->format('d \d\e F \d\e Y') }}        </td>
    </tr>
</table>

    <div class="section-label">II. RESULTADOS DE LA EVALUACIÓN</div>
    <table class="table-container">
        <tr>
            <th width="30%" rowspan="2">CRITERIO</th>
            <th colspan="2">PARCIAL</th>
            <th width="15%" rowspan="2">TOTAL<br>PREGUNTAS</th>
            <th width="15%" rowspan="2">NOTA<br>FINAL</th>
        </tr>
        <tr>
            <th width="15%">CORRECTAS</th>
            <th width="15%">INCORRECTAS</th>
        </tr>
        <tr>
            <td style="text-align: left; font-weight: bold;">COMPRENSIÓN AUDITIVA</td>
            <td>{{ $l_correct }}</td>
            <td>{{ $l_total - $l_correct }}</td>
            <td>{{ $l_total }}</td>
            <td rowspan="2" class="final-score-box">{{ $attempt->score }}</td>
        </tr>
        <tr>
            <td style="text-align: left; font-weight: bold;">COMPRENSIÓN DE LECTURA</td>
            <td>{{ $r_correct }}</td>
            <td>{{ $r_total - $r_correct }}</td>
            <td>{{ $r_total }}</td>
        </tr>
    </table>
 <div class="section-label">III. NIVEL DE CONOCIMIENTO ALCANZADO</div>
    <table class="table-container">
        
        <tr>
            <td style="padding: 15px; font-size: 14px; font-weight: bold;">
               @php
    $s = $attempt->score;
    
    if ($s >= 90) {
        $n = "C2 - MAESTRÍA";
    } elseif ($s >= 80) {
        $n = "C1 - AVANZADO DOMINIO OPERATIVO EFICAZ";
    } elseif ($s >= 60) {
        $n = "B2 - INTERMEDIO-ALTO";
    } elseif ($s >= 50) {
        $n = "B1 - INTERMEDIO";
    } elseif ($s >= 40) {
        $n = "A2 - BÁSICO";
    } elseif ($s >= 1) { // Rango 0-39.9
        $n = "A1 - PRINCIPIANTE";
    } else {
        $n = "SIN CALIFICACIÓN";
    }
@endphp

{{ $n }}
            </td>
        </tr>
    </table>

    <table class="signature-table">
        <tr>
            <td>
				 <div class="sig-line" style="text-transform: uppercase; font-weight: bold; margin-bottom: 5px;">
                    {{ $attempt->student->name }} {{ $attempt->student->lastname ?? '' }} {{ $attempt->student->surname ?? '' }}
                </div>
              CONFORMIDAD ESTUDIANTE</div>
            </td>
            <td>

            </td>
        </tr>
		<tr>
            <td>

            </td>
            <td>
                <div class="sig-line" style="text-transform: uppercase; font-weight: bold; margin-bottom: 5px;">
                    {{ auth()->user()->name }} {{ auth()->user()->lastname }} {{ auth()->user()->surname }}
                
                <div >EVALUADOR</div></div>
            </td>
        </tr>
    </table>
<div style="text-align: center; margin-top: 20px;">
        <img src="data:image/svg+xml;base64,{{ $qrcode }}" width="100" height="100">
        <p style="font-size: 7px; color: #64748b; margin-top: 5px; text-transform: uppercase;">
            Validación Electrónica de Autenticidad
        </p>
    </div>
</body>
</html>