<?php

namespace App\Enums;

enum LaboratoryCaseStatus: string
{
    case draft = 'draft';
    case ordered = 'ordered';
    case sent = 'sent';
    case accepted_by_lab = 'accepted_by_lab';
    case in_production = 'in_production';
    case received = 'received';
    case quality_review = 'quality_review';
    case ready_for_patient = 'ready_for_patient';
    case adjustment_required = 'adjustment_required';
    case cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::draft => 'Borrador',
            self::ordered => 'Ordenado',
            self::sent => 'Enviado',
            self::accepted_by_lab => 'Aceptado por laboratorio',
            self::in_production => 'En produccion',
            self::received => 'Recibido',
            self::quality_review => 'Control de calidad',
            self::ready_for_patient => 'Listo para paciente',
            self::adjustment_required => 'Requiere ajuste',
            self::cancelled => 'Cancelado',
        };
    }
}
