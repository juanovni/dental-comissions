<?php

namespace App\Enums;

enum ClinicalAlertSource: string
{
    case PatientReported = 'patient_reported';
    case ProfessionalVerified = 'professional_verified';
    case SystemDerived = 'system_derived';
    case SafetyRule = 'safety_rule';

    public function label(): string
    {
        return match ($this) {
            self::PatientReported => 'Reportado por paciente',
            self::ProfessionalVerified => 'Verificado por profesional',
            self::SystemDerived => 'Derivado del sistema',
            self::SafetyRule => 'Regla de seguridad',
        };
    }
}
