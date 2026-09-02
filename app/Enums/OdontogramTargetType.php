<?php

namespace App\Enums;

enum OdontogramTargetType: string
{
    case Tooth = 'tooth';
    case Surface = 'surface';
    case MultipleTeeth = 'multiple_teeth';
    case Quadrant = 'quadrant';
    case Arch = 'arch';
    case EdentulousSpan = 'edentulous_span';
    case Implant = 'implant';
    case OralRegion = 'oral_region';

    public function label(): string
    {
        return match ($this) {
            self::Tooth => 'Pieza',
            self::Surface => 'Superficie',
            self::MultipleTeeth => 'Multiples piezas',
            self::Quadrant => 'Cuadrante',
            self::Arch => 'Arcada',
            self::EdentulousSpan => 'Espacio edentulo',
            self::Implant => 'Implante',
            self::OralRegion => 'Region oral',
        };
    }
}
