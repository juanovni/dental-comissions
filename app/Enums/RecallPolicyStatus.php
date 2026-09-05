<?php

namespace App\Enums;

enum RecallPolicyStatus: string
{
    case draft = 'draft';
    case active = 'active';
    case archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::draft => 'Borrador',
            self::active => 'Activa',
            self::archived => 'Archivada',
        };
    }
}
