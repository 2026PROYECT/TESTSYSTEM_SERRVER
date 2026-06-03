<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewStudentRegistered extends Notification
{
    use Queueable;

    protected $pendingUser;

    public function __construct($pendingUser)
    {
        $this->pendingUser = $pendingUser;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Nuevo Aspirante - Registro EMI')
                    ->greeting('Hola, Administrador')
                    ->line('Un nuevo estudiante ha solicitado registrarse en el sistema.')
                    ->line('Nombre: ' . $this->pendingUser->name)
                    ->line('Email: ' . $this->pendingUser->email)
                    ->action('Revisar Aspirantes', url('/admin/pending-users'))
                    ->line('Por favor, verifique las fotos de identidad antes de aprobar.');
    }
}