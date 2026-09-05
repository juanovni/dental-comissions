<?php

namespace App\Enums;

enum UrgentCareDisposition: string
{
    case discharged = 'discharged';
    case admitted = 'admitted';
    case transferred = 'transferred';
    case deferred = 'deferred';

    public function label(): string
    {
        return match ($this) {
            self::discharged => 'Dado de alta',
            self::admitted => 'Internado',
            self::transferred => 'Trasladado',
            self::deferred => 'Diferido',
        };
    }
}
