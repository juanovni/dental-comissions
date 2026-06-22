<?php

namespace App\Events;

use App\Models\SocialComment;
use App\Models\SocialLinkEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadActivityDetected implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SocialComment $comment,
        public SocialLinkEvent $event,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('admin-notifications');
    }

    public function broadcastAs(): string
    {
        return 'LeadActivityDetected';
    }

    public function broadcastWith(): array
    {
        return [
            'lead_id' => $this->comment->id,
            'tracking_token' => $this->comment->tracking_token,
            'event_type' => $this->event->event_type,
            'interest_score' => (int) $this->comment->interest_score,
            'recent_engagement_score' => (int) $this->comment->recent_engagement_score,
            'last_engagement_at' => $this->comment->last_engagement_at?->toISOString(),
            'engagement_event_count_1h' => (int) $this->comment->engagement_event_count_1h,
            'engagement_event_count_24h' => (int) $this->comment->engagement_event_count_24h,
            'last_engagement_event_type' => $this->comment->last_engagement_event_type,
            'engagement_priority_reason' => $this->comment->engagement_priority_reason,
            'hot_lead' => filled($this->comment->hot_lead_at),
            'created_at' => $this->event->created_at?->toISOString(),
        ];
    }
}
