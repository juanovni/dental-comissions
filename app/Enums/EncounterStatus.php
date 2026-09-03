<?php

namespace App\Enums;

enum EncounterStatus: string
{
    case Draft = 'draft';
    case Signed = 'signed';
    case Amended = 'amended';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Signed => 'Firmado',
            self::Amended => 'Enmendado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Signed => 'success',
            self::Amended => 'warning',
            self::Cancelled => 'danger',
        };
    }
}
