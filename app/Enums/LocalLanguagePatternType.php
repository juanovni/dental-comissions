<?php

namespace App\Enums;

enum LocalLanguagePatternType: string
{
    case Period = 'period';
    case AppointmentIntent = 'appointment_intent';
    case Confirmation = 'confirmation';
    case Cancellation = 'cancellation';
    case Reschedule = 'reschedule';
    case ProcedureAlias = 'procedure_alias';

    public function label(): string
    {
        return match ($this) {
            self::Period => 'Periodo',
            self::AppointmentIntent => 'Intención de cita',
            self::Confirmation => 'Confirmación',
            self::Cancellation => 'Cancelación',
            self::Reschedule => 'Reprogramación',
            self::ProcedureAlias => 'Alias de procedimiento',
        };
    }
}
