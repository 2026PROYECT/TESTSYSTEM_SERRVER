<?php

namespace App\Enums; // 👈 Al estar en la carpeta App/Enums, este namespace es obligatorio

enum QuizType: string
{
    case NORMAL = 'normal';
    case DEDICATED = 'dedicated';
    case MODULAR = 'modular';
    case DETAILED = 'detailed';
    
    public function label(): string
    {
        return match($this) {
            self::NORMAL => 'Normal',
            self::DEDICATED => 'Dedicado',
            self::MODULAR => 'Modular',
            self::DETAILED => 'Detallado',
        };
    }
}