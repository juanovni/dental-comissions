<?php

namespace App\Services;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialConversionStatus;
use App\Models\SocialComment;
use Illuminate\Database\Eloquent\Builder;

class SocialLeadOperationsService
{
    public function queryActionableLeads(): Builder
    {
        $settings = app(SocialCrmSettingsService::class);
        $urgentScore = $settings->salesUrgentScoreThreshold();

        return SocialComment::query()
            ->with(['convertedPatient', 'socialIdentity.patient', 'socialAccount', 'suggestedProcedure'])
            ->whereNull('lost_at')
            ->where(function (Builder $query) use ($urgentScore): void {
                $query->where('interest_score', '>=', $urgentScore)
                    ->orWhereNotNull('hot_lead_at')
                    ->orWhereNotNull('reheated_at')
                    ->orWhereNotNull('follow_up_at')
                    ->orWhereIn('conversion_status', [
                        SocialConversionStatus::TokenGenerated->value,
                        SocialConversionStatus::PendingPatientCreation->value,
                    ]);
            })
            ->where(function (Builder $query): void {
                $query->whereNull('contacted_at')
                    ->orWhereNotNull('follow_up_at');
            });
    }

    public function markContacted(SocialComment $comment, ?string $notes = null): SocialComment
    {
        $comment->update([
            'contacted_at' => now(),
            'follow_up_at' => null,
            'follow_up_notes' => null,
        ]);

        $comment->actions()->create([
            'action' => SocialCommentActionType::MarkAsContacted,
            'performed_by' => auth()->id(),
            'notes' => $notes ?: 'Lead contactado desde Leads Calientes.',
        ]);

        $this->resolveOpenAlerts($comment, 'Lead contactado.');

        return $comment->refresh();
    }

    public function scheduleFollowUp(SocialComment $comment, ?int $hours = null, ?string $notes = null): SocialComment
    {
        $hours ??= app(SocialCrmSettingsService::class)->salesDefaultFollowUpHours();
        $followUpAt = now()->addHours(max(1, $hours));

        $comment->update([
            'follow_up_at' => $followUpAt,
            'follow_up_notes' => $notes ?: 'Seguimiento programado desde Leads Calientes.',
        ]);

        $comment->actions()->create([
            'action' => SocialCommentActionType::ScheduleFollowUp,
            'performed_by' => auth()->id(),
            'notes' => $notes ?: 'Seguimiento programado desde Leads Calientes.',
            'external_response' => [
                'follow_up_at' => $followUpAt->toISOString(),
                'hours' => $hours,
            ],
        ]);

        $this->resolveOpenAlerts($comment, 'Lead marcado como perdido.');

        return $comment->refresh();
    }

    public function markLost(SocialComment $comment, ?string $reason = null, ?string $notes = null): SocialComment
    {
        $reason ??= app(SocialCrmSettingsService::class)->salesLostReasons()[0] ?? 'sin_respuesta';

        $comment->update([
            'lost_at' => now(),
            'lost_reason' => $reason,
            'follow_up_at' => null,
        ]);

        $comment->actions()->create([
            'action' => SocialCommentActionType::MarkAsLost,
            'performed_by' => auth()->id(),
            'notes' => $notes ?: 'Lead marcado como perdido desde Leads Calientes.',
            'external_response' => ['lost_reason' => $reason],
        ]);

        return $comment->refresh();
    }

    public function isOverdue(SocialComment $comment): bool
    {
        if ($comment->contacted_at || $comment->lost_at) {
            return false;
        }

        $anchor = $comment->hot_lead_at ?: $comment->reheated_at ?: $comment->whatsapp_redirected_at ?: $comment->created_at;
        $maxHours = app(SocialCrmSettingsService::class)->salesMaxHoursWithoutContact();

        return $anchor?->lte(now()->subHours($maxHours)) ?? false;
    }

    private function resolveOpenAlerts(SocialComment $comment, string $notes): void
    {
        $comment->leadAlerts()
            ->whereNull('resolved_at')
            ->get()
            ->each(fn ($alert) => app(SocialLeadAlertService::class)->resolve($alert, $notes));
    }
}
