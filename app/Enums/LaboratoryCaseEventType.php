<?php

namespace App\Enums;

enum LaboratoryCaseEventType: string
{
    case ordered = 'ordered';
    case sent = 'sent';
    case received = 'received';
    case quality_review = 'quality_review';
    case adjustment = 'adjustment';
    case remake = 'remake';
    case cancelled = 'cancelled';
    case communicated = 'communicated';

    public function label(): string
    {
        return match ($this) {
            self::ordered => 'Ordenado',
            self::sent => 'Enviado',
            self::received => 'Recibido',
            self::quality_review => 'Control de calidad',
            self::adjustment => 'Ajuste',
            self::remake => 'Refabricacion',
            self::cancelled => 'Cancelado',
            self::communicated => 'Comunicado',
        };
    }
}
