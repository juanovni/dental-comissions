<?php

namespace App\Enums;

enum ClinicalCaseType: string
{
    case Orthodontics = 'orthodontics';
    case Endodontics = 'endodontics';
    case Periodontics = 'periodontics';
    case General = 'general';
    case Implantology = 'implantology';
    case Prosthodontics = 'prosthodontics';
    case Pediatric = 'pediatric';
    case OralSurgery = 'oral_surgery';
    case Cosmetic = 'cosmetic';

    public function label(): string
    {
        return match ($this) {
            self::Orthodontics => 'Ortodoncia',
            self::Endodontics => 'Endodoncia',
            self::Periodontics => 'Periodoncia',
            self::General => 'General',
            self::Implantology => 'Implantologia',
            self::Prosthodontics => 'Prostodoncia',
            self::Pediatric => 'Odontopediatria',
            self::OralSurgery => 'Cirugia oral',
            self::Cosmetic => 'Estetica dental',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Orthodontics => 'info',
            self::Endodontics => 'warning',
            self::Periodontics => 'success',
            self::General => 'gray',
            self::Implantology => 'danger',
            self::Prosthodontics => 'info',
            self::Pediatric => 'success',
            self::OralSurgery => 'danger',
            self::Cosmetic => 'info',
        };
    }
}
