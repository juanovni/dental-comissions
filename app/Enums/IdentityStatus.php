<?php

namespace App\Enums;

enum IdentityStatus: string
{
    case Provisional = 'provisional';
    case Verified = 'verified';
    case Merged = 'merged';

    public function label(): string
    {
        return match ($this) {
            self::Provisional => 'Provisional',
            self::Verified => 'Verificado',
            self::Merged => 'Fusionado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Provisional => 'warning',
            self::Verified => 'success',
            self::Merged => 'gray',
        };
    }
}
