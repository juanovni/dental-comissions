<?php

namespace App\Enums;

enum PhaseStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::InProgress => 'En progreso',
            self::Completed => 'Completada',
            self::Skipped => 'Omitida',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::InProgress => 'info',
            self::Completed => 'success',
            self::Skipped => 'warning',
        };
    }
}
