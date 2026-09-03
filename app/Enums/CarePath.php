<?php

namespace App\Enums;

enum CarePath: string
{
    case Planned = 'planned';
    case Emergency = 'emergency';
    case WalkIn = 'walk_in';
    case Recall = 'recall';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planificado',
            self::Emergency => 'Urgencia',
            self::WalkIn => 'Sin cita',
            self::Recall => 'Control programado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planned => 'info',
            self::Emergency => 'danger',
            self::WalkIn => 'warning',
            self::Recall => 'success',
        };
    }
}
