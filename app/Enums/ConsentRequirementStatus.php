<?php

namespace App\Enums;

enum ConsentRequirementStatus: string
{
    case active = 'active';
    case satisfied = 'satisfied';
    case waived = 'waived';
    case expired = 'expired';
    case superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::active => 'Activo',
            self::satisfied => 'Satisfecho',
            self::waived => 'Dispensado',
            self::expired => 'Vencido',
            self::superseded => 'Reemplazado',
        };
    }
}
