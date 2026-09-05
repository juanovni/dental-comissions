<?php

namespace App\Enums;

enum ClearanceConditionStatus: string
{
    case pending = 'pending';
    case verified = 'verified';
    case not_met = 'not_met';
    case waived = 'waived';
    case expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::pending => 'Pendiente',
            self::verified => 'Verificada',
            self::not_met => 'No cumplida',
            self::waived => 'Dispensada',
            self::expired => 'Vencida',
        };
    }
}
