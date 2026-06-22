<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case PendingConfirmation = 'pending_confirmation';
    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case Rescheduled = 'rescheduled';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::PendingConfirmation => 'Pendiente de confirmar',
            self::Scheduled => 'Agendada',
            self::Confirmed => 'Confirmada',
            self::Rescheduled => 'Reprogramada',
            self::Cancelled => 'Cancelada',
            self::Completed => 'Completada',
            self::NoShow => 'No asistio',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingConfirmation => 'warning',
            self::Scheduled,
            self::Rescheduled => 'info',
            self::Confirmed,
            self::Completed => 'success',
            self::Cancelled,
            self::NoShow => 'danger',
        };
    }
}
