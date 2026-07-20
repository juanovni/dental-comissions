<?php

namespace App\Enums;

enum VoiceChannelType: string
{
    case WhatsappCalling = 'whatsapp_calling';
    case Twilio = 'twilio';
    case Telnyx = 'telnyx';
    case Sip = 'sip';
    case WebTest = 'web_test';

    public function label(): string
    {
        return match ($this) {
            self::WhatsappCalling => 'WhatsApp Calling',
            self::Twilio => 'Twilio Voice',
            self::Telnyx => 'Telnyx Voice',
            self::Sip => 'SIP',
            self::WebTest => 'Web test',
        };
    }
}
