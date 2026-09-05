<?php

namespace App\Enums;

enum MedicalClearanceDecision: string
{
    case cleared = 'cleared';
    case cleared_with_conditions = 'cleared_with_conditions';
    case not_cleared = 'not_cleared';
    case insufficient_information = 'insufficient_information';
    case expired = 'expired';
    case superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::cleared => 'Autorizado',
            self::cleared_with_conditions => 'Autorizado con condiciones',
            self::not_cleared => 'No autorizado',
            self::insufficient_information => 'Informacion insuficiente',
            self::expired => 'Vencido',
            self::superseded => 'Reemplazado',
        };
    }
}
