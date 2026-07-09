<?php

namespace App\Services;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialPipelineStage;
use App\Models\SocialComment;

class SocialPipelineTransitionService
{
    public function toNew(SocialComment $comment, ?string $notes = null): SocialComment
    {
        return $this->transition($comment, SocialPipelineStage::New, SocialConversionStatus::None, $notes);
    }

    public function toQualified(SocialComment $comment, ?SocialConversionStatus $conversionStatus = null, ?string $notes = null): SocialComment
    {
        return $this->transition($comment, SocialPipelineStage::Qualified, $conversionStatus ?? SocialConversionStatus::TokenGenerated, $notes);
    }

    public function toAppointment(SocialComment $comment, ?SocialConversionStatus $conversionStatus = null, ?string $notes = null): SocialComment
    {
        return $this->transition($comment, SocialPipelineStage::Appointment, $conversionStatus ?? SocialConversionStatus::WhatsappStarted, $notes);
    }

    public function toProposal(SocialComment $comment, ?SocialConversionStatus $conversionStatus = null, ?string $notes = null): SocialComment
    {
        return $this->transition($comment, SocialPipelineStage::Proposal, $conversionStatus ?? SocialConversionStatus::AppointmentCreated, $notes);
    }

    public function toWon(SocialComment $comment, ?string $notes = null): SocialComment
    {
        return $this->transition($comment, SocialPipelineStage::Won, SocialConversionStatus::Converted, $notes, [
            'converted_at' => now(),
        ]);
    }

    public function toLost(SocialComment $comment, string $reason, ?string $notes = null): SocialComment
    {
        return $this->transition($comment, SocialPipelineStage::Lost, SocialConversionStatus::Lost, $notes, [
            'lost_at' => now(),
            'lost_reason' => $reason ?: null,
            'follow_up_at' => null,
        ]);
    }

    public function moveToNoShow(SocialComment $comment, ?string $notes = null): SocialComment
    {
        return $this->transition($comment, SocialPipelineStage::Qualified, SocialConversionStatus::WhatsappStarted, $notes, [
            'follow_up_at' => now(),
            'follow_up_notes' => 'No asistio a la cita. Contactar para reagendar.',
        ]);
    }

    public static function translateAgentStage(string $stage): ?SocialPipelineStage
    {
        return match ($stage) {
            'lead', 'new' => SocialPipelineStage::Qualified,
            'appointment' => SocialPipelineStage::Appointment,
            'negotiation', 'proposal' => SocialPipelineStage::Proposal,
            'closed_won', 'won' => SocialPipelineStage::Won,
            'closed_lost', 'lost' => SocialPipelineStage::Lost,
            default => null,
        };
    }

    private function transition(
        SocialComment $comment,
        SocialPipelineStage $stage,
        SocialConversionStatus $conversionStatus,
        ?string $notes = null,
        array $extra = [],
    ): SocialComment {
        $comment = $comment->refresh();

        $previousStage = $comment->pipeline_stage;

        if ($previousStage === $stage) {
            return $comment;
        }

        $data = array_merge([
            'pipeline_stage' => $stage,
            'conversion_status' => $conversionStatus,
        ], $extra);

        $comment->update($data);

        $fromLabel = $previousStage?->label() ?? 'sin etapa';
        $toLabel = $stage->label();

        $comment->actions()->create([
            'action' => SocialCommentActionType::PipelineStageChanged,
            'performed_by' => auth()->id(),
            'notes' => $notes ?? "Movido de {$fromLabel} a {$toLabel}.",
            'external_response' => [
                'from' => $previousStage?->value,
                'to' => $stage->value,
                'conversion_status' => $conversionStatus->value,
            ],
        ]);

        return $comment->refresh();
    }
}
