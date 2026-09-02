<?php

namespace App\Enums;

enum ReadinessMoment: string
{
    case Scheduling = 'scheduling';
    case Execution = 'execution';

    public function label(): string
    {
        return match ($this) {
            self::Scheduling => 'Al agendar',
            self::Execution => 'Al ejecutar',
        };
    }
}
