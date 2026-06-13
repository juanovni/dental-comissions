<?php

namespace Tests\Feature\Services;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use App\Services\SocialLeadScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialLeadScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_score_marks_hot_lead_when_threshold_is_reached(): void
    {
        $comment = $this->socialComment(['interest_score' => 70]);

        app(SocialLeadScoringService::class)->addScore($comment, 10, 'Prueba de lead caliente.');

        $comment->refresh();

        $this->assertSame(80, $comment->interest_score);
        $this->assertNotNull($comment->hot_lead_at);
        $this->assertDatabaseHas('social_comment_actions', [
            'social_comment_id' => $comment->id,
            'action' => SocialCommentActionType::LeadScoreUpdated->value,
        ]);
    }

    public function test_smart_link_revisit_after_idle_time_marks_reheated_lead(): void
    {
        $comment = $this->socialComment([
            'interest_score' => 50,
            'last_smart_link_visited_at' => now()->subDays(4),
        ]);

        app(SocialLeadScoringService::class)->scoreSmartLinkVisit($comment);

        $comment->refresh();

        $this->assertSame(70, $comment->interest_score);
        $this->assertNotNull($comment->reheated_at);
        $this->assertDatabaseHas('social_comment_actions', [
            'social_comment_id' => $comment->id,
            'action' => SocialCommentActionType::LeadReheated->value,
        ]);
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
            'comment_text' => 'Quiero informacion',
        ], $overrides));
    }
}
