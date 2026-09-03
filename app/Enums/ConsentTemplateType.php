<?php

namespace App\Enums;

enum ConsentTemplateType: string
{
    case general = 'general';
    case procedure = 'procedure';
    case surgery = 'surgery';
    case anesthesia = 'anesthesia';
    case sedation = 'sedation';
    case implant = 'implant';
    case orthodontics = 'orthodontics';
    case endodontics = 'endodontics';
    case periodontics = 'periodontics';
    case prosthodontics = 'prosthodontics';
    case cosmetic = 'cosmetic';
    case radiology = 'radiology';
    case laboratory = 'laboratory';
    case custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::general => 'General',
            self::procedure => 'Procedimiento',
            self::surgery => 'Cirugia',
            self::anesthesia => 'Anestesia',
            self::sedation => 'Sedacion',
            self::implant => 'Implante',
            self::orthodontics => 'Ortodoncia',
            self::endodontics => 'Endodoncia',
            self::periodontics => 'Periodoncia',
            self::prosthodontics => 'Prostodoncia',
            self::cosmetic => 'Estetica',
            self::radiology => 'Radiologia',
            self::laboratory => 'Laboratorio',
            self::custom => 'Personalizado',
        };
    }
}
