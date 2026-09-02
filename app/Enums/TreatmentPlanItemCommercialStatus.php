<?php

namespace App\Enums;

enum TreatmentPlanItemCommercialStatus: string
{
    case Proposed = 'proposed';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Proposed => 'Propuesto',
            self::Accepted => 'Aceptado',
            self::Rejected => 'Rechazado',
            self::Expired => 'Vencido',
            self::Withdrawn => 'Retirado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Proposed => 'info',
            self::Accepted => 'success',
            self::Rejected => 'danger',
            self::Expired => 'danger',
            self::Withdrawn => 'gray',
        };
    }
}
