<?php

namespace App\Services;

use App\Models\SocialComment;
use App\Models\SocialLinkEvent;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class SocialLeadEngagementPriorityService
{
    private const WEIGHTS = [
        'whatsapp_click' => 40,
        'video_complete' => 30,
        'button_click' => 25,
        'section_click' => 18,
        'video_75' => 22,
        'duration_threshold' => 20,
        'revisit' => 15,
        'video_50' => 15,
        'video_25' => 8,
        'view' => 5,
        'video_start' => 4,
        'engagement_ping' => 2,
    ];

    public function refresh(SocialComment $comment): SocialComment
    {
        $events = $comment->linkEvents()
            ->where('created_at', '>=', now()->subDay())
            ->latest('created_at')
            ->get();

        $latest = $events->first();
        $score = $events->sum(fn (SocialLinkEvent $event): int => $this->scoreEvent($event));

        $comment->update([
            'recent_engagement_score' => $score,
            'last_engagement_at' => $latest?->created_at,
            'engagement_event_count_1h' => $this->countSince($events, now()->subHour()),
            'engagement_event_count_24h' => $events->count(),
            'last_engagement_event_type' => $latest?->event_type,
            'engagement_priority_reason' => $this->priorityReason($events),
        ]);

        return $comment->refresh();
    }

    private function scoreEvent(SocialLinkEvent $event): int
    {
        $base = $event->event_type === 'video_play_seconds'
            ? min((int) floor(($event->duration_seconds ?? 0) / 10), 20)
            : (self::WEIGHTS[$event->event_type] ?? 1);

        return (int) ceil($base * $this->recencyMultiplier($event));
    }

    private function recencyMultiplier(SocialLinkEvent $event): float
    {
        $minutes = $event->created_at?->diffInMinutes(now()) ?? 1440;

        return match (true) {
            $minutes <= 15 => 1,
            $minutes <= 60 => .7,
            $minutes <= 360 => .4,
            default => .2,
        };
    }

    private function countSince(Collection $events, CarbonInterface $since): int
    {
        return $events->filter(fn (SocialLinkEvent $event): bool => $event->created_at?->gte($since))->count();
    }

    private function priorityReason(Collection $events): ?string
    {
        $event = $events->first(fn (SocialLinkEvent $event): bool => in_array($event->event_type, [
            'whatsapp_click',
            'video_complete',
            'button_click',
            'section_click',
            'duration_threshold',
            'revisit',
        ], true)) ?? $events->first();

        if (! $event) {
            return null;
        }

        return SocialLinkEventMapper::label($event->event_type).' '.$event->created_at?->diffForHumans();
    }
}
