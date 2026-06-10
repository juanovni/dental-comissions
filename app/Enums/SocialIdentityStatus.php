<?php

namespace App\Enums;

enum SocialIdentityStatus: string
{
    case NewLead = 'new_lead';
    case PendingPatientCreation = 'pending_patient_creation';
    case LinkedPatient = 'linked_patient';
    case Converted = 'converted';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::NewLead => 'Nuevo lead',
            self::PendingPatientCreation => 'Pendiente de crear ficha',
            self::LinkedPatient => 'Paciente vinculado',
            self::Converted => 'Convertido',
            self::Lost => 'Perdido',
        };
    }
}
