<?php

namespace Tests\Feature\Services;

use App\Enums\SocialCommentClassification;
use App\Enums\SocialCommentStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\MetaSocialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_process_webhook_payload_stores_facebook_comment(): void
    {
        SocialAccount::create([
            'platform' => SocialPlatform::Facebook,
            'account_name' => 'Clinica Dental',
            'external_account_id' => 'page_1',
            'page_id' => 'page_1',
            'is_active' => true,
        ]);

        $summary = app(MetaSocialService::class)->processWebhookPayload([
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'page_1',
                    'changes' => [
                        [
                            'field' => 'feed',
                            'value' => [
                                'item' => 'comment',
                                'verb' => 'add',
                                'post_id' => 'post_1',
                                'comment_id' => 'comment_1',
                                'message' => 'Quiero una cita',
                                'sender_id' => 'facebook_user_1',
                                'sender_name' => 'Carlos Cliente',
                                'created_time' => now()->timestamp,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame(1, $summary['comments']);
        $this->assertDatabaseHas('social_posts', [
            'platform' => SocialPlatform::Facebook->value,
            'external_post_id' => 'post_1',
        ]);
        $this->assertDatabaseHas('social_comments', [
            'platform' => SocialPlatform::Facebook->value,
            'external_comment_id' => 'comment_1',
            'comment_text' => 'Quiero una cita',
            'author_external_id' => 'facebook_user_1',
            'classification' => SocialCommentClassification::SalesLead->value,
            'status' => SocialCommentStatus::Classified->value,
        ]);
    }

    public function test_process_webhook_payload_stores_instagram_comment(): void
    {
        SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'Clinica Dental IG',
            'external_account_id' => 'ig_1',
            'instagram_business_account_id' => 'ig_1',
            'is_active' => true,
        ]);

        $summary = app(MetaSocialService::class)->processWebhookPayload([
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => 'ig_1',
                    'changes' => [
                        [
                            'field' => 'comments',
                            'value' => [
                                'id' => 'ig_comment_1',
                                'text' => 'Info por favor',
                                'media' => ['id' => 'ig_media_1'],
                                'from' => [
                                    'id' => 'ig_user_1',
                                    'username' => 'cliente_ig',
                                ],
                                'timestamp' => now()->toIso8601String(),
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame(1, $summary['comments']);
        $this->assertDatabaseHas('social_posts', [
            'platform' => SocialPlatform::Instagram->value,
            'external_post_id' => 'ig_media_1',
        ]);
        $this->assertDatabaseHas('social_comments', [
            'platform' => SocialPlatform::Instagram->value,
            'external_comment_id' => 'ig_comment_1',
            'comment_text' => 'Info por favor',
            'author_username' => 'cliente_ig',
            'author_external_id' => 'ig_user_1',
            'classification' => SocialCommentClassification::SalesLead->value,
            'status' => SocialCommentStatus::Classified->value,
        ]);
    }

    public function test_sync_account_classifies_synced_facebook_comments(): void
    {
        config([
            'services.meta.access_token' => 'test-token',
            'services.meta.api_url' => 'https://graph.facebook.com/v25.0',
            'services.gemini.api_key' => null,
        ]);

        $account = SocialAccount::create([
            'platform' => SocialPlatform::Facebook,
            'account_name' => 'Clinica Dental',
            'external_account_id' => 'page_1',
            'page_id' => 'page_1',
            'access_token' => 'page-token',
            'is_active' => true,
        ]);

        Http::fake([
            'https://graph.facebook.com/v25.0/page_1/posts*' => Http::response([
                'data' => [
                    [
                        'id' => 'post_sync_1',
                        'message' => 'Promo limpieza dental',
                        'created_time' => now()->toIso8601String(),
                    ],
                ],
            ]),
            'https://graph.facebook.com/v25.0/post_sync_1/comments*' => Http::response([
                'data' => [
                    [
                        'id' => 'comment_sync_1',
                        'message' => 'Precio de una limpieza dental',
                        'from' => ['id' => 'fb_user_1', 'name' => 'Cliente Facebook'],
                        'created_time' => now()->toIso8601String(),
                    ],
                ],
            ]),
        ]);

        $summary = app(MetaSocialService::class)->syncAccount($account);

        $this->assertSame(['posts' => 1, 'comments' => 1], $summary);
        $this->assertDatabaseHas('social_comments', [
            'platform' => SocialPlatform::Facebook->value,
            'external_comment_id' => 'comment_sync_1',
            'classification' => SocialCommentClassification::SalesLead->value,
            'status' => SocialCommentStatus::Classified->value,
        ]);
    }
}
