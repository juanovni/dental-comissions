<?php

namespace App\Events;

use App\Models\SocialComment;
use App\Models\SocialLinkEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadActivityDetected implements ShouldBroadcastNow
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
            'hot_lead' => filled($this->comment->hot_lead_at),
            'created_at' => $this->event->created_at?->toISOString(),
        ];
    }
}
