<?php

namespace Tests\Feature\Services;

use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\MetaSocialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaSocialServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_comment_creates_social_identity_for_author(): void
    {
        $account = SocialAccount::create([
            'platform' => SocialPlatform::Facebook,
            'account_name' => 'Clinica Dental',
            'external_account_id' => 'page_1',
            'page_id' => 'page_1',
            'is_active' => true,
        ]);

        $post = SocialPost::create([
            'social_account_id' => $account->id,
            'platform' => SocialPlatform::Facebook,
            'external_post_id' => 'post_1',
            'caption' => 'Ortodoncia',
        ]);

        $comment = app(MetaSocialService::class)->storeComment($account, $post, [
            'id' => 'comment_1',
            'message' => 'Cuanto cuesta?',
            'from' => [
                'id' => 'facebook_user_1',
                'name' => 'Carlos Cliente',
            ],
            'created_time' => now()->toIso8601String(),
        ]);

        $this->assertNotNull($comment->social_identity_id);
        $this->assertDatabaseHas('social_identities', [
            'id' => $comment->social_identity_id,
            'platform' => SocialPlatform::Facebook->value,
            'platform_user_id' => 'facebook_user_1',
            'display_name' => 'Carlos Cliente',
            'status' => SocialIdentityStatus::NewLead->value,
        ]);
    }
}
