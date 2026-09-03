<?php

namespace App\Enums;

enum ConsentTemplateStatus: string
{
    case draft = 'draft';
    case active = 'active';
    case archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::draft => 'Borrador',
            self::active => 'Activo',
            self::archived => 'Archivado',
        };
    }
}
