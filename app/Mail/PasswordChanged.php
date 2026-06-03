<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordChanged extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct($user)
    {
        // Pasamos los datos del usuario a la vista
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Seguridad: Tu contraseña ha sido cambiada',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.password_changed', // Usaremos Markdown para que se vea profesional
        );
    }
}