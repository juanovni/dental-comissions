<?php

namespace App\Services;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialCommentClassification;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Models\ActivityRecord;
use App\Models\SocialComment;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
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
            'orphan_attribution_count' => $this->orphanAttributionCount(),
            'lead_to_activity_rate' => $leadCount > 0 ? round(($activityCount / $leadCount) * 100, 1) : 0,
        ];
    }

    public function orphanAttributionCount(): int
    {
        return SocialComment::query()
            ->where('conversion_status', SocialConversionStatus::TokenGenerated->value)
            ->whereNull('whatsapp_redirected_at')
            ->count();
    }

    public function platformPerformanceData(): array
    {
        $platforms = collect(SocialPlatform::cases());

        $leadRows = SocialComment::query()
            ->leftJoin('social_identities', 'social_identities.id', '=', 'social_comments.social_identity_id')
            ->whereIn('social_comments.classification', [
                SocialCommentClassification::SalesLead->value,
                SocialCommentClassification::CommercialQuestion->value,
            ])
            ->selectRaw('social_comments.platform as platform')
            ->selectRaw('SUM(CASE WHEN social_identities.patient_id IS NULL THEN 1 ELSE 0 END) as new_leads')
            ->selectRaw('SUM(CASE WHEN social_identities.patient_id IS NOT NULL THEN 1 ELSE 0 END) as recurring_patients')
            ->groupBy('social_comments.platform')
            ->get()
            ->keyBy('platform');

        $revenueRows = ActivityRecord::query()
            ->leftJoin('social_posts', 'social_posts.id', '=', 'activity_records.social_post_id')
            ->leftJoin('social_comments', 'social_comments.id', '=', 'activity_records.social_comment_id')
            ->leftJoin('social_identities', 'social_identities.id', '=', 'activity_records.social_identity_id')
            ->where(function (Builder $query): void {
                $query->whereNotNull('activity_records.social_post_id')
                    ->orWhereNotNull('activity_records.social_comment_id')
                    ->orWhereNotNull('activity_records.social_identity_id');
            })
            ->selectRaw('COALESCE(social_posts.platform, social_comments.platform, social_identities.platform) as platform')
            ->selectRaw('COALESCE(SUM(activity_records.internal_rate_snapshot), 0) as revenue')
            ->groupByRaw('COALESCE(social_posts.platform, social_comments.platform, social_identities.platform)')
            ->pluck('revenue', 'platform');

        return [
            'labels' => $platforms->map(fn (SocialPlatform $platform): string => $platform->label())->all(),
            'new_leads' => $platforms->map(fn (SocialPlatform $platform): int => (int) ($leadRows[$platform->value]->new_leads ?? 0))->all(),
            'recurring_patients' => $platforms->map(fn (SocialPlatform $platform): int => (int) ($leadRows[$platform->value]->recurring_patients ?? 0))->all(),
            'revenue' => $platforms->map(fn (SocialPlatform $platform): float => round((float) ($revenueRows[$platform->value] ?? 0), 2))->all(),
        ];
    }

    public function procedureConversionData(int $limit = 5): array
    {
        $rows = SocialComment::query()
            ->leftJoin('procedures', 'procedures.id', '=', 'social_comments.suggested_procedure_id')
            ->leftJoin('activity_records', 'activity_records.social_comment_id', '=', 'social_comments.id')
            ->selectRaw("COALESCE(procedures.name, 'Consulta General/Otros') as label")
            ->selectRaw('COUNT(DISTINCT social_comments.id) as comments_count')
            ->selectRaw('COUNT(DISTINCT activity_records.id) as conversions_count')
            ->groupByRaw("COALESCE(procedures.name, 'Consulta General/Otros')")
            ->orderByDesc('comments_count')
            ->limit($limit)
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'comments' => $rows->map(fn ($row): int => (int) $row->comments_count)->all(),
            'conversion_rates' => $rows->map(function ($row): float {
                $comments = (int) $row->comments_count;

                if ($comments === 0) {
                    return 0;
                }

                return round(((int) $row->conversions_count / $comments) * 100, 1);
            })->all(),
        ];
    }

    public function responseTimeVsRevenueData(int $weeks = 12): array
    {
        $start = now()->startOfWeek()->subWeeks($weeks - 1);
        $actions = [
            SocialCommentActionType::Reply->value,
            SocialCommentActionType::RedirectToWhatsapp->value,
            SocialCommentActionType::WhatsappHandshake->value,
            SocialCommentActionType::LinkIdentity->value,
            SocialCommentActionType::CreatePatientFromLead->value,
        ];
        $firstActions = DB::table('social_comment_actions')
            ->selectRaw('social_comment_id, MIN(created_at) as first_action_at')
            ->whereIn('action', $actions)
            ->groupBy('social_comment_id');

        $labels = [];
        $responseMinutes = [];
        $revenue = [];

        for ($index = 0; $index < $weeks; $index++) {
            $weekStart = (clone $start)->addWeeks($index)->startOfWeek();
            $weekEnd = (clone $weekStart)->endOfWeek();

            $averageMinutes = DB::table('social_comments')
                ->joinSub($firstActions, 'first_actions', function ($join): void {
                    $join->on('first_actions.social_comment_id', '=', 'social_comments.id');
                })
                ->whereBetween('social_comments.created_at', [$weekStart, $weekEnd])
                ->selectRaw('AVG(EXTRACT(EPOCH FROM (first_actions.first_action_at::timestamp - social_comments.created_at::timestamp)) / 60) as average_minutes')
                ->value('average_minutes');

            $weekRevenue = ActivityRecord::query()
                ->whereNotNull('social_attributed_at')
                ->whereBetween('activity_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->sum('internal_rate_snapshot');

            $labels[] = 'Sem ' . $weekStart->isoWeek();
            $responseMinutes[] = round((float) ($averageMinutes ?? 0), 1);
            $revenue[] = round((float) $weekRevenue, 2);
        }

        return [
            'labels' => $labels,
            'response_minutes' => $responseMinutes,
            'revenue' => $revenue,
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
