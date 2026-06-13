<?php

namespace Tests\Feature\Http;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialSmartLinkControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_smart_link_landing_loads_by_tracking_token(): void
    {
        $comment = $this->socialComment('DNT-LAND1');

        $this->get(route('social-smart-link.show', ['trackingToken' => $comment->tracking_token]))
            ->assertOk()
            ->assertSee('DNT-LAND1')
            ->assertSee('Continuar por WhatsApp', false);
    }

    public function test_smart_link_view_event_is_recorded_and_scores_lead(): void
    {
        $comment = $this->socialComment('DNT-VIEW1');

        $this->withSession(['_token' => 'test-csrf-token'])->postJson(route('social-smart-link.track', ['trackingToken' => $comment->tracking_token]), [
            'event_type' => 'view',
            'session_id' => 'session-1',
            'duration_seconds' => 0,
        ], ['X-CSRF-TOKEN' => 'test-csrf-token'])->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->assertDatabaseHas('social_link_events', [
            'social_comment_id' => $comment->id,
            'event_type' => 'view',
            'session_id' => 'session-1',
        ]);
        $this->assertDatabaseHas('social_comments', [
            'id' => $comment->id,
            'interest_score' => 15,
        ]);
    }

    public function test_duration_threshold_event_scores_only_once(): void
    {
        $comment = $this->socialComment('DNT-DUR01');

        $payload = [
            'event_type' => 'duration_threshold',
            'session_id' => 'session-duration',
            'duration_seconds' => 75,
        ];

        $this->withSession(['_token' => 'test-csrf-token'])->postJson(route('social-smart-link.track', ['trackingToken' => $comment->tracking_token]), $payload, ['X-CSRF-TOKEN' => 'test-csrf-token'])->assertOk();
        $this->withSession(['_token' => 'test-csrf-token'])->postJson(route('social-smart-link.track', ['trackingToken' => $comment->tracking_token]), $payload, ['X-CSRF-TOKEN' => 'test-csrf-token'])->assertOk();

        $this->assertDatabaseHas('social_comments', [
            'id' => $comment->id,
            'interest_score' => 20,
        ]);
        $this->assertDatabaseHas('social_comment_actions', [
            'social_comment_id' => $comment->id,
            'action' => SocialCommentActionType::LeadScoreUpdated->value,
            'notes' => 'Paciente esta muy interesado en los resultados visuales.',
        ]);
    }

    private function socialComment(string $token): SocialComment
    {
        $account = SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'Clinica Dental',
            'external_account_id' => 'ig_account_'.uniqid(),
            'is_active' => true,
        ]);

        $post = SocialPost::create([
            'social_account_id' => $account->id,
            'platform' => SocialPlatform::Instagram,
            'external_post_id' => 'post_'.uniqid(),
            'caption' => 'Implantes dentales',
        ]);

        $identity = SocialIdentity::create([
            'platform' => SocialPlatform::Instagram,
            'platform_user_id' => 'user_'.uniqid(),
            'username' => 'paciente_test',
            'display_name' => 'Paciente Test',
            'status' => SocialIdentityStatus::NewLead,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        return SocialComment::create([
            'social_account_id' => $account->id,
            'social_identity_id' => $identity->id,
            'social_post_id' => $post->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'comment_'.uniqid(),
            'author_name' => 'Paciente Test',
            'author_username' => 'paciente_test',
            'author_external_id' => $identity->platform_user_id,
            'comment_text' => 'Quiero informacion',
            'tracking_token' => $token,
        ]);
    }
}
