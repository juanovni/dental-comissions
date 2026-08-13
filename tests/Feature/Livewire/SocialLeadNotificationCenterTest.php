<?php

namespace Tests\Feature\Livewire;

use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Filament\Pages\SocialInbox;
use App\Livewire\SocialLeadNotificationCenter;
use App\Models\Clinic;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialIdentity;
use App\Models\SocialLeadAlert;
use App\Models\SocialPost;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SocialLeadNotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_center_lists_and_resolves_open_alert(): void
    {
        $clinic = $this->clinic();
        $comment = $this->socialComment($clinic);
        $alert = SocialLeadAlert::create([
            'clinic_id' => $clinic->id,
            'social_comment_id' => $comment->id,
            'alert_type' => 'hot_lead_created',
            'severity' => 'danger',
            'title' => 'Lead caliente',
            'message' => 'Requiere atencion inmediata.',
        ]);

        $user = User::factory()->create();
        $clinic->users()->attach($user, ['role' => 'admin', 'is_default' => true, 'is_active' => true]);
        Filament::setCurrentPanel('clinic');
        Filament::setTenant($clinic, isQuiet: true);

        Livewire::actingAs($user)
            ->test(SocialLeadNotificationCenter::class)
            ->assertSee('Lead caliente')
            ->call('resolveAlert', $alert->id);

        $this->assertSame(
            SocialInbox::getUrl(['comment' => $comment->id], panel: 'clinic', tenant: $clinic),
            app(SocialLeadNotificationCenter::class)->leadUrl($alert),
        );
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

    public function test_notification_center_marks_urgent_on_closing_opportunity_payload(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(SocialLeadNotificationCenter::class)
            ->call('handleClosingOpportunityDetected', [
                'intent' => 'ready_to_book',
                'closing_opportunity_score' => 85,
            ])
            ->assertSet('urgentPulse', true);
    }

    private function socialComment(Clinic $clinic): SocialComment
    {
        $account = SocialAccount::create([
            'clinic_id' => $clinic->id,
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'Clinica Dental',
            'external_account_id' => 'ig_account_'.uniqid(),
            'is_active' => true,
        ]);

        $post = SocialPost::create([
            'clinic_id' => $clinic->id,
            'social_account_id' => $account->id,
            'platform' => SocialPlatform::Instagram,
            'external_post_id' => 'post_'.uniqid(),
            'caption' => 'Implantes dentales',
        ]);

        $identity = SocialIdentity::create([
            'clinic_id' => $clinic->id,
            'platform' => SocialPlatform::Instagram,
            'platform_user_id' => 'user_'.uniqid(),
            'username' => 'paciente_test',
            'display_name' => 'Paciente Test',
            'status' => SocialIdentityStatus::NewLead,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        return SocialComment::create([
            'clinic_id' => $clinic->id,
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

    private function clinic(): Clinic
    {
        return Clinic::create([
            'name' => 'Clinica Demo',
            'slug' => 'clinica-demo',
            'subdomain' => 'clinica-demo',
            'primary_domain' => 'clinica-demo.localhost',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }
}
