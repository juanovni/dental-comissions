<?php

namespace App\Enums;

enum TreatmentPlanLifecycleStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Active = 'active';
    case Superseded = 'superseded';
    case AdministrativelyClosed = 'administratively_closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::PendingApproval => 'Pendiente de aprobacion',
            self::Active => 'Activo',
            self::Superseded => 'Reemplazado',
            self::AdministrativelyClosed => 'Cerrado administrativamente',
            self::Cancelled => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::PendingApproval => 'warning',
            self::Active => 'success',
            self::Superseded => 'info',
            self::AdministrativelyClosed => 'danger',
            self::Cancelled => 'danger',
        };
    }
}
