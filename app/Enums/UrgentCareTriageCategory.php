<?php

namespace App\Enums;

enum UrgentCareTriageCategory: string
{
    case immediate = 'immediate';
    case urgent = 'urgent';
    case semi_urgent = 'semi_urgent';
    case non_urgent = 'non_urgent';

    public function label(): string
    {
        return match ($this) {
            self::immediate => 'Inmediato',
            self::urgent => 'Urgente',
            self::semi_urgent => 'Semi-urgente',
            self::non_urgent => 'No urgente',
        };
    }
}
