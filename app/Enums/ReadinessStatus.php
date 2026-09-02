<?php

namespace App\Enums;

enum ReadinessStatus: string
{
    case NotReady = 'not_ready';
    case Ready = 'ready';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::NotReady => 'No listo',
            self::Ready => 'Listo',
            self::Blocked => 'Bloqueado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NotReady => 'gray',
            self::Ready => 'success',
            self::Blocked => 'danger',
        };
    }
}
