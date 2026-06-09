<?php

namespace App\Enums;

enum WhatsappMessageStatus: string
{
    case Received = 'received';
    case Parsed = 'parsed';
    case Confirmed = 'confirmed';
    case NeedsReview = 'needs_review';
    case Processed = 'processed';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Recibido',
            self::Parsed => 'Parseado por IA',
            self::Confirmed => 'Confirmado',
            self::NeedsReview => 'Requiere revision',
            self::Processed => 'Procesado',
            self::Sent => 'Enviado',
            self::Failed => 'Error',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Received => 'warning',
            self::Parsed => 'info',
            self::Confirmed => 'success',
            self::NeedsReview => 'danger',
            self::Processed => 'info',
            self::Sent => 'success',
            self::Failed => 'gray',
        };
    }
}
