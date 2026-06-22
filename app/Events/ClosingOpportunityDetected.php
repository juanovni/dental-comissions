<?php

namespace App\Events;

use App\Models\SocialComment;
use App\Models\SocialLeadAlert;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClosingOpportunityDetected implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SocialComment $comment,
        public array $agentResponse,
        public ?SocialLeadAlert $alert = null,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('admin-notifications');
    }

    public function broadcastAs(): string
    {
        return 'ClosingOpportunityDetected';
    }

    public function broadcastWith(): array
    {
        $this->comment->loadMissing(['suggestedProcedure', 'socialPost']);

        return [
            'lead_id' => $this->comment->id,
            'alert_id' => $this->alert?->id,
            'tracking_token' => $this->comment->tracking_token,
            'lead_name' => $this->comment->author_name ?: $this->comment->author_username,
            'procedure' => $this->comment->suggestedProcedure?->name,
            'intent' => (string) ($this->agentResponse['intent'] ?? 'unknown'),
            'closing_opportunity_score' => (int) ($this->agentResponse['closing_opportunity_score'] ?? 0),
            'handoff_reason' => (string) ($this->agentResponse['handoff_reason'] ?? ''),
            'clinical_safety_flag' => (bool) ($this->agentResponse['clinical_safety_flag'] ?? false),
            'created_at' => now()->toISOString(),
        ];
    }
}
