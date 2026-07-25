<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case PendingConfirmation = 'pending_confirmation';
    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case Preparing = 'preparing';
    case ReadyForDoctor = 'ready_for_doctor';
    case InConsultation = 'in_consultation';
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
            self::CheckedIn => 'En espera',
            self::Preparing => 'En preparacion',
            self::ReadyForDoctor => 'Listo para doctor',
            self::InConsultation => 'En consulta',
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
            self::CheckedIn,
            self::ReadyForDoctor,
            self::Completed => 'success',
            self::Preparing => 'warning',
            self::InConsultation => 'info',
            self::Cancelled,
            self::NoShow => 'danger',
        };
    }
}
