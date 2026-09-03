<?php

namespace App\Enums;

enum ConsentScope: string
{
    case scheduling = 'scheduling';
    case execution = 'execution';
    case both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::scheduling => 'Al agendar',
            self::execution => 'Al ejecutar',
            self::both => 'Agendamiento y ejecucion',
        };
    }
}
