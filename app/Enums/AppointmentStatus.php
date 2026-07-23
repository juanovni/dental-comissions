<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case PendingConfirmation = 'pending_confirmation';
    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case Rescheduled = 'rescheduled';
    case OnTheWay = 'on_the_way';
    case Waiting = 'waiting';
    case InConsultation = 'in_consultation';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::PendingConfirmation => 'Pre-reservada',
            self::Scheduled => 'Agendada',
            self::Confirmed => 'Confirmada',
            self::Rescheduled => 'Reprogramada',
            self::OnTheWay => 'En camino',
            self::Waiting => 'En espera',
            self::InConsultation => 'En consulta',
            self::Completed => 'Finalizada',
            self::Cancelled => 'Cancelada',
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
            self::OnTheWay => 'primary',
            self::Waiting => 'warning',
            self::InConsultation => 'info',
            self::Completed => 'success',
            self::Cancelled,
            self::NoShow => 'danger',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::PendingConfirmation,
            self::Scheduled,
            self::Confirmed,
            self::Rescheduled,
            self::OnTheWay,
            self::Waiting,
            self::InConsultation,
        ], true);
    }

    public function isOperational(): bool
    {
        return in_array($this, [
            self::OnTheWay,
            self::Waiting,
            self::InConsultation,
        ], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Completed,
            self::Cancelled,
            self::NoShow,
        ], true);
    }
}
