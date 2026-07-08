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
    case WhatsappSalesAgent = 'whatsapp_sales_agent';
    case LinkIdentity = 'link_identity';
    case CreatePatientFromLead = 'create_patient_from_lead';
    case AppointmentCreated = 'appointment_created';
    case LeadScoreUpdated = 'lead_score_updated';
    case SmartLinkVisited = 'smart_link_visited';
    case SmartLinkRevisited = 'smart_link_revisited';
    case LeadReheated = 'lead_reheated';
    case MarkAsContacted = 'mark_as_contacted';
    case ScheduleFollowUp = 'schedule_follow_up';
    case MarkAsLost = 'mark_as_lost';
    case PipelineStageChanged = 'pipeline_stage_changed';
    case Error = 'error';
    case AutoReplyGenerated = 'auto_reply_generated';
    case AutoReplySent = 'auto_reply_sent';
    case AutoReplyFailed = 'auto_reply_failed';
    case AutoReplySkipped = 'auto_reply_skipped';
    case WhatsappClickFollowUpSent = 'whatsapp_click_follow_up_sent';
    case BookingIntentDetected = 'booking_intent_detected';
    case AppointmentAutoCreated = 'appointment_auto_created';
    case BookingConfirmed = 'booking_confirmed';
    case BookingRejected = 'booking_rejected';
    case BookingModified = 'booking_modified';

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
            self::WhatsappSalesAgent => 'Agente comercial WhatsApp',
            self::LinkIdentity => 'Vincular identidad',
            self::CreatePatientFromLead => 'Crear paciente desde lead',
            self::AppointmentCreated => 'Crear cita',
            self::LeadScoreUpdated => 'Actualizar puntaje de lead',
            self::SmartLinkVisited => 'Visita Smart Link',
            self::SmartLinkRevisited => 'Reingreso Smart Link',
            self::LeadReheated => 'Lead recalentado',
            self::MarkAsContacted => 'Marcar como contactado',
            self::ScheduleFollowUp => 'Programar seguimiento',
            self::MarkAsLost => 'Marcar como perdido',
            self::PipelineStageChanged => 'Cambiar etapa del pipeline',
            self::Error => 'Error',
            self::AutoReplyGenerated => 'Respuesta automática generada',
            self::AutoReplySent => 'Respuesta automática enviada',
            self::AutoReplyFailed => 'Respuesta automática fallida',
            self::AutoReplySkipped => 'Respuesta automática omitida',
            self::WhatsappClickFollowUpSent => 'Seguimiento por clic WhatsApp sin mensaje',
            self::BookingIntentDetected => 'Intención de agendamiento detectada',
            self::AppointmentAutoCreated => 'Cita creada automáticamente',
            self::BookingConfirmed => 'Cita confirmada por lead',
            self::BookingRejected => 'Cita rechazada por lead',
            self::BookingModified => 'Cita modificada por lead',
        };
    }
}
