<x-mail::message>
# Hola, {{ $user->name }}

Te informamos que la contraseña de tu cuenta en **{{ config('app.name') }}** ha sido actualizada exitosamente.

Si **fuiste tú** quien realizó este cambio, puedes ignorar este mensaje.

Si **NO** realizaste este cambio, por favor contacta con nuestro equipo de soporte de inmediato para proteger tu cuenta.

<x-mail::button :url="config('app.url') . '/profile'">
Ir a mi perfil
</x-mail::button>

Gracias,<br>
El equipo de {{ config('app.name') }}
</x-mail::message>