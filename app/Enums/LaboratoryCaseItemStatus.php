<?php

namespace App\Enums;

enum LaboratoryCaseItemStatus: string
{
    case pending = 'pending';
    case in_production = 'in_production';
    case received = 'received';
    case approved = 'approved';
    case adjustment_required = 'adjustment_required';

    public function label(): string
    {
        return match ($this) {
            self::pending => 'Pendiente',
            self::in_production => 'En produccion',
            self::received => 'Recibido',
            self::approved => 'Aprobado',
            self::adjustment_required => 'Requiere ajuste',
        };
    }
}
