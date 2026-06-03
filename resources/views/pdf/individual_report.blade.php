<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Planilla de Evaluación Oral - {{ $student_full_name }}</title>
    <style>
        @page { margin: 0.3cm 0.8cm; }
        body { 
            margin-top: 1.5cm;
            font-family: 'Helvetica', 'Arial', sans-serif; 
            font-size: 8.5pt; 
            color: #000;
            line-height: 1.1;
        }
        .main-title { text-align: center; font-weight: bold; text-decoration: underline; margin-bottom: 2px; font-size: 14pt; }
        .main-subtitle { text-align: center; margin-bottom: 10px; font-size: 12pt; }
        
        /* Tablas de Información */
        .table-info { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .table-info td { border: 1px solid #000; padding: 4px; text-transform: uppercase; font-size: 7.5pt; }
        .label { font-weight: bold; background-color: #f2f2f2; width: 25%; }
        .val-center { text-align: center; font-weight: bold; }
        
        /* Badge de Estado */
        .status-badge { font-size: 7.5pt; font-weight: bold; padding: 2px; display: block; margin: 2px auto 0 auto; border: 1px solid #000; width: 85%; text-align: center; }
        
        /* Secciones */
        .section-header { margin: 8px 0 4px 0; text-transform: uppercase; padding-left: 5px; font-size: 11pt; font-weight: bold;  }
        
        /* Grid de Niveles */
        .levels-grid { width: 100%; }
        .level-card { width: 32%; float: left; margin-right: 1.3%; margin-bottom: 6px; border: 1px solid #000; }
        .level-card:nth-child(3n) { margin-right: 0; }
        .level-head { background-color: #e6e6e6; text-align: center; font-weight: bold; border-bottom: 1px solid #000; padding: 2px; font-size: 8pt; }
        
        /* Criterios y Barras */
        .crit-row { padding: 2px 4px; border-bottom: 1px solid #eee; }
        .crit-name { font-size: 6pt; font-weight: bold; margin-bottom: 1px; }
        .bar-container { width: 100%; height: 5px; background-color: #ddd; border: 0.3px solid #000; }
        .bar-fill { height: 100%; background-color: #333; }
        
        /* Observaciones y Firmas */
        .feedback-area { border: 1px solid #000; padding: 5px; min-height: 40px; font-style: italic; margin-bottom: 15px; font-size: 8pt; }
        .signature-wrap { width: 100%; margin-top: 50px; }
        .sig-box { width: 45%; text-align: center; }
        .sig-line { border-top: 1px solid #000; width: 80%; margin: 0 auto 3px auto; }
        .sig-name { font-size: 7.5pt; font-weight: bold; text-transform: uppercase; }
        .sig-label { font-size: 6.5pt; color: #444; }
        
        .clear { clear: both; }

        /* QR y Verificación */
        .qr-section {
            position: absolute;
            bottom: 20px;
            left: 40px;
            text-align: center;
        }
        .qr-text {
            font-size: 6pt;
            font-weight: bold;
            color: #555;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        .lang-badge {
            background-color: #1e293b;
            color: white;
            padding: 1px 5px;
            border-radius: 2px;
            font-size: 8pt;
        }
    </style>
</head>
<body>

    @php
        /** * Lógica de aprobación del sistema EmiSystem
         * Requiere nivel B2+ y nota mínima de 60%
         */
        $nivelNormalizado = strtoupper(trim($displayLevel));
        $nivelesValidos = ['B2', 'C1', 'C2'];
        $estaAprobado = in_array($nivelNormalizado, $nivelesValidos) && ($displayScore >= 60);
        
        $colorEstado = $estaAprobado ? '#15803d' : '#b91c1c';
        $bgBadge = $estaAprobado ? '#16a34a' : '#dc2626';
    @endphp

    <div style="margin-top: 1.5cm;">
        <div class="main-title">PLANILLA DE EVALUACIÓN INDIVIDUAL</div>
        <div class="main-subtitle">EXAMEN ORAL DE IDIOMA EXTRANJERO</div>

        <div class="section-header">I. INFORMACIÓN PERSONAL DEL EVALUADO</div>
        <table class="table-info">
            <tr>
                <td class="label">NOMBRE EVALUADO</td>
                <td class="val-center" colspan="2">{{ $student_full_name }}</td>
                <td class="label" style="text-align: center; width: 25%;">NOTA FINAL / ESTADO</td>
            </tr>
            <tr>
                <td class="label">CARRERA</td>
                <td class="val-center" colspan="2">{{ $career_name }}</td>
                <td class="nota-box" rowspan="4" style="text-align: center; vertical-align: middle;">
                    <div style="font-size: 16pt; font-weight: bold; color: {{ $colorEstado }};">
                        {{ number_format($displayScore, 2) }}%
                    </div>
                    <div style="font-size: 7pt; color: #444; font-weight: bold; margin-bottom: 4px;">
                        NIVEL: {{ $displayLevel }}
                    </div>
                    <span class="status-badge" style="color: white; background-color: {{ $bgBadge }};">
                        {{ $estaAprobado ? 'APROBADO' : 'REPROBADO' }}
                    </span>
                </td>
            </tr>
            <tr>
                <td class="label">IDIOMA EVALUADO</td>
                <td class="val-center" colspan="2">
                    <span class="lang-badge">{{ $language_name }}</span>
                </td>
            </tr>
            <tr>
                <td class="label">SEMESTRE / CURSO</td>
                <td class="val-center" colspan="2">{{ $semester }}</td>
            </tr>
            <tr>
                <td class="label">FECHA EVALUACIÓN</td>
                <td class="val-center" colspan="2">{{ $date }}</td>
            </tr>
        </table>

        <div class="section-header">II. DESGLOSE DE NIVELES ALCANZADOS</div>
        <div class="levels-grid">
            @php $displayCount = 0; @endphp
            @foreach($detailed_scores as $lvl => $data)
                @php 
                    $sumaCriterios = collect($data)->except('completed')->sum();
                @endphp
                
                @if($sumaCriterios > 0)
                    @php 
                        $displayCount++; 
                        $currentAvg = collect($data)->except('completed')->avg();
                        $isLvlPassed = $currentAvg >= 60;
                    @endphp
                    <div class="level-card" style="border: 1px solid {{ $isLvlPassed ? '#000' : '#b91c1c' }};">
                        <div class="level-head" style="background-color: {{ $isLvlPassed ? '#e6e6e6' : '#fee2e2' }}; color: {{ $isLvlPassed ? '#000' : '#b91c1c' }};">
                            NIVEL {{ $lvl }} {{ $isLvlPassed ? '' : '(PENDIENTE)' }}
                        </div>
                        
                        @foreach(['vocabulario', 'gramatica', 'fluidez', 'pronunciacion', 'contenido'] as $c)
                            <div class="crit-row">
                                <div class="crit-name">
                                    {{ strtoupper($c) }} 
                                    <span style="float: right;">{{ number_format($data[$c] ?? 0, 1) }}%</span>
                                </div>
                                <div class="bar-container">
                                    <div class="bar-fill" style="width: {{ $data[$c] ?? 0 }}%; background-color: {{ $isLvlPassed ? '#333' : '#dc2626' }};"></div>
                                </div>
                            </div>
                        @endforeach
                        
                        <div style="font-size: 6.5pt; text-align: center; padding: 3px; font-weight: bold; background: #f8fafc; color: {{ $isLvlPassed ? '#065f46' : '#b91c1c' }};">
                            PROMEDIO: {{ number_format($currentAvg, 2) }}%
                        </div>
                    </div>
                    @if($displayCount % 3 == 0) <div class="clear"></div> @endif
                @endif
            @endforeach
        </div>

        <div class="clear"></div>

        <div class="section-header">III. OBSERVACIONES DEL EVALUADOR</div>
        <div class="feedback-area">
            {{ $teacher_feedback ?? 'El estudiante demuestra las competencias lingüísticas correspondientes al nivel asignado según el marco de referencia institucional.' }}
        </div>
<p></p>
<p></p>
        <div class="signature-wrap">
            <div class="sig-box" style="float: left;">
                <div class="sig-line"></div>
                <div class="sig-name">{{ $student_full_name }}</div>
                <div class="sig-label">FIRMA DEL ESTUDIANTE</div>
            </div>

            <div class="clear"></div>
        </div>
         <div class="signature-wrap">

            
            <div class="sig-box" style="float: right;">
                <div class="sig-line"></div>
                <div class="sig-name">{{ $teacher_full_name }}</div>
                <div class="sig-label">DOCENTE EVALUADOR</div>
            </div>
            <div class="clear"></div>
        </div>
    </div>

    <div class="qr-section">
        <div class="qr-text">Verificación Digital</div>
        <img src="data:image/svg+xml;base64,{{ $qrcode }}" width="110" height="110">
        <div style="font-size: 5pt; color: #888; margin-top: 2px;">REG-ID: {{ $student_id }} | EmiSystem v3</div>
    </div>

</body>
</html>