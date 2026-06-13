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
    case GenerateWhatsappToken = 'generate_whatsapp_token';
    case RedirectToWhatsapp = 'redirect_to_whatsapp';
    case WhatsappHandshake = 'whatsapp_handshake';
    case LinkIdentity = 'link_identity';
    case CreatePatientFromLead = 'create_patient_from_lead';
    case LeadScoreUpdated = 'lead_score_updated';
    case SmartLinkVisited = 'smart_link_visited';
    case SmartLinkRevisited = 'smart_link_revisited';
    case LeadReheated = 'lead_reheated';
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
            self::GenerateWhatsappToken => 'Generar token WhatsApp',
            self::RedirectToWhatsapp => 'Derivar a WhatsApp',
            self::WhatsappHandshake => 'Handshake WhatsApp',
            self::LinkIdentity => 'Vincular identidad',
            self::CreatePatientFromLead => 'Crear paciente desde lead',
            self::LeadScoreUpdated => 'Actualizar puntaje de lead',
            self::SmartLinkVisited => 'Visita Smart Link',
            self::SmartLinkRevisited => 'Reingreso Smart Link',
            self::LeadReheated => 'Lead recalentado',
            self::Error => 'Error',
        };
    }
}
