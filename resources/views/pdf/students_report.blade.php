<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Estudiantes - EmiSystem</title>
    <style>
        @page { 
            margin: 2cm; 
            size: letter portrait; 
        }
        
        body { 
            font-family: 'Helvetica', Arial, sans-serif; 
            color: #1e293b; 
            font-size: 9px; 
            margin: 0;
        }

        .header { 
            text-align: center; 
            margin-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
        }

        h1 { 
            text-align: center; 
            color: #1e293b; 
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .subtitle {
            text-align: center;
            color: #64748b;
            font-size: 10px;
            margin-bottom: 15px;
        }
        
        .info {
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
            margin-bottom: 20px;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px; 
        }

        th { 
            background: #f1f5f9; 
            border: 1px solid #cbd5e1; 
            padding: 10px 6px; 
            text-align: center; 
            font-size: 8px;
            text-transform: uppercase;
            font-weight: bold;
        }

        td { 
            border: 1px solid #cbd5e1; 
            padding: 8px 6px; 
            vertical-align: middle;
        }

        .text-left { 
            text-align: left; 
        }
        
        .text-center { 
            text-align: center; 
        }

        .footer { 
            margin-top: 30px; 
            text-align: center; 
            font-size: 7px; 
            color: #94a3b8; 
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Reporte de Estudiantes</h1>
    <div class="subtitle">Sistema de Gestión Académica - EmiSystem</div>
    <div class="info">
        Generado por: {{ $admin ?? 'Sistema' }} | Fecha: {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

<table>
    <thead>
        <tr>
            <th class="text-left" width="30%">Nombre Completo</th>
            <th class="text-left" width="25%">Email</th>
            <th class="text-left" width="20%">Carrera</th>
            <th class="text-center" width="12%">Código SAGA</th>
            <th class="text-center" width="13%">CI</th>
        </tr>
    </thead>
    <tbody>
        @forelse($students as $student)
        <tr>
            <td class="text-left">{{ $student->full_name }}</td>
            <td class="text-left">{{ $student->email }}</td>
            <td class="text-left">{{ $student->student->career->name ?? 'N/A' }}</td>
            <td class="text-center">{{ $student->student->saga_code ?? 'N/A' }}</td>
            <td class="text-center">{{ $student->student->id_number ?? 'N/A' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="text-center">No hay estudiantes registrados</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    <p>Documento generado automáticamente por EmiSystem - {{ now()->format('Y-m-d H:i:s') }}</p>
    <p>Total de estudiantes: {{ $students->count() }}</p>
</div>

</body>
</html>