<?php

namespace App\Enums;

enum SocialCommentStatus: string
{
    case New = 'new';
    case Classified = 'classified';
    case ReviewRequired = 'review_required';
    case Responded = 'responded';
    case Hidden = 'hidden';
    case Ignored = 'ignored';
    case MarkedAsSpam = 'marked_as_spam';
    case Escalated = 'escalated';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nuevo',
            self::Classified => 'Clasificado',
            self::ReviewRequired => 'Requiere revision',
            self::Responded => 'Respondido',
            self::Hidden => 'Oculto',
            self::Ignored => 'Ignorado',
            self::MarkedAsSpam => 'Marcado como spam',
            self::Escalated => 'Escalado',
            self::Error => 'Error',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'gray',
            self::Classified => 'info',
            self::ReviewRequired => 'warning',
            self::Responded => 'success',
            self::Hidden, self::Ignored => 'gray',
            self::MarkedAsSpam, self::Escalated, self::Error => 'danger',
        };
    }
}
