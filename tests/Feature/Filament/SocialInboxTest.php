<?php

namespace Tests\Feature\Filament;

use App\Enums\SocialCommentClassification;
use App\Enums\SocialCommentStatus;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Filament\Pages\SocialInbox;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SocialInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_inbox_hides_archived_leads_from_main_filter(): void
    {
        $visible = $this->socialComment([
            'classification' => SocialCommentClassification::SalesLead,
            'conversion_status' => SocialConversionStatus::TokenGenerated,
            'tracking_token' => 'DNT-VISIB',
        ]);

        $archived = $this->socialComment([
            'classification' => SocialCommentClassification::SalesLead,
            'conversion_status' => SocialConversionStatus::PendingPatientCreation,
            'tracking_token' => 'DNT-ARCH1',
            'is_hidden' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(SocialInbox::class)
            ->set('filter', 'leads')
            ->assertSee($visible->comment_text)
            ->assertDontSee($archived->comment_text)
            ->set('filter', 'archived')
            ->assertSee($archived->comment_text);
    }

    public function test_mark_reviewed_archives_comment(): void
    {
        $comment = $this->socialComment([
            'classification' => SocialCommentClassification::SalesLead,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(SocialInbox::class)
            ->call('markReviewed', $comment->id);

        $this->assertDatabaseHas('social_comments', [
            'id' => $comment->id,
            'status' => SocialCommentStatus::Classified->value,
            'is_hidden' => true,
        ]);
    }

    public function test_navigation_badge_counts_pending_social_comments(): void
    {
        $this->socialComment(['status' => SocialCommentStatus::New]);
        $this->socialComment(['status' => SocialCommentStatus::ReviewRequired]);
        $this->socialComment(['status' => SocialCommentStatus::Classified]);
        $this->socialComment(['status' => SocialCommentStatus::Ignored]);
        $this->socialComment(['status' => SocialCommentStatus::MarkedAsSpam]);
        $this->socialComment([
            'status' => SocialCommentStatus::New,
            'is_hidden' => true,
        ]);
        $this->socialComment([
            'status' => SocialCommentStatus::Classified,
            'conversion_status' => SocialConversionStatus::PendingPatientCreation,
        ]);

        $this->assertSame('3', SocialInbox::getNavigationBadge());
        $this->assertSame('primary', SocialInbox::getNavigationBadgeColor());
    }

    private function socialComment(array $overrides = []): SocialComment
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

        return SocialComment::create(array_merge([
            'social_account_id' => $account->id,
            'social_identity_id' => $identity->id,
            'social_post_id' => $post->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'comment_'.uniqid(),
            'author_name' => 'Paciente Test',
            'author_username' => 'paciente_test',
            'author_external_id' => $identity->platform_user_id,
            'comment_text' => 'Quiero informacion '.uniqid(),
            'classification' => SocialCommentClassification::SalesLead,
            'conversion_status' => SocialConversionStatus::None,
            'status' => SocialCommentStatus::New,
            'is_hidden' => false,
        ], $overrides));
    }
}
