<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CustomNotification extends Notification
{
    use Queueable;

    protected $type;
    protected $title;
    protected $message;
    protected $extraData;

    public function __construct($type, $title, $message, $extraData = [])
    {
        $this->type = $type;
        $this->title = $title;
        $this->message = $message;
        $this->extraData = $extraData;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->extraData,
            'sent_at' => now()
        ];
    }
}