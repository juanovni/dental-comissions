<?php

namespace App\Enums;

enum OdontogramEventType: string
{
    case EntryCreated = 'entry_created';
    case EntryCorrected = 'entry_corrected';
    case EntryResolved = 'entry_resolved';
    case DiagnosisConfirmed = 'diagnosis_confirmed';
    case TreatmentPlanned = 'treatment_planned';
    case TreatmentCompleted = 'treatment_completed';
    case OdontogramSigned = 'odontogram_signed';

    public function label(): string
    {
        return match ($this) {
            self::EntryCreated => 'Entrada creada',
            self::EntryCorrected => 'Entrada corregida',
            self::EntryResolved => 'Entrada resuelta',
            self::DiagnosisConfirmed => 'Diagnostico confirmado',
            self::TreatmentPlanned => 'Tratamiento planificado',
            self::TreatmentCompleted => 'Tratamiento completado',
            self::OdontogramSigned => 'Odontograma firmado',
        };
    }
}
