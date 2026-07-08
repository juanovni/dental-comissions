<?php

namespace App\Enums;

enum SocialSuggestedAction: string
{
    case Reply = 'reply';
    case ReplyAndRouteToWhatsapp = 'reply_and_route_to_whatsapp';
    case Hide = 'hide';
    case Review = 'review';
    case Ignore = 'ignore';
    case MarkAsSpam = 'mark_as_spam';
    case Escalate = 'escalate';
    case ThankUser = 'thank_user';

    public function label(): string
    {
        return match ($this) {
            self::Reply => 'Responder',
            self::ReplyAndRouteToWhatsapp => 'Responder y derivar a WhatsApp',
            self::Hide => 'Ocultar',
            self::Review => 'Revisar',
            self::Ignore => 'Ignorar',
            self::MarkAsSpam => 'Marcar como spam',
            self::Escalate => 'Escalar',
            self::ThankUser => 'Agradecer',
        };
    }
}
