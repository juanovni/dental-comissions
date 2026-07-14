<?php

namespace App\Enums;

enum SocialPlatform: string
{
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case Whatsapp = 'whatsapp';

    public function label(): string
    {
        return match ($this) {
            self::Facebook => 'Facebook',
            self::Instagram => 'Instagram',
            self::Whatsapp => 'WhatsApp',
        };
    }
}
