<?php

namespace App\Enums;

enum VoiceCallStatus: string
{
    case Started = 'started';
    case InProgress = 'in_progress';
    case AppointmentScheduled = 'appointment_scheduled';
    case HandoffRequired = 'handoff_required';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Started => 'Iniciada',
            self::InProgress => 'En progreso',
            self::AppointmentScheduled => 'Cita agendada',
            self::HandoffRequired => 'Requiere transferencia',
            self::Completed => 'Finalizada',
            self::Failed => 'Fallida',
            self::Cancelled => 'Cancelada',
        };
    }
}
