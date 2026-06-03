<?php

namespace App\Mail;

use App\Models\PendingUser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyPendingEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $pending;

    public function __construct(PendingUser $pending)
    {
        $this->pending = $pending;
    }

    public function build()
{
    // Definimos la URL de tu aplicación Vue
    // En producción, esto debería venir de una variable en el .env como VITE_APP_URL
    // En lugar de escribir http://localhost:5173 a mano...
$frontendUrl = config('app.frontend_url') . '/verify-email/' . $this->pending->verification_token;

    return $this->view('emails.verify-pending')
                ->subject('Verificación de Correo - Registro EMI')
                ->with([
                    'name' => $this->pending->name,
                    'url'  => $frontendUrl, // El usuario ahora irá a Vue
                ]);
}
}