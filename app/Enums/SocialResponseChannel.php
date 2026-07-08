<?php

namespace App\Enums;

enum SocialResponseChannel: string
{
    case Public = 'public';
    case Private = 'private';
    case Both = 'both';
    case NoResponse = 'no_response';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Publico',
            self::Private => 'Privado',
            self::Both => 'Publico y privado',
            self::NoResponse => 'Sin respuesta',
        };
    }
}
