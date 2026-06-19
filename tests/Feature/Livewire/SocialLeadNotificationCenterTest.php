<?php

namespace Tests\Feature\Livewire;

use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Filament\Pages\SocialPipelineKanban;
use App\Livewire\SocialLeadNotificationCenter;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialIdentity;
use App\Models\SocialLeadAlert;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SocialLeadNotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_center_lists_and_resolves_open_alert(): void
    {
        $comment = $this->socialComment();
        $alert = SocialLeadAlert::create([
            'social_comment_id' => $comment->id,
            'alert_type' => 'hot_lead_created',
            'severity' => 'danger',
            'title' => 'Lead caliente',
            'message' => 'Requiere atencion inmediata.',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(SocialLeadNotificationCenter::class)
            ->assertSee('Lead caliente')
            ->assertSee(SocialPipelineKanban::getUrl(['lead' => $comment->id]))
            ->call('resolveAlert', $alert->id);

        $this->assertNotNull($alert->refresh()->resolved_at);
    }

    public function test_notification_center_marks_urgent_on_hot_realtime_payload(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(SocialLeadNotificationCenter::class)
            ->call('handleLeadActivityDetected', [
                'interest_score' => 40,
                'recent_engagement_score' => 80,
            ])
            ->assertSet('urgentPulse', true);
    }

    private function socialComment(): SocialComment
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
            'interest_score' => 90,
            'recent_engagement_score' => 80,
            'hot_lead_at' => now(),
        ]);
    }
}
