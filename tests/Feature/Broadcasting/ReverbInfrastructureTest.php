<?php

namespace Tests\Feature\Broadcasting;

use App\Events\LeadActivityDetected;
use App\Models\SocialComment;
use App\Models\SocialLinkEvent;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReverbInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_notifications_channel_rejects_guests(): void
    {
        config(['broadcasting.default' => 'reverb']);

        $this->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-admin-notifications',
        ])->assertForbidden();
    }

    public function test_admin_notifications_channel_accepts_authenticated_users(): void
    {
        config(['broadcasting.default' => 'reverb']);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-admin-notifications',
            ])
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }

    public function test_user_notification_channel_only_accepts_the_same_authenticated_user(): void
    {
        config(['broadcasting.default' => 'reverb']);

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => "private-App.Models.User.{$user->id}",
            ])
            ->assertOk()
            ->assertJsonStructure(['auth']);

        $this->actingAs($user)
            ->postJson('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => "private-App.Models.User.{$otherUser->id}",
            ])
            ->assertForbidden();
    }

    public function test_lead_activity_detected_uses_private_admin_channel_and_minimal_payload(): void
    {
        $comment = new SocialComment([
            'tracking_token' => 'DNT-TEST',
            'interest_score' => 85,
            'hot_lead_at' => now(),
        ]);
        $comment->id = 123;

        $linkEvent = new SocialLinkEvent([
            'event_type' => 'whatsapp_click',
        ]);
        $linkEvent->created_at = now();

        $event = new LeadActivityDetected($comment, $linkEvent);

        $this->assertEquals(new PrivateChannel('admin-notifications'), $event->broadcastOn());
        $this->assertSame('LeadActivityDetected', $event->broadcastAs());
        $this->assertSame([
            'lead_id' => 123,
            'tracking_token' => 'DNT-TEST',
            'event_type' => 'whatsapp_click',
            'interest_score' => 85,
            'recent_engagement_score' => 0,
            'last_engagement_at' => null,
            'engagement_event_count_1h' => 0,
            'engagement_event_count_24h' => 0,
            'last_engagement_event_type' => null,
            'engagement_priority_reason' => null,
            'hot_lead' => true,
            'created_at' => $linkEvent->created_at?->toISOString(),
        ], $event->broadcastWith());
    }
}
