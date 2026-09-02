<?php

namespace App\Enums;

enum DependencyType: string
{
    case MustCompleteBefore = 'must_complete_before';
    case MustStartBefore = 'must_start_before';
    case RequiresResult = 'requires_result';
    case MinimumHealingInterval = 'minimum_healing_interval';

    public function label(): string
    {
        return match ($this) {
            self::MustCompleteBefore => 'Debe completarse antes',
            self::MustStartBefore => 'Debe iniciarse antes',
            self::RequiresResult => 'Requiere resultado',
            self::MinimumHealingInterval => 'Intervalo minimo de cicatrizacion',
        };
    }
}
