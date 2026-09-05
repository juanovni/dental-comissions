<?php

namespace App\Enums;

enum FollowUpChannel: string
{
    case whatsapp = 'whatsapp';
    case phone = 'phone';
    case email = 'email';
    case sms = 'sms';
    case in_person = 'in_person';

    public function label(): string
    {
        return match ($this) {
            self::whatsapp => 'WhatsApp',
            self::phone => 'Telefono',
            self::email => 'Email',
            self::sms => 'SMS',
            self::in_person => 'En persona',
        };
    }
}
