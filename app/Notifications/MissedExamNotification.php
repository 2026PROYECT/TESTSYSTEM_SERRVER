<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class MissedExamNotification extends Notification
{
    use Queueable;

    protected $assignment;
    protected $student;
    protected $sanctionDays;

    public function __construct($assignment, $student, $sanctionDays = 14)
    {
        $this->assignment = $assignment;
        $this->student = $student;
        $this->sanctionDays = $sanctionDays;
    }

    public function via($notifiable)
    {
        return ['mail', 'database']; // ✅ Email + Base de datos
    }

    public function toMail($notifiable)
    {
        $examType = $this->assignment->test_type === 'OralTest' ? 'Oral' : 'Computarizado';
        $examDate = \Carbon\Carbon::parse($this->assignment->start_at)->format('d/m/Y H:i');
        $reactivationDate = \Carbon\Carbon::parse($this->assignment->updated_at)->addDays($this->sanctionDays)->format('d/m/Y');
        
        return (new MailMessage)
            ->subject('⚠️ Inasistencia a Examen - EmiSystem')
            ->greeting('Hola ' . $this->student->name . ' ' . $this->student->lastname)
            ->line('Se ha registrado una inasistencia en tu examen ' . $examType . '.')
            ->line('📅 Fecha del examen: ' . $examDate)
            ->line('⏰ Duración de la sanción: ' . $this->sanctionDays . ' días')
            ->line('🔓 Podrás reagendar a partir del: ' . $reactivationDate)
            ->action('Ver Dashboard', url('/'))
            ->line('Recuerda que es importante asistir a tus exámenes programados.')
            ->salutation('Saludos, equipo de EmiSystem');
    }

    public function toDatabase($notifiable)
    {
        $examType = $this->assignment->test_type === 'OralTest' ? 'Oral' : 'Computarizado';
        $reactivationDate = \Carbon\Carbon::parse($this->assignment->updated_at)->addDays($this->sanctionDays);
        
        return [
            'assignment_id' => $this->assignment->id,
            'exam_type' => $this->assignment->test_type,
            'exam_date' => $this->assignment->start_at,
            'sanction_days' => $this->sanctionDays,
            'reactivation_date' => $reactivationDate,
            'message' => '⚠️ Has registrado una inasistencia en tu examen ' . $examType . '. Sanción de ' . $this->sanctionDays . ' días.',
        ];
    }
}