<?php

namespace App\Enums;

enum ActivityStatus: string
{
    case PendingConfirmation = 'pending_confirmation';
    case NeedsReview = 'needs_review';
    case Approved = 'approved';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingConfirmation => 'Pendiente de confirmación',
            self::NeedsReview => 'Requiere revisión',
            self::Approved => 'Aprobado',
            self::Paid => 'Pagado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingConfirmation => 'warning',
            self::NeedsReview => 'danger',
            self::Approved => 'success',
            self::Paid => 'info',
            self::Cancelled => 'gray',
        };
    }
}
