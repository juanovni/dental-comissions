<?php

namespace App\Enums;

enum UrgentCareArrivalMode: string
{
    case walk_in = 'walk_in';
    case ambulance = 'ambulance';
    case transfer = 'transfer';
    case self_referral = 'self_referral';

    public function label(): string
    {
        return match ($this) {
            self::walk_in => 'Sin cita',
            self::ambulance => 'Ambulancia',
            self::transfer => 'Traslado',
            self::self_referral => 'Auto-referido',
        };
    }
}
