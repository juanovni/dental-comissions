<?php

namespace App\Enums;

enum AppointmentSource: string
{
    case WhatsappAi = 'whatsapp_ai';
    case WhatsappHuman = 'whatsapp_human';
    case SmartLink = 'smart_link';
    case AdminManual = 'admin_manual';
    case ExternalProvider = 'external_provider';

    public function label(): string
    {
        return match ($this) {
            self::WhatsappAi => 'WhatsApp IA',
            self::WhatsappHuman => 'WhatsApp humano',
            self::SmartLink => 'Smart Link',
            self::AdminManual => 'Manual admin',
            self::ExternalProvider => 'Proveedor externo',
        };
    }
}
