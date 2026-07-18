<?php

namespace App\Services;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialPipelineStage;
use App\Models\SocialComment;
use App\Models\SocialLinkEvent;

class SocialPipelineAutomationService
{
    public function applyEngagement(SocialComment $comment, SocialLinkEvent $event): SocialComment
    {
        $comment = $comment->refresh();

        if ($this->isTerminal($comment)) {
            return $comment;
        }

        if ($event->event_type === 'whatsapp_click') {
            return app(SocialPipelineTransitionService::class)->toAppointment(
                $comment,
                SocialConversionStatus::WhatsappStarted,
                'Movido automaticamente a cita por clic hacia WhatsApp.',
            );
        }

        if ($this->isNew($comment) && max((int) $comment->recent_engagement_score, (int) $comment->interest_score) >= $this->qualifyThreshold()) {
            return app(SocialPipelineTransitionService::class)->toQualified(
                $comment,
                null,
                'Movido automaticamente a calificado por engagement reciente.',
            );
        }

        return $comment;
    }

    public function applyAgentResponse(SocialComment $comment, array $agentResponse): SocialComment
    {
        $comment = $comment->refresh();

        if ($this->isTerminal($comment) || (bool) ($agentResponse['clinical_safety_flag'] ?? false)) {
            return $comment;
        }

        $intent = (string) ($agentResponse['intent'] ?? '');
        $suggestedStage = (string) ($agentResponse['suggested_pipeline_stage'] ?? '');
        $score = (int) ($agentResponse['closing_opportunity_score'] ?? 0);

        $translated = SocialPipelineTransitionService::translateAgentStage($suggestedStage);

        if ($translated === SocialPipelineStage::Won) {
            return app(SocialPipelineTransitionService::class)->toWon(
                $comment,
                'Movido automaticamente a ganado por deteccion de cierre en WhatsApp.',
            );
        }

        if ($translated === SocialPipelineStage::Lost) {
            $reason = $agentResponse['lost_reason'] ?? 'Rechazo detectado por agente comercial.';

            return app(SocialPipelineTransitionService::class)->toLost(
                $comment,
                $reason,
                'Movido automaticamente a perdido por agente comercial.',
            );
        }

        if ($translated === SocialPipelineStage::Proposal) {
            return app(SocialPipelineTransitionService::class)->toProposal(
                $comment,
                null,
                'Movido automaticamente a presupuesto por deteccion de negociacion en WhatsApp.',
            );
        }

        if (in_array($intent, ['appointment_interest', 'ready_to_book'], true)
            || $translated === SocialPipelineStage::Appointment
            || $suggestedStage === SocialPipelineStage::Appointment->value
            || $score >= $this->closingThreshold()
        ) {
            $candidate = $agentResponse['appointment_candidate'] ?? [];

            $preferredDate = $candidate['preferred_date_parsed'] ?? null;
            $preferredTime = $candidate['preferred_time_parsed'] ?? null;

            if ($preferredDate) {
                $comment->updateQuietly([
                    'appointment_scheduled_at' => $preferredTime
                        ? \Carbon\Carbon::parse($preferredDate . ' ' . $preferredTime)
                        : \Carbon\Carbon::parse($preferredDate),
                    'ai_intent' => $candidate['intent_type'] ?? $intent,
                    'ai_confidence' => $candidate['intent_confidence'] ?? $score,
                ]);
            } else {
                $comment->updateQuietly([
                    'ai_intent' => $candidate['intent_type'] ?? $intent,
                    'ai_confidence' => $candidate['intent_confidence'] ?? $score,
                ]);
            }

            return app(SocialPipelineTransitionService::class)->toAppointment(
                $comment,
                SocialConversionStatus::WhatsappStarted,
                'Movido automaticamente a cita por intencion detectada en WhatsApp.',
            );
        }

        if ($this->isNew($comment) && $score >= $this->qualifyThreshold()) {
            return app(SocialPipelineTransitionService::class)->toQualified(
                $comment,
                null,
                'Movido automaticamente a calificado por score de oportunidad en WhatsApp.',
            );
        }

        return $comment;
    }

    private function isTerminal(SocialComment $comment): bool
    {
        return in_array($comment->pipeline_stage, [
            SocialPipelineStage::Won,
            SocialPipelineStage::Lost,
        ], true);
    }

    private function isNew(SocialComment $comment): bool
    {
        return $comment->pipeline_stage === null || $comment->pipeline_stage === SocialPipelineStage::New;
    }

    private function qualifyThreshold(): int
    {
        return (int) app(SocialCrmSettingsService::class)->get('social_pipeline_auto_qualify_recent_score', 50);
    }

    private function closingThreshold(): int
    {
        return (int) app(SocialCrmSettingsService::class)->get('social_pipeline_closing_opportunity_threshold', 75);
    }
}
