<?php

namespace App\Enums;

enum SocialReputationRisk: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Bajo',
            self::Medium => 'Medio',
            self::High => 'Alto',
            self::Critical => 'Critico',
        };
    }
}
