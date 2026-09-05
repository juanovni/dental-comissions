<?php

namespace App\Enums;

enum ClearanceRequirementType: string
{
    case medical_clearance = 'medical_clearance';
    case specialist_report = 'specialist_report';
    case lab_result = 'lab_result';
    case imaging = 'imaging';
    case pre_anesthetic = 'pre_anesthetic';
    case cardiac_clearance = 'cardiac_clearance';
    case custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::medical_clearance => 'Autorizacion medica',
            self::specialist_report => 'Informe de especialista',
            self::lab_result => 'Resultado de laboratorio',
            self::imaging => 'Imagen diagnostica',
            self::pre_anesthetic => 'Evaluacion pre-anestesica',
            self::cardiac_clearance => 'Autorizacion cardiologica',
            self::custom => 'Personalizado',
        };
    }
}
