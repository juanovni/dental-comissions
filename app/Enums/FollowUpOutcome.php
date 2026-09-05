<?php

namespace App\Enums;

enum FollowUpOutcome: string
{
    case contacted = 'contacted';
    case no_answer = 'no_answer';
    case rescheduled = 'rescheduled';
    case completed = 'completed';
    case declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::contacted => 'Contactado',
            self::no_answer => 'Sin respuesta',
            self::rescheduled => 'Reprogramado',
            self::completed => 'Completado',
            self::declined => 'Rechazado',
        };
    }
}
