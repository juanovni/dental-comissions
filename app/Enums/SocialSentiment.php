<?php

namespace App\Enums;

enum SocialSentiment: string
{
    case Positive = 'positive';
    case Neutral = 'neutral';
    case Negative = 'negative';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Positive => 'Positivo',
            self::Neutral => 'Neutral',
            self::Negative => 'Negativo',
            self::Mixed => 'Mixto',
        };
    }
}
