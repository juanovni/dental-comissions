<?php

namespace App\Enums;

enum PatientRiskLevel: string
{
    case low = 'low';
    case moderate = 'moderate';
    case high = 'high';
    case very_high = 'very_high';

    public function label(): string
    {
        return match ($this) {
            self::low => 'Bajo',
            self::moderate => 'Moderado',
            self::high => 'Alto',
            self::very_high => 'Muy alto',
        };
    }
}
