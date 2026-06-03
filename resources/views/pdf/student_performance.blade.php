<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0; }
        body { font-family: 'Helvetica', sans-serif; color: #1e293b; line-height: 1.5; margin: 0; background-color: #ffffff; }
        .certificate-container { padding: 50px; border: 20px solid #f8fafc; min-height: 90vh; position: relative; }
        .header { text-align: center; margin-bottom: 40px; border-bottom: 3px solid #4f46e5; padding-bottom: 20px; }
        .header h1 { color: #4f46e5; margin: 0; text-transform: uppercase; font-size: 26px; letter-spacing: 2px; }
        
        .student-box { background: #f1f5f9; padding: 25px; border-radius: 20px; margin-bottom: 40px; }
        .student-name { font-size: 22px; font-weight: bold; color: #0f172a; text-transform: uppercase; }
        .career-name { color: #4f46e5; font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }

        .results-grid { width: 100%; margin-top: 20px; }
        .score-card { 
            width: 45%; 
            display: inline-block; 
            vertical-align: top; 
            background: #fff; 
            border: 1px solid #e2e8f0; 
            padding: 20px; 
            border-radius: 15px;
            text-align: center;
        }
        .score-val { font-size: 48px; font-weight: 900; color: #4f46e5; display: block; margin: 10px 0; }
        .score-label { font-size: 12px; font-weight: bold; color: #64748b; text-transform: uppercase; }

        .footer { position: absolute; bottom: 50px; left: 0; right: 0; text-align: center; font-size: 11px; color: #94a3b8; }
        .signature { margin-top: 80px; border-top: 1px solid #000; width: 200px; margin-left: auto; margin-right: auto; padding-top: 10px; font-weight: bold; color: #000; }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="header">
            <h1>EMI SYSTEM</h1>
            <p style="font-weight: bold; color: #64748b;">REPORT OF ACADEMIC PERFORMANCE</p>
        </div>

        <div class="student-box">
            <div class="career-name">{{ $user->student->career->name ?? 'GENERAL PROGRAM' }}</div>
            <div class="student-name">{{ $user->lastname }} {{ $user->surname }} {{ $user->name }}</div>
            <div style="font-size: 12px; margin-top: 5px; color: #64748b;">Student ID: #{{ $user->id }} | Saga Code: {{ $user->student->saga_code ?? 'N/A' }}</div>
        </div>

        <div class="results-grid">
            <div class="score-card" style="margin-right: 4%;">
                <span class="score-label">🗣️ Oral Test Score</span>
                <span class="score-val">{{ $scores['oral'] }}</span>
                <small style="color: {{ $scores['oral'] >= 51 ? '#059669' : '#dc2626' }}; font-weight: bold;">
                    {{ $scores['oral'] >= 51 ? 'PASSED' : 'FAILED' }}
                </small>
            </div>

            <div class="score-card">
                <span class="score-label">💻 Computer Test Score</span>
                <span class="score-val">{{ $scores['comp'] }}</span>
                <small style="color: {{ $scores['comp'] >= 51 ? '#059