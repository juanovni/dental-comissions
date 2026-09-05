<?php

namespace App\Enums;

enum FollowUpStatus: string
{
    case pending = 'pending';
    case performed = 'performed';
    case failed = 'failed';
    case cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::pending => 'Pendiente',
            self::performed => 'Realizado',
            self::failed => 'Fallido',
            self::cancelled => 'Cancelado',
        };
    }
}
