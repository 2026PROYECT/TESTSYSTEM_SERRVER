<!DOCTYPE html>
<html>
<head>
    <style>
        .button {
            background-color: #1e293b;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
        }
    </style>
</head>
<body style="font-family: sans-serif; text-align: center; padding: 40px;">
    <div style="font-family: sans-serif; max-width: 600px; margin: auto; border: 1px solid #e2e8f0; padding: 30px; border-radius: 16px;">
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="{{ asset('logo.png') }}" alt="EMI" style="height: 50px;">
    </div>
    <h2 style="color: #1e293b; text-align: center;">Hola, {{ $name }}</h2>
    <p style="color: #64748b; line-height: 1.6; text-align: center;">
        Gracias por iniciar tu proceso de registro en la EMI. Para confirmar que este correo te pertenece y que un administrador pueda revisar tus documentos, pulsa el botón de abajo:
    </p>
    <div style="text-align: center; margin-top: 30px;">
        <a href="{{ $url }}" style="background-color: #4f46e5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            VERIFICAR MI CORREO
        </a>
    </div>
    <p style="color: #94a3b8; font-size: 12px; margin-top: 40px; text-align: center;">
        Si no solicitaste este registro, puedes ignorar este correo de forma segura.
    </p>
</div>
</body>
</html>