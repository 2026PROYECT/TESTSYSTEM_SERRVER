<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class ForgotPasswordController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        // 1. Validar el email
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'El correo electrónico no está registrado en nuestro sistema.'
            ], 422);
        }

        // 2. Intentar enviar el enlace a través del Broker de Laravel
        // Este método se encarga de crear el token en la tabla y enviar el correo
        $status = Password::broker()->sendResetLink(
            $request->only('email')
        );

        // 3. Responder según el resultado
        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Te hemos enviado por correo el enlace para restablecer tu contraseña.'], 200)
            : response()->json(['message' => 'No se pudo enviar el correo. Por favor, intenta más tarde.'], 422);
    }
}