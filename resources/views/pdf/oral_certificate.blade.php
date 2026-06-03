<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado Examen Oral - EMI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            background: white;
            padding: 15px;
            font-size: 8px;
        }
        
        /* Marca de agua */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 35px;
            color: rgba(220, 38, 38, 0.06);
            white-space: nowrap;
            font-weight: bold;
            pointer-events: none;
            z-index: 1000;
        }
        
        /* Cabecera */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        
        .logo-text {
            font-size: 14px;
            font-weight: bold;
            color: #1e293b;
        }
        
        .sub-text {
            font-size: 7px;
            color: #64748b;
        }
        
        .qr-image {
            width: 50px;
            height: 50px;
        }
        
        /* Secciones */
        .section-title {
            font-size: 10px;
            font-weight: bold;
            background: #f1f5f9;
            padding: 5px 8px;
            margin: 12px 0 8px 0;
            border-left: 3px solid #4f46e5;
        }
        
        .student-box {
            background: #f8fafc;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-bottom: 10px;
        }
        
        .level-badge {
            display: inline-block;
            background: #4f46e5;
            color: white;
            font-weight: bold;
            font-size: 16px;
            padding: 3px 12px;
            border-radius: 25px;
        }
        
        /* PRIMERA FILA - 3 cuadros */
        .row {
            width: 100%;
            margin-bottom: 10px;
            overflow: hidden;
        }
        
        .row::after {
            content: "";
            clear: both;
            display: table;
        }
        
        .level-card {
            float: left;
            width: calc(33.33% - 7px);
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-right: 10px;
        }
        
        .level-card:last-child {
            margin-right: 0;
        }
        
        .level-card.current {
            border: 2px solid #4f46e5;
            box-shadow: 0 2px 8px rgba(79,70,229,0.15);
        }
        
        .level-header {
            background: #f8fafc;
            padding: 6px 8px;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .level-name {
            font-size: 14px;
            font-weight: bold;
        }
        
        .level-name.current-level {
            color: #4f46e5;
        }
        
        .level-score {
            font-size: 10px;
            margin-top: 2px;
        }
        
        .level-status {
            font-size: 7px;
            margin-top: 2px;
            font-weight: bold;
        }
        
        .level-body {
            padding: 6px 8px;
        }
        
        .criteria-item {
            margin-bottom: 5px;
        }
        
        .criteria-label {
            font-size: 6px;
            font-weight: bold;
            color: #334155;
            text-transform: capitalize;
            margin-bottom: 2px;
        }
        
        .criteria-bar {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .bar-container {
            flex: 1;
            background-color: #e2e8f0;
            border-radius: 8px;
            height: 4px;
            overflow: hidden;
        }
        
        .bar-fill {
            height: 4px;
            border-radius: 8px;
        }
        
        .criteria-value {
            font-size: 6px;
            font-weight: bold;
            min-width: 28px;
            text-align: right;
        }
        
        .empty-criteria {
            text-align: center;
            color: #94a3b8;
            font-size: 6px;
            padding: 8px;
            font-style: italic;
        }
        
        /* Observaciones */
        .feedback-box {
            background: #fef3c7;
            padding: 8px 12px;
            border-radius: 8px;
            border-left: 3px solid #f59e0b;
            font-size: 8px;
            color: #92400e;
            margin: 10px 0;
        }
        
        /* Pie de página */
        .footer-table {
            width: 100%;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #cbd5e1;
        }
        
        .disclaimer {
            text-align: center;
            margin-top: 10px;
            padding: 5px;
            background: #fef2f2;
            border-radius: 6px;
            font-size: 6px;
            color: #dc2626;
            font-weight: bold;
        }
        
        /* Text colors */
        .text-success { color: #059669; }
        .text-danger { color: #dc2626; }
        .text-warning { color: #d97706; }
        .text-muted { color: #94a3b8; }
        .bg-success { background-color: #10b981; }
        .bg-danger { background-color: #f43f5e; }
    </style>
</head>
<body>
    <!-- Marca de agua -->
    <div class="watermark">DOCUMENTO NO OFICIAL - SOLO INFORMATIVO</div>

    <!-- Cabecera -->
    <table class="header-table">
        <tr>
            <td>
                <p class="logo-text">ESCUELA MILITAR DE INGENIERÍA</p>
                <p class="sub-text">EmiSystem - Evaluación de Lenguas Extranjeras</p>
                <p class="sub-text" style="color: #dc2626;">Documento Informativo - Sin valor oficial</p>
            </td>
            <td class="qr-section" style="text-align: right;">
                @if(isset($qrCode))
                    <img src="data:image/svg+xml;base64,{{ $qrCode }}" class="qr-image" alt="QR">
                @endif
            </td>
        </tr>
    </table>

    <!-- Identificación del Estudiante -->
    <div class="section-title">📋 IDENTIFICACIÓN DEL ESTUDIANTE</div>
    <div class="student-box">
        <table width="100%">
            <tr>
                <td>
                    <strong style="font-size: 12px;">{{ $result->student->name }} {{ $result->student->lastname }} {{ $result->student->surname ?? '' }}</strong><br>
                    <span style="font-size: 7px;">Carrera: {{ $result->student->studentProfile->career->name ?? 'No especificada' }}</span><br>
                    <span style="font-size: 7px;">Idioma Evaluado: {{ $language_name }}</span>
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    <span style="font-size: 7px;">Nivel Alcanzado</span>
                    <div class="level-badge">{{ $result->final_level }}</div>
                    <span style="font-weight: bold; font-size: 10px; color: {{ $result->final_score >= 60 ? '#10b981' : '#dc2626' }};">
                        Puntaje: {{ $result->final_score }}%
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <!-- 6 CUADROS: 3 EN PRIMERA FILA + 3 EN SEGUNDA FILA -->
    <div class="section-title">📊 COMPETENCIAS POR NIVEL MCER</div>
    
    @php
        $allLevels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
        $firstRow = ['A1', 'A2', 'B1'];
        $secondRow = ['B2', 'C1', 'C2'];
        
        $levelsMap = [];
        foreach ($levelsSummary as $item) {
            $levelsMap[$item['level']] = $item;
        }
        
        // Obtener criterios del nivel actual
        $currentDetailed = is_array($result->detailed_scores) 
            ? $result->detailed_scores 
            : json_decode($result->detailed_scores, true);
        
        $criteriaMap = [
            'A1' => ['vocabulary', 'grammar', 'pronunciation', 'fluency'],
            'A2' => ['vocabulary', 'grammar', 'pronunciation', 'fluency', 'interaction'],
            'B1' => ['vocabulary', 'grammar', 'pronunciation', 'fluency', 'interaction', 'coherence'],
            'B2' => ['vocabulary', 'grammar', 'pronunciation', 'fluency', 'interaction', 'coherence', 'argumentation'],
            'C1' => ['vocabulary', 'grammar', 'pronunciation', 'fluency', 'interaction', 'coherence', 'argumentation', 'nuance'],
            'C2' => ['vocabulary', 'grammar', 'pronunciation', 'fluency', 'interaction', 'coherence', 'argumentation', 'nuance', 'precision']
        ];
        
        function getLevelCriteria($level, $currentDetailed, $levelsMap, $criteriaMap) {
            // Si es el nivel actual, usar sus criterios reales
            if (isset($currentDetailed[$level]) && count($currentDetailed[$level]) > 0) {
                return $currentDetailed[$level];
            }
            
            // Buscar en levelsMap
            if (isset($levelsMap[$level]) && isset($levelsMap[$level]['detailed_scores'])) {
                return $levelsMap[$level]['detailed_scores'];
            }
            
            // Si no hay datos, usar criterios por defecto con nota global
            $globalScore = isset($levelsMap[$level]) ? round($levelsMap[$level]['average'], 1) : 0;
            $defaultCriteria = $criteriaMap[$level] ?? ['vocabulary', 'grammar', 'pronunciation', 'fluency'];
            $criteria = [];
            foreach ($defaultCriteria as $criterion) {
                $criteria[$criterion] = $globalScore;
            }
            return $criteria;
        }
    @endphp
    
    <!-- PRIMERA FILA: A1, A2, B1 -->
    <div class="row">
        @foreach($firstRow as $level)
            @php
                $item = $levelsMap[$level] ?? null;
                $isCurrent = $level === $result->final_level;
                $score = $item ? round($item['average'], 1) : 0;
                $hasScore = $score > 0;
                $isPassed = $hasScore && $score >= 60;
                $isCompleted = $item && $item['completed'];
                $levelCriteria = getLevelCriteria($level, $currentDetailed, $levelsMap, $criteriaMap);
            @endphp
            <div class="level-card {{ $isCurrent ? 'current' : '' }}">
                <div class="level-header">
                    <div class="level-name {{ $isCurrent ? 'current-level' : '' }}">
                        MCER {{ $level }}
                        @if($isCurrent)
                            <span style="font-size: 6px; color: #4f46e5;"> (actual)</span>
                        @endif
                    </div>
                    <div class="level-score">
                        @if($hasScore)
                            <span class="{{ $isPassed ? 'text-success' : 'text-danger' }}">{{ $score }}%</span>
                        @else
                            <span class="text-muted">No evaluado</span>
                        @endif
                    </div>
                    <div class="level-status">
                        @if($isCompleted && $isPassed)
                            <span class="text-success">✅ APROBADO</span>
                        @elseif($hasScore)
                            <span class="text-danger">❌ REPROBADO</span>
                        @else
                            <span class="text-muted">⏳ PENDIENTE</span>
                        @endif
                    </div>
                </div>
                <div class="level-body">
                    @if($hasScore && count($levelCriteria) > 0)
                        @foreach($levelCriteria as $criterion => $criterionScore)
                            @if(is_numeric($criterionScore) && $criterion !== 'completed')
                                <div class="criteria-item">
                                    <div class="criteria-label">{{ ucfirst(str_replace('_', ' ', $criterion)) }}</div>
                                    <div class="criteria-bar">
                                        <div class="bar-container">
                                            <div class="bar-fill" style="width: {{ $criterionScore }}%; background-color: {{ $criterionScore >= 60 ? '#10b981' : '#f43f5e' }};"></div>
                                        </div>
                                        <div class="criteria-value">{{ $criterionScore }}%</div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @elseif($hasScore)
                        <div class="empty-criteria">Detalles no disponibles</div>
                    @else
                        <div class="empty-criteria">No evaluado</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    
    <!-- SEGUNDA FILA: B2, C1, C2 -->
    <div class="row">
        @foreach($secondRow as $level)
            @php
                $item = $levelsMap[$level] ?? null;
                $isCurrent = $level === $result->final_level;
                $score = $item ? round($item['average'], 1) : 0;
                $hasScore = $score > 0;
                $isPassed = $hasScore && $score >= 60;
                $isCompleted = $item && $item['completed'];
                $levelCriteria = getLevelCriteria($level, $currentDetailed, $levelsMap, $criteriaMap);
            @endphp
            <div class="level-card {{ $isCurrent ? 'current' : '' }}">
                <div class="level-header">
                    <div class="level-name {{ $isCurrent ? 'current-level' : '' }}">
                        MCER {{ $level }}
                        @if($isCurrent)
                            <span style="font-size: 6px; color: #4f46e5;"> (actual)</span>
                        @endif
                    </div>
                    <div class="level-score">
                        @if($hasScore)
                            <span class="{{ $isPassed ? 'text-success' : 'text-danger' }}">{{ $score }}%</span>
                        @else
                            <span class="text-muted">No evaluado</span>
                        @endif
                    </div>
                    <div class="level-status">
                        @if($isCompleted && $isPassed)
                            <span class="text-success">✅ APROBADO</span>
                        @elseif($hasScore)
                            <span class="text-danger">❌ REPROBADO</span>
                        @else
                            <span class="text-muted">⏳ PENDIENTE</span>
                        @endif
                    </div>
                </div>
                <div class="level-body">
                    @if($hasScore && count($levelCriteria) > 0)
                        @foreach($levelCriteria as $criterion => $criterionScore)
                            @if(is_numeric($criterionScore) && $criterion !== 'completed')
                                <div class="criteria-item">
                                    <div class="criteria-label">{{ ucfirst(str_replace('_', ' ', $criterion)) }}</div>
                                    <div class="criteria-bar">
                                        <div class="bar-container">
                                            <div class="bar-fill" style="width: {{ $criterionScore }}%; background-color: {{ $criterionScore >= 60 ? '#10b981' : '#f43f5e' }};"></div>
                                        </div>
                                        <div class="criteria-value">{{ $criterionScore }}%</div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @elseif($hasScore)
                        <div class="empty-criteria">Detalles no disponibles</div>
                    @else
                        <div class="empty-criteria">No evaluado</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- Observaciones -->
    <div class="section-title">💬 OBSERVACIONES</div>
    <div class="feedback-box">
        "{{ $result->teacher_feedback ?? 'El evaluador no registró observaciones adicionales.' }}"
    </div>

    <!-- Pie de página -->
    <table class="footer-table">
        <tr>
            <td style="font-size: 6px; color: #94a3b8;">
                Documento Informativo generado por EmiSystem.<br>
                Validación mediante código QR.
            </td>
            <td style="text-align: right;">
                <strong style="font-size: 9px;">{{ $result->teacher->name }} {{ $result->teacher->lastname }}</strong><br>
                <span style="font-size: 6px;">Docente Examinador Autorizado</span>
            </td>
        </tr>
    </table>

    <!-- Advertencia -->
    <div class="disclaimer">
        ⚠️ DOCUMENTO INFORMATIVO - NO CONSTITUYE CERTIFICADO OFICIAL ⚠️
    </div>
</body>
</html>