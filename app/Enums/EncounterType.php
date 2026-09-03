<?php

namespace App\Enums;

enum EncounterType: string
{
    case Consultation = 'consultation';
    case Treatment = 'treatment';
    case FollowUp = 'follow_up';
    case Emergency = 'emergency';
    case WalkIn = 'walk_in';
    case Recall = 'recall';

    public function label(): string
    {
        return match ($this) {
            self::Consultation => 'Consulta',
            self::Treatment => 'Tratamiento',
            self::FollowUp => 'Seguimiento',
            self::Emergency => 'Urgencia',
            self::WalkIn => 'Sin cita',
            self::Recall => 'Control programado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Consultation => 'info',
            self::Treatment => 'success',
            self::FollowUp => 'warning',
            self::Emergency => 'danger',
            self::WalkIn => 'warning',
            self::Recall => 'info',
        };
    }
}
