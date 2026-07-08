<?php

namespace Tests\Feature\Filament;

use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Filament\Pages\SocialHotLeads;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SocialHotLeadsTest extends TestCase
{
    use RefreshDatabase;

    public function test_hot_leads_page_lists_and_contacts_lead(): void
    {
        $comment = $this->socialComment();

        Livewire::actingAs(User::factory()->create())
            ->test(SocialHotLeads::class)
            ->assertSee($comment->comment_text)
            ->call('markContacted', $comment->id);

        $this->assertNotNull($comment->refresh()->contacted_at);
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
            'comment_text' => 'Quiero agenda para implantes',
            'interest_score' => 90,
            'hot_lead_at' => now(),
        ]);
    }
}
