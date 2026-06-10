<?php

namespace App\Services;

use App\Enums\SocialConversionStatus;
use App\Enums\SocialIdentityStatus;
use App\Models\ActivityRecord;
use App\Models\SocialComment;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SocialRoiService
{
    public function attributeActivity(ActivityRecord $activity): ?ActivityRecord
    {
        if ($activity->social_comment_id || !$activity->patient_id) {
            return $activity->social_comment_id ? $activity : null;
        }

        $identity = SocialIdentity::query()
            ->where('patient_id', $activity->patient_id)
            ->latest('linked_at')
            ->latest('last_seen_at')
            ->first();

        if (!$identity) {
            return null;
        }

        $comment = SocialComment::query()
            ->where('social_identity_id', $identity->id)
            ->whereNotNull('social_post_id')
            ->latest('converted_at')
            ->latest('whatsapp_redirected_at')
            ->latest('created_at')
            ->first();

        if (!$comment) {
            return null;
        }

        $activity->update([
            'social_comment_id' => $comment->id,
            'social_identity_id' => $identity->id,
            'social_post_id' => $comment->social_post_id,
            'social_attributed_at' => now(),
        ]);

        $comment->update([
            'conversion_status' => SocialConversionStatus::Converted,
            'converted_patient_id' => $activity->patient_id,
            'converted_at' => $comment->converted_at ?: now(),
        ]);

        $identity->update([
            'status' => SocialIdentityStatus::Converted,
            'last_seen_at' => now(),
        ]);

        $this->refreshPostMetrics($comment->socialPost);

        return $activity->refresh();
    }

    public function refreshPostMetrics(?SocialPost $post): void
    {
        if (!$post) {
            return;
        }

        $metrics = ActivityRecord::query()
            ->where('social_post_id', $post->id)
            ->selectRaw('COALESCE(SUM(internal_rate_snapshot), 0) as revenue, COUNT(*) as conversions')
            ->first();

        $post->update([
            'revenue_generated' => (float) ($metrics->revenue ?? 0),
            'conversion_count' => (int) ($metrics->conversions ?? 0),
        ]);
    }

    public function summary(): array
    {
        $socialActivities = ActivityRecord::query()->whereNotNull('social_post_id');
        $comments = SocialComment::query();

        $leadCount = (clone $comments)->whereNotNull('social_identity_id')->count();
        $whatsappCount = (clone $comments)->whereNotNull('whatsapp_redirected_at')->count();
        $linkedCount = (clone $comments)->whereNotNull('converted_patient_id')->count();
        $activityCount = (clone $socialActivities)->count();
        $revenue = (clone $socialActivities)->sum('internal_rate_snapshot');
        $leakageCount = $this->leakageQuery()->count();

        return [
            'lead_count' => $leadCount,
            'whatsapp_count' => $whatsappCount,
            'linked_count' => $linkedCount,
            'activity_count' => $activityCount,
            'revenue' => (float) $revenue,
            'leakage_count' => $leakageCount,
            'lead_to_activity_rate' => $leadCount > 0 ? round(($activityCount / $leadCount) * 100, 1) : 0,
        ];
    }

    public function topPosts(int $limit = 8): Collection
    {
        return SocialPost::query()
            ->with('socialAccount')
            ->where(function (Builder $query): void {
                $query->where('revenue_generated', '>', 0)
                    ->orWhere('conversion_count', '>', 0);
            })
            ->orderByDesc('revenue_generated')
            ->orderByDesc('conversion_count')
            ->limit($limit)
            ->get();
    }

    public function leakageQuery(): Builder
    {
        return SocialComment::query()
            ->whereNull('converted_patient_id')
            ->whereNull('whatsapp_redirected_at')
            ->where('created_at', '<=', now()->subDay())
            ->where(function (Builder $query): void {
                $query->whereIn('classification', ['sales_lead', 'commercial_question'])
                    ->orWhereNotNull('social_identity_id');
            });
    }

    public function funnelData(): array
    {
        $summary = $this->summary();

        return [
            'labels' => ['Comentarios', 'WhatsApp', 'Ficha', 'Actividad'],
            'values' => [
                $summary['lead_count'],
                $summary['whatsapp_count'],
                $summary['linked_count'],
                $summary['activity_count'],
            ],
        ];
    }
}
