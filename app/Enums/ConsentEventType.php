<?php

namespace App\Enums;

enum ConsentEventType: string
{
    case created = 'created';
    case presented = 'presented';
    case signed = 'signed';
    case rejected = 'rejected';
    case revoked = 'revoked';
    case expired = 'expired';
    case waived = 'waived';
    case superseded = 'superseded';
    case re_signed = 're_signed';
    case substituted = 'substituted';

    public function label(): string
    {
        return match ($this) {
            self::created => 'Creado',
            self::presented => 'Presentado',
            self::signed => 'Firmado',
            self::rejected => 'Rechazado',
            self::revoked => 'Revocado',
            self::expired => 'Vencido',
            self::waived => 'Dispensado',
            self::superseded => 'Reemplazado',
            self::re_signed => 'Re-firmado',
            self::substituted => 'Sustituido',
        };
    }
}
