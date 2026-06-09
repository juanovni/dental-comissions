<?php

namespace App\Enums;

enum SocialCommentActionType: string
{
    case Reply = 'reply';
    case Hide = 'hide';
    case MarkAsReviewed = 'mark_as_reviewed';
    case Ignore = 'ignore';
    case Escalate = 'escalate';
    case MarkAsSpam = 'mark_as_spam';
    case Classify = 'classify';
    case Sync = 'sync';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Reply => 'Responder',
            self::Hide => 'Ocultar',
            self::MarkAsReviewed => 'Marcar como revisado',
            self::Ignore => 'Ignorar',
            self::Escalate => 'Escalar',
            self::MarkAsSpam => 'Marcar como spam',
            self::Classify => 'Clasificar',
            self::Sync => 'Sincronizar',
            self::Error => 'Error',
        };
    }
}
