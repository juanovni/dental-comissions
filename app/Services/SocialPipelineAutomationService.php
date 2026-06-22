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
            return $this->moveTo(
                $comment,
                SocialPipelineStage::Appointment,
                'Movido automaticamente a cita por clic hacia WhatsApp.',
                [
                    'source' => 'smart_link_engagement',
                    'event_type' => $event->event_type,
                    'social_link_event_id' => $event->id,
                    'recent_engagement_score' => (int) $comment->recent_engagement_score,
                ],
                SocialConversionStatus::WhatsappStarted,
            );
        }

        if ($this->isNew($comment) && (int) $comment->recent_engagement_score >= $this->qualifyThreshold()) {
            return $this->moveTo(
                $comment,
                SocialPipelineStage::Qualified,
                'Movido automaticamente a calificado por engagement reciente.',
                [
                    'source' => 'recent_engagement_score',
                    'event_type' => $event->event_type,
                    'social_link_event_id' => $event->id,
                    'recent_engagement_score' => (int) $comment->recent_engagement_score,
                    'threshold' => $this->qualifyThreshold(),
                ],
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

        if (in_array($intent, ['appointment_interest', 'ready_to_book'], true)
            || $suggestedStage === SocialPipelineStage::Appointment->value
            || $score >= $this->closingThreshold()
        ) {
            return $this->moveTo(
                $comment,
                SocialPipelineStage::Appointment,
                'Movido automaticamente a cita por intencion detectada en WhatsApp.',
                [
                    'source' => 'whatsapp_sales_agent',
                    'intent' => $intent,
                    'closing_opportunity_score' => $score,
                    'suggested_pipeline_stage' => $suggestedStage,
                ],
                SocialConversionStatus::WhatsappStarted,
            );
        }

        return $comment;
    }

    private function moveTo(
        SocialComment $comment,
        SocialPipelineStage $stage,
        string $notes,
        array $context,
        ?SocialConversionStatus $conversionStatus = null,
    ): SocialComment {
        if ($comment->pipeline_stage === $stage) {
            return $comment;
        }

        $previousStage = $comment->pipeline_stage;
        $updates = ['pipeline_stage' => $stage];

        if ($conversionStatus && ! in_array($comment->conversion_status, [
            SocialConversionStatus::AppointmentCreated,
            SocialConversionStatus::Converted,
            SocialConversionStatus::Lost,
        ], true)) {
            $updates['conversion_status'] = $conversionStatus;
        }

        $comment->update($updates);

        $comment->actions()->create([
            'action' => SocialCommentActionType::PipelineStageChanged,
            'performed_by' => auth()->id(),
            'notes' => $notes,
            'external_response' => array_merge($context, [
                'from' => $previousStage?->value,
                'to' => $stage->value,
                'automated' => true,
            ]),
        ]);

        return $comment->refresh();
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
