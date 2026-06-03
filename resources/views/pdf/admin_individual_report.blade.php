<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Administrativo - {{ $student_full_name }}</title>
    <style>
        @page { 
            margin: 0.5cm 1cm;
            size: A4;
        }
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            font-size: 9pt; 
            color: #1a1a2e;
            line-height: 1.2;
        }
        
        /* Encabezado Administrativo */
        .admin-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            position: relative;
        }
        .admin-badge {
            position: absolute;
            top: 10px;
            right: 15px;
            background: #ef4444;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .main-title { 
            text-align: center; 
            font-weight: bold; 
            margin-bottom: 5px; 
            font-size: 16pt; 
            color: #fff;
        }
        .main-subtitle { 
            text-align: center; 
            margin-bottom: 0; 
            font-size: 10pt;
            color: #cbd5e1;
        }
        
        /* Tablas de Información */
        .table-info { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 15px; 
            background: #f8fafc;
            border-radius: 8px;
            overflow: hidden;
        }
        .table-info td { 
            border: 1px solid #e2e8f0; 
            padding: 6px 8px; 
            text-transform: uppercase; 
            font-size: 8pt; 
        }
        .label { 
            font-weight: bold; 
            background-color: #e2e8f0; 
            width: 25%; 
        }
        .val-center { 
            text-align: center; 
            font-weight: bold; 
        }
        
        /* Badge de Estado */
        .status-badge { 
            font-size: 9pt; 
            font-weight: bold; 
            padding: 4px; 
            display: block; 
            margin: 5px auto 0 auto; 
            border: 1px solid #000; 
            width: 85%; 
            text-align: center;
            border-radius: 6px;
        }
        
        /* Secciones */
        .section-header { 
            margin: 12px 0 6px 0; 
            text-transform: uppercase; 
            padding-left: 5px; 
            font-size: 11pt; 
            font-weight: bold;
            background: #f1f5f9;
            padding: 6px;
            border-left: 4px solid #3b82f6;
        }
        
        /* Grid de Niveles */
        .levels-grid { 
            width: 100%; 
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .level-card { 
            flex: 1;
            min-width: 200px;
            border: 1px solid #e2e8f0; 
            border-radius: 8px;
            overflow: hidden;
            background: white;
        }
        .level-head { 
            background-color: #f1f5f9; 
            text-align: center; 
            font-weight: bold; 
            border-bottom: 1px solid #e2e8f0; 
            padding: 6px; 
            font-size: 9pt; 
        }
        
        /* Criterios y Barras */
        .crit-row { 
            padding: 5px 8px; 
            border-bottom: 1px solid #f1f5f9; 
        }
        .crit-name { 
            font-size: 7pt; 
            font-weight: bold; 
            margin-bottom: 3px; 
            text-transform: uppercase;
        }
        .bar-container { 
            width: 100%; 
            height: 6px; 
            background-color: #e2e8f0; 
            border-radius: 3px;
            overflow: hidden;
        }
        .bar-fill { 
            height: 100%; 
            background: linear-gradient(90deg, #3b82f6, #1d4ed8);
            transition: width 0.3s ease;
        }
        
        /* Observaciones y Firmas */
        .feedback-area { 
            border: 1px solid #e2e8f0; 
            padding: 10px; 
            min-height: 50px; 
            background: #fefce8;
            margin-bottom: 20px; 
            font-size: 8pt;
            border-radius: 8px;
        }
        
        .signature-wrap { 
            width: 100%; 
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }
        .sig-box { 
            width: 45%; 
            text-align: center; 
        }
        .sig-line { 
            border-top: 1px solid #000; 
            width: 80%; 
            margin: 0 auto 5px auto; 
        }
        .sig-name { 
            font-size: 8pt; 
            font-weight: bold; 
            text-transform: uppercase; 
        }
        .sig-label { 
            font-size: 7pt; 
            color: #64748b; 
        }
        
        /* Sello de Verificación Admin */
        .admin-verification {
            margin-top: 30px;
            padding: 10px;
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 8px;
            text-align: center;
            font-size: 7pt;
            color: #92400e;
        }
        
        /* QR y Verificación */
        .qr-section {
            position: relative;
            text-align: center;
            margin-top: 20px;
        }
        .qr-text {
            font-size: 7pt;
            font-weight: bold;
            color: #64748b;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .lang-badge {
            background-color: #1e293b;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 8pt;
            display: inline-block;
        }
        
        .watermark {
            position: fixed;
            bottom: 20px;
            right: 20px;
            opacity: 0.1;
            font-size: 40pt;
            font-weight: bold;
            transform: rotate(-15deg);
            pointer-events: none;
        }
        
        .footer-admin {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 6pt;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }
        
        .clear { clear: both; }
    </style>
</head>
<body>

<div class="watermark">ADMIN COPIA</div>

<div class="admin-header">
    <div class="admin-badge">Copia Administrativa</div>
    <div class="main-title">REPORTE OFICIAL DE EVALUACIÓN</div>
    <div class="main-subtitle">Sistema de Evaluación de Idiomas - Administración</div>
</div>

@php
    $nivelNormalizado = strtoupper(trim($displayLevel));
    $nivelesValidos = ['B2', 'C1', 'C2'];
    $estaAprobado = in_array($nivelNormalizado, $nivelesValidos) && ($displayScore >= 60);
    
    $colorEstado = $estaAprobado ? '#059669' : '#dc2626';
    $bgBadge = $estaAprobado ? '#10b981' : '#ef4444';
@endphp

<div>
    <div class="section-header">I. INFORMACIÓN DEL EVALUADO</div>
    <table class="table-info">
        <tr>
            <td class="label">NOMBRE COMPLETO</td>
            <td class="val-center" colspan="2">{{ $student_full_name }}</td>
            <td class="label" style="text-align: center; width: 25%;">CALIFICACIÓN</td>
        </tr>
        <tr>
            <td class="label">CARRERA / PROGRAMA</td>
            <td class="val-center" colspan="2">{{ $career_name }}</td>
            <td class="nota-box" rowspan="3" style="text-align: center; vertical-align: middle; background: #f8fafc;">
                <div style="font-size: 20pt; font-weight: bold; color: {{ $colorEstado }};">
                    {{ number_format($displayScore, 2) }}%
                </div>
                <div style="font-size: 9pt; color: #475569; font-weight: bold; margin-bottom: 5px;">
                    NIVEL ALCANZADO: {{ $displayLevel }}
                </div>
                <span class="status-badge" style="color: white; background-color: {{ $bgBadge }};">
                    {{ $estaAprobado ? 'APROBADO' : 'REPROBADO' }}
                </span>
            </td>
        </tr>
        <tr>
            <td class="label">IDIOMA</td>
            <td class="val-center" colspan="2">
                <span class="lang-badge">{{ $language_name }}</span>
            </td>
        </tr>
        <tr>
            <td class="label">FECHA EVALUACIÓN</td>
            <td class="val-center" colspan="2">{{ $date }}</td>
        </tr>
    </table>

    <div class="section-header">II. DESGLOSE DE COMPETENCIAS POR NIVEL</div>
    <div class="levels-grid">
        @foreach($detailed_scores as $lvl => $data)
            @php 
                $sumaCriterios = collect($data)->except('completed')->sum();
            @endphp
            
            @if($sumaCriterios > 0)
                @php 
                    $currentAvg = collect($data)->except('completed')->avg();
                    $isLvlPassed = $currentAvg >= 60;
                @endphp
                <div class="level-card">
                    <div class="level-head" style="background-color: {{ $isLvlPassed ? '#dbeafe' : '#fee2e2' }}; color: {{ $isLvlPassed ? '#1e40af' : '#991b1b' }};">
                        NIVEL {{ $lvl }} {{ $isLvlPassed ? '✓ APROBADO' : '✗ PENDIENTE' }}
                    </div>
                    
                    @foreach(['vocabulario', 'gramatica', 'fluidez', 'pronunciacion', 'contenido'] as $c)
                        <div class="crit-row">
                            <div class="crit-name">
                                {{ strtoupper($c) }} 
                                <span style="float: right;">{{ number_format($data[$c] ?? 0, 1) }}%</span>
                            </div>
                            <div class="bar-container">
                                <div class="bar-fill" style="width: {{ $data[$c] ?? 0 }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                    
                    <div style="font-size: 7pt; text-align: center; padding: 6px; font-weight: bold; background: #f8fafc;">
                        PROMEDIO DEL NIVEL: {{ number_format($currentAvg, 2) }}%
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <div class="clear"></div>

    <div class="section-header">III. OBSERVACIONES DEL EVALUADOR</div>
    <div class="feedback-area">
        {!! nl2br(e($teacher_feedback)) !!}
    </div>

    <div class="section-header">IV. INFORMACIÓN ADMINISTRATIVA</div>
    <table class="table-info">
        <tr>
            <td class="label" style="width: 30%">ID DE EVALUACIÓN</td>
            <td>{{ $student_id }}</td>
            <td class="label" style="width: 30%">FECHA DE EMISIÓN</td>
            <td>{{ date('d/m/Y H:i:s') }}</td>
        </tr>
        <tr>
            <td class="label">CÓDIGO DE VERIFICACIÓN</td>
            <td colspan="3">{{ uniqid('EMI-ADMIN-') }}</td>
        </tr>
    </table>

    <div class="signature-wrap">
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-name">{{ $student_full_name }}</div>
            <div class="sig-label">FIRMA DEL ESTUDIANTE</div>
        </div>

        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-name">{{ $teacher_full_name }}</div>
            <div class="sig-label">DOCENTE EVALUADOR</div>
        </div>

        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-name">Administración EMI</div>
            <div class="sig-label">SELLO Y VALIDACIÓN</div>
        </div>
    </div>
    
    <div class="admin-verification">
        <strong>DOCUMENTO DE VALIDACIÓN ADMINISTRATIVA</strong><br>
        Este documento es una copia oficial emitida por el sistema administrativo. 
        Verificar autenticidad en: sistema.emi.edu.bo/verificar/{{ $student_id }}
    </div>

    <div class="qr-section">
        <div class="qr-text">VERIFICACIÓN DIGITAL ADMINISTRATIVA</div>
        <img src="data:image/svg+xml;base64,{{ $qrcode }}" width="100" height="100">
        <div style="font-size: 6pt; color: #94a3b8; margin-top: 5px;">
            REG-ID: {{ $student_id }} | EmiSystem v3 | Admin Copy
        </div>
    </div>
</div>

<div class="footer-admin">
    Este reporte fue generado automáticamente por el Sistema de Administración EMI. 
    Fecha de generación: {{ now()->format('d/m/Y H:i:s') }}
</div>

</body>
</html>