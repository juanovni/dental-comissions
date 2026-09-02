<?php

namespace App\Enums;

enum OdontogramDentitionType: string
{
    case Permanent = 'permanent';
    case Primary = 'primary';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Permanent => 'Permanente',
            self::Primary => 'Temporal',
            self::Mixed => 'Mixta',
        };
    }
}
