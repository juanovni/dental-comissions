<?php

namespace App\Services;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialConversionStatus;
use App\Models\SocialComment;
use App\Models\SocialLeadAlert;
use Illuminate\Database\Eloquent\Collection;

class SocialLeadAlertService
{
    public function createAlert(
        SocialComment $comment,
        string $type,
        string $severity = 'info',
        array $metadata = [],
    ): ?SocialLeadAlert {
        $settings = app(SocialCrmSettingsService::class);

        if (! $settings->alertsEnabled()) {
            return null;
        }

        $existing = SocialLeadAlert::query()
            ->where('social_comment_id', $comment->id)
            ->where('alert_type', $type)
            ->whereNull('resolved_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        $message = $settings->alertMessage($type);

        return SocialLeadAlert::create([
            'social_comment_id' => $comment->id,
            'alert_type' => $type,
            'severity' => $severity,
            'title' => $message['title'],
            'message' => $message['message'],
            'metadata' => $metadata,
        ]);
    }

    public function resolve(SocialLeadAlert $alert, ?string $notes = null): SocialLeadAlert
    {
        $alert->update([
            'resolved_at' => now(),
            'resolved_by' => auth()->id(),
            'resolution_notes' => $notes ?: 'Alerta resuelta desde el panel.',
        ]);

        return $alert->refresh();
    }

    public function runScheduledChecks(): array
    {
        $summary = [
            'no_contact_overdue' => 0,
            'follow_up_due' => 0,
            'pending_patient_creation' => 0,
            'whatsapp_click_no_message' => 0,
        ];

        $operations = app(SocialLeadOperationsService::class);
        $settings = app(SocialCrmSettingsService::class);

        $operations->queryActionableLeads()
            ->get()
            ->each(function (SocialComment $comment) use (&$summary, $operations, $settings): void {
                if ($operations->isOverdue($comment)) {
                    $alert = $this->createAlert($comment, 'no_contact_overdue', 'danger', [
                        'interest_score' => $comment->interest_score,
                        'hot_lead_at' => $comment->hot_lead_at?->toISOString(),
                    ]);

                    if ($alert?->wasRecentlyCreated) {
                        $summary['no_contact_overdue']++;
                    }
                }

                if ($comment->follow_up_at?->isPast()) {
                    $alert = $this->createAlert($comment, 'follow_up_due', 'warning', [
                        'follow_up_at' => $comment->follow_up_at->toISOString(),
                    ]);

                    if ($alert?->wasRecentlyCreated) {
                        $summary['follow_up_due']++;
                    }
                }

                if ($comment->conversion_status === SocialConversionStatus::PendingPatientCreation) {
                    $alert = $this->createAlert($comment, 'pending_patient_creation', 'warning', [
                        'tracking_token' => $comment->tracking_token,
                    ]);

                    if ($alert?->wasRecentlyCreated) {
                        $summary['pending_patient_creation']++;
                    }
                }

                $clickWithoutMessage = $comment->linkEvents()
                    ->where('event_type', 'whatsapp_click')
                    ->latest()
                    ->first();

                if ($clickWithoutMessage && ! $comment->actions()->where('action', SocialCommentActionType::WhatsappHandshake)->exists()) {
                    $minutes = $settings->whatsappClickFollowUpMinutes();

                    if ($clickWithoutMessage->created_at->lte(now()->subMinutes($minutes))) {
                        $alert = $this->createAlert($comment, 'whatsapp_click_no_message', 'warning', [
                            'whatsapp_clicked_at' => $clickWithoutMessage->created_at->toISOString(),
                            'minutes_without_message' => $clickWithoutMessage->created_at->diffInMinutes(now()),
                        ]);

                        if ($alert?->wasRecentlyCreated) {
                            $summary['whatsapp_click_no_message']++;
                        }

                        if ($alert?->wasRecentlyCreated && $settings->whatsappFollowUpAutoReplyEnabled()) {
                            app(SocialAutoReplyService::class)->sendFollowUpReply($comment->fresh());
                        }
                    }
                }
            });

        return $summary;
    }

    public function openAlerts(): Collection
    {
        return SocialLeadAlert::query()
            ->with(['socialComment.socialIdentity.patient', 'socialComment.convertedPatient', 'socialComment.suggestedProcedure'])
            ->whereNull('resolved_at')
            ->orderByRaw("case when severity = 'danger' then 0 when severity = 'warning' then 1 else 2 end")
            ->latest('created_at')
            ->get();
    }
}
