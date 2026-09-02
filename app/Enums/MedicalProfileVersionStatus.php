<?php

namespace App\Enums;

enum MedicalProfileVersionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Reviewed = 'reviewed';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Submitted => 'Enviado',
            self::Reviewed => 'Revisado',
            self::Superseded => 'Reemplazado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted => 'info',
            self::Reviewed => 'success',
            self::Superseded => 'warning',
        };
    }
}
