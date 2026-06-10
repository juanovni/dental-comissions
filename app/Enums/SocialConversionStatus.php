<?php

namespace App\Enums;

enum SocialConversionStatus: string
{
    case None = 'none';
    case TokenGenerated = 'token_generated';
    case WhatsappStarted = 'whatsapp_started';
    case IdentityLinked = 'identity_linked';
    case PendingPatientCreation = 'pending_patient_creation';
    case AppointmentCreated = 'appointment_created';
    case Converted = 'converted';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Sin conversion',
            self::TokenGenerated => 'Token generado',
            self::WhatsappStarted => 'WhatsApp iniciado',
            self::IdentityLinked => 'Identidad vinculada',
            self::PendingPatientCreation => 'Pendiente de crear ficha',
            self::AppointmentCreated => 'Cita creada',
            self::Converted => 'Convertido',
            self::Lost => 'Perdido',
        };
    }
}
