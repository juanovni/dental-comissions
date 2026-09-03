<?php

namespace App\Enums;

enum ConsentDecisionStatus: string
{
    case pending = 'pending';
    case signed = 'signed';
    case rejected = 'rejected';
    case revoked = 'revoked';
    case expired = 'expired';
    case waived = 'waived';
    case superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::pending => 'Pendiente',
            self::signed => 'Firmado',
            self::rejected => 'Rechazado',
            self::revoked => 'Revocado',
            self::expired => 'Vencido',
            self::waived => 'Dispensado',
            self::superseded => 'Reemplazado',
        };
    }
}
