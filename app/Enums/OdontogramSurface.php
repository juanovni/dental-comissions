<?php

namespace App\Enums;

enum OdontogramSurface: string
{
    case Mesial = 'mesial';
    case Distal = 'distal';
    case Vestibular = 'vestibular';
    case Lingual = 'lingual';
    case Palatal = 'palatal';
    case Occlusal = 'occlusal';
    case Incisal = 'incisal';
    case Cervical = 'cervical';
    case Root = 'root';
    case WholeTooth = 'whole_tooth';

    public function label(): string
    {
        return match ($this) {
            self::Mesial => 'Mesial',
            self::Distal => 'Distal',
            self::Vestibular => 'Vestibular',
            self::Lingual => 'Lingual',
            self::Palatal => 'Palatino',
            self::Occlusal => 'Oclusal',
            self::Incisal => 'Incisal',
            self::Cervical => 'Cervical',
            self::Root => 'Radicular',
            self::WholeTooth => 'Pieza completa',
        };
    }
}
