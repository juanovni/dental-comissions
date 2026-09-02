<?php

namespace App\Enums;

enum TreatmentPlanCommercialStatus: string
{
    case NotSent = 'not_sent';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case PartiallyAccepted = 'partially_accepted';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::NotSent => 'No enviado',
            self::Sent => 'Enviado',
            self::Viewed => 'Visualizado',
            self::PartiallyAccepted => 'Parcialmente aceptado',
            self::Accepted => 'Aceptado',
            self::Rejected => 'Rechazado',
            self::Expired => 'Vencido',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NotSent => 'gray',
            self::Sent => 'info',
            self::Viewed => 'info',
            self::PartiallyAccepted => 'warning',
            self::Accepted => 'success',
            self::Rejected => 'danger',
            self::Expired => 'danger',
        };
    }
}
