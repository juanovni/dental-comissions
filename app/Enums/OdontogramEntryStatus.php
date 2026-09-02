<?php

namespace App\Enums;

enum OdontogramEntryStatus: string
{
    case Active = 'active';
    case Resolved = 'resolved';
    case Corrected = 'corrected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::Resolved => 'Resuelto',
            self::Corrected => 'Corregido',
            self::Cancelled => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'info',
            self::Resolved => 'success',
            self::Corrected => 'warning',
            self::Cancelled => 'danger',
        };
    }
}
