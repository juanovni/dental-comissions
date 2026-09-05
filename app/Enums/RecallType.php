<?php

namespace App\Enums;

enum RecallType: string
{
    case general = 'general';
    case periodontal = 'periodontal';
    case implant = 'implant';
    case retention = 'retention';
    case custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::general => 'Preventivo general',
            self::periodontal => 'Periodontal',
            self::implant => 'Implantes',
            self::retention => 'Retencion',
            self::custom => 'Personalizado',
        };
    }
}
