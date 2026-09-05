<?php

namespace App\Enums;

enum ExternalConsultationStatus: string
{
    case draft = 'draft';
    case requested = 'requested';
    case sent = 'sent';
    case received = 'received';
    case under_review = 'under_review';
    case closed = 'closed';
    case cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::draft => 'Borrador',
            self::requested => 'Solicitada',
            self::sent => 'Enviada',
            self::received => 'Recibida',
            self::under_review => 'En revision',
            self::closed => 'Cerrada',
            self::cancelled => 'Cancelada',
        };
    }
}
