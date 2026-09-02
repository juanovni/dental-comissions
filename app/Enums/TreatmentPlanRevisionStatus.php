<?php

namespace App\Enums;

enum TreatmentPlanRevisionStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Viewed = 'viewed';
    case Accepted = 'accepted';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Issued => 'Emitida',
            self::Viewed => 'Visualizada',
            self::Accepted => 'Aceptada',
            self::Superseded => 'Reemplazada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Issued => 'info',
            self::Viewed => 'info',
            self::Accepted => 'success',
            self::Superseded => 'warning',
        };
    }
}
