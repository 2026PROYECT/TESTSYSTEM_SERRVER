<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación EMI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 antialiased">
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="max-w-sm w-full bg-white rounded-[2rem] shadow-xl border border-gray-100 overflow-hidden">
            <div class="bg-indigo-600 p-6 text-center">
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3">
                    <span class="text-white text-xl">🛡️</span>
                </div>
                <h1 class="text-white font-bold text-lg uppercase tracking-tight">Certificado Válido</h1>
            </div>

            <div class="p-6 space-y-4">
                <div class="text-center">
                    <h2 class="text-lg font-black text-slate-800">
                        {{ $result->student->name }} {{ $result->student->lastname }} {{ $result->student->surname ?? '' }}
                    </h2>
                    <p class="text-indigo-600 text-xs font-bold">{{ $result->student->studentProfile->career->name ?? 'Estudiante' }}</p>
                </div>

                <div class="bg-slate-50 p-4 rounded-xl flex justify-around">
                    <div class="text-center">
                        <p class="text-[9px] text-gray-400 font-bold uppercase">Nivel</p>
                        <p class="text-xl font-black text-slate-700">{{ $result->final_level }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-[9px] text-gray-400 font-bold uppercase">Saga Code</p>
                        <p class="text-xl font-black text-slate-700">{{ $result->student->studentProfile->saga_code ?? 'N/A' }}</p>
                    </div>
                </div>

                <div class="text-[11px] space-y-2 border-t pt-4">
                    <div class="flex justify-between">
                        <span class="text-gray-400 font-bold uppercase">Fecha:</span>
                        <span class="text-slate-700 font-black">{{ $result->created_at->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400 font-bold uppercase">Docente:</span>
                        <span class="text-slate-700 font-black italic">{{ $result->teacher->name }} {{ $result->teacher->lastname }}</span>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-gray-50 text-center border-t">
                <p class="text-[8px] text-gray-400 font-bold uppercase">EmiSystem - Registro Oficial de Evaluación</p>
            </div>
        </div>
    </div>
</body>
</html>