<?php

namespace App\Enums;

enum VoiceHandoffReason: string
{
    case Emergency = 'emergency';
    case Pain = 'pain';
    case Complaint = 'complaint';
    case ClinicalQuestion = 'clinical_question';
    case HumanRequested = 'human_requested';
    case ToolFailure = 'tool_failure';
    case Timeout = 'timeout';

    public function label(): string
    {
        return match ($this) {
            self::Emergency => 'Emergencia',
            self::Pain => 'Dolor intenso',
            self::Complaint => 'Reclamo',
            self::ClinicalQuestion => 'Consulta clinica compleja',
            self::HumanRequested => 'Solicitud de hablar con persona',
            self::ToolFailure => 'Fallo de herramienta',
            self::Timeout => 'Tiempo de espera agotado',
        };
    }
}
