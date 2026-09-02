<?php

namespace App\Enums;

enum ClinicalAlertLevel: string
{
    case Advisory = 'advisory';
    case ConditionalHold = 'conditional_hold';
    case HardStop = 'hard_stop';

    public function label(): string
    {
        return match ($this) {
            self::Advisory => 'Informativa',
            self::ConditionalHold => 'Bloqueo condicional',
            self::HardStop => 'Bloqueo duro',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Advisory => 'info',
            self::ConditionalHold => 'warning',
            self::HardStop => 'danger',
        };
    }

    public function blocksScheduling(): bool
    {
        return $this === self::ConditionalHold || $this === self::HardStop;
    }

    public function blocksExecution(): bool
    {
        return $this === self::ConditionalHold || $this === self::HardStop;
    }

    public function allowsOverride(): bool
    {
        return $this === self::ConditionalHold;
    }
}
