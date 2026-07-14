<?php

namespace App\Services;

use App\Enums\SocialCommentActionType;
use App\Models\SocialComment;

class SocialLeadScoringService
{
    public function addScore(
        SocialComment $comment,
        int $points,
        string $reason,
        array $context = [],
        ?SocialCommentActionType $action = null,
    ): SocialComment {
        if ($points <= 0) {
            return $comment->refresh();
        }

        $previousScore = (int) $comment->interest_score;
        $newScore = $previousScore + $points;
        $threshold = app(SocialCrmSettingsService::class)->hotLeadThreshold();

        $updates = ['interest_score' => $newScore];

        $becameHotLead = $previousScore < $threshold && $newScore >= $threshold && ! $comment->hot_lead_at;

        if ($becameHotLead) {
            $updates['hot_lead_at'] = now();
        }

        $comment->update($updates);

        $comment->actions()->create([
            'action' => $action ?? SocialCommentActionType::LeadScoreUpdated,
            'notes' => $reason,
            'external_response' => array_merge($context, [
                'points' => $points,
                'previous_score' => $previousScore,
                'new_score' => $newScore,
                'hot_lead_threshold' => $threshold,
                'hot_lead' => $newScore >= $threshold,
            ]),
        ]);

        if ($becameHotLead) {
            app(SocialLeadAlertService::class)->createAlert($comment->refresh(), 'hot_lead_created', 'danger', [
                'previous_score' => $previousScore,
                'new_score' => $newScore,
                'threshold' => $threshold,
            ]);
        }

        return $comment->refresh();
    }

    public function scoreTokenGenerated(SocialComment $comment): SocialComment
    {
        return $this->addScore(
            $comment,
            app(SocialCrmSettingsService::class)->scoreForTokenGenerated(),
            'Puntaje sumado por generacion de token WhatsApp.',
            ['event' => 'token_generated'],
            SocialCommentActionType::LeadScoreUpdated,
        );
    }

    public function scoreWhatsappFirstMessage(SocialComment $comment): SocialComment
    {
        return $this->addScore(
            $comment,
            app(SocialCrmSettingsService::class)->scoreForWhatsappFirstMessage(),
            'Puntaje sumado por primer mensaje entrante de WhatsApp.',
            ['event' => 'whatsapp_first_message'],
            SocialCommentActionType::LeadScoreUpdated,
        );
    }

    public function scoreWhatsappTreatmentInterest(SocialComment $comment): SocialComment
    {
        if ($this->hasScoreEvent($comment, 'whatsapp_treatment_interest')) {
            return $comment->refresh();
        }

        return $this->addScore(
            $comment,
            app(SocialCrmSettingsService::class)->scoreForWhatsappTreatmentInterest(),
            'Puntaje sumado por interes comercial detectado en WhatsApp.',
            ['event' => 'whatsapp_treatment_interest'],
            SocialCommentActionType::LeadScoreUpdated,
        );
    }

    public function scoreWhatsappAppointmentIntent(SocialComment $comment): SocialComment
    {
        if ($this->hasScoreEvent($comment, 'whatsapp_appointment_intent')) {
            return $comment->refresh();
        }

        return $this->addScore(
            $comment,
            app(SocialCrmSettingsService::class)->scoreForWhatsappAppointmentIntent(),
            'Puntaje sumado por intencion de cita detectada en WhatsApp.',
            ['event' => 'whatsapp_appointment_intent'],
            SocialCommentActionType::LeadScoreUpdated,
        );
    }

    public function scoreWhatsappSlotSelected(SocialComment $comment): SocialComment
    {
        if ($this->hasScoreEvent($comment, 'whatsapp_slot_selected')) {
            return $comment->refresh();
        }

        return $this->addScore(
            $comment,
            app(SocialCrmSettingsService::class)->scoreForWhatsappSlotSelected(),
            'Puntaje sumado por seleccion de horario en WhatsApp.',
            ['event' => 'whatsapp_slot_selected'],
            SocialCommentActionType::LeadScoreUpdated,
        );
    }

    public function scoreSmartLinkVisit(SocialComment $comment): SocialComment
    {
        $settings = app(SocialCrmSettingsService::class);
        $previousVisit = $comment->last_smart_link_visited_at;
        $isRevisit = filled($previousVisit);
        $isReheated = $isRevisit && $previousVisit->lte(now()->subHours($settings->reheatedAfterHours()));

        $points = $isRevisit
            ? $settings->scoreForSmartLinkRevisit()
            : $settings->scoreForSmartLinkClick();

        if ($isReheated) {
            $points += $settings->scoreForReheatedRevisitBonus();
        }

        $comment->update([
            'last_smart_link_visited_at' => now(),
            'reheated_at' => $isReheated ? now() : $comment->reheated_at,
        ]);

        $comment = $this->addScore(
            $comment->refresh(),
            $points,
            $isReheated
                ? 'Lead recalentado por reingreso al Smart Link despues de tiempo muerto.'
                : ($isRevisit ? 'Puntaje sumado por reingreso al Smart Link.' : 'Puntaje sumado por primer clic en Smart Link.'),
            [
                'event' => $isReheated ? 'smart_link_reheated_revisit' : ($isRevisit ? 'smart_link_revisit' : 'smart_link_first_visit'),
                'previous_visit_at' => $previousVisit?->toISOString(),
                'reheated_after_hours' => $settings->reheatedAfterHours(),
            ],
            $isReheated
                ? SocialCommentActionType::LeadReheated
                : ($isRevisit ? SocialCommentActionType::SmartLinkRevisited : SocialCommentActionType::SmartLinkVisited),
        );

        if ($isReheated) {
            app(SocialLeadAlertService::class)->createAlert($comment, 'lead_reheated', 'warning', [
                'previous_visit_at' => $previousVisit?->toISOString(),
                'reheated_after_hours' => $settings->reheatedAfterHours(),
            ]);
        }

        return $comment;
    }

    private function hasScoreEvent(SocialComment $comment, string $event): bool
    {
        return $comment->actions()
            ->where('action', SocialCommentActionType::LeadScoreUpdated->value)
            ->get()
            ->contains(fn ($action): bool => ($action->external_response['event'] ?? null) === $event);
    }
}
