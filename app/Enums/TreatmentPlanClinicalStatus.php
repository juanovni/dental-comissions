<?php

namespace App\Enums;

enum TreatmentPlanClinicalStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case PartiallyCompleted = 'partially_completed';
    case Completed = 'completed';
    case OnHold = 'on_hold';
    case UnresolvedNeeds = 'unresolved_needs';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'No iniciado',
            self::InProgress => 'En progreso',
            self::PartiallyCompleted => 'Parcialmente completado',
            self::Completed => 'Completado',
            self::OnHold => 'En espera',
            self::UnresolvedNeeds => 'Necesidades sin resolver',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NotStarted => 'gray',
            self::InProgress => 'info',
            self::PartiallyCompleted => 'warning',
            self::Completed => 'success',
            self::OnHold => 'warning',
            self::UnresolvedNeeds => 'danger',
        };
    }
}
