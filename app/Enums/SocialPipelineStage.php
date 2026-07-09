<?php

namespace App\Enums;

enum SocialPipelineStage: string
{
    case New = 'new';
    case Qualified = 'qualified';
    case Appointment = 'appointment';
    case Proposal = 'proposal';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nuevo',
            self::Qualified => 'Calificado',
            self::Appointment => 'Cita',
            self::Proposal => 'Presupuesto',
            self::Won => 'Ganado',
            self::Lost => 'Perdido',
        };
    }

    public static function fromConversionStatus(SocialConversionStatus $conversionStatus): self
    {
        return match ($conversionStatus) {
            SocialConversionStatus::None => self::New,
            SocialConversionStatus::TokenGenerated,
            SocialConversionStatus::WhatsappStarted => self::Qualified,
            SocialConversionStatus::IdentityLinked,
            SocialConversionStatus::PendingPatientCreation => self::Appointment,
            SocialConversionStatus::AppointmentCreated => self::Appointment,
            SocialConversionStatus::Converted => self::Won,
            SocialConversionStatus::Lost => self::Lost,
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::New => 0,
            self::Qualified => 1,
            self::Appointment => 2,
            self::Proposal => 3,
            self::Won => 4,
            self::Lost => 5,
        };
    }
}
