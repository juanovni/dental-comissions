<?php

namespace App\Enums;

enum OdontogramEntryType: string
{
    case PreexistingCondition = 'preexisting_condition';
    case ClinicalFinding = 'clinical_finding';
    case Diagnosis = 'diagnosis';
    case PlannedTreatment = 'planned_treatment';
    case CompletedTreatment = 'completed_treatment';

    public function label(): string
    {
        return match ($this) {
            self::PreexistingCondition => 'Preexistencia',
            self::ClinicalFinding => 'Hallazgo clinico',
            self::Diagnosis => 'Diagnostico',
            self::PlannedTreatment => 'Tratamiento propuesto',
            self::CompletedTreatment => 'Tratamiento realizado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PreexistingCondition => 'info',
            self::ClinicalFinding => 'warning',
            self::Diagnosis => 'danger',
            self::PlannedTreatment => 'primary',
            self::CompletedTreatment => 'success',
        };
    }
}
