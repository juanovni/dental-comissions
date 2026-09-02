<?php

namespace App\Enums;

enum TreatmentPlanItemClinicalStatus: string
{
    case Unscheduled = 'unscheduled';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Unscheduled => 'Sin agendar',
            self::Scheduled => 'Agendado',
            self::InProgress => 'En progreso',
            self::Completed => 'Completado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unscheduled => 'gray',
            self::Scheduled => 'info',
            self::InProgress => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }
}
