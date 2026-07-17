<?php

namespace Tests\Feature\Services;

use App\Enums\SocialCommentClassification;
use App\Enums\SocialCommentStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Jobs\SendSocialCommentAutoReply;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialCrmSetting;
use App\Models\SocialPost;
use App\Services\MetaSocialService;
use App\Services\SocialCrmSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MetaSocialServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.ai.provider' => 'gemini',
            'services.gemini.api_key' => null,
        ]);
    }

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

    public function test_process_webhook_payload_ignores_comments_created_before_connected_at(): void
    {
        SocialAccount::create([
            'platform' => SocialPlatform::Facebook,
            'account_name' => 'Clinica Dental',
            'external_account_id' => 'page_1',
            'page_id' => 'page_1',
            'is_active' => true,
            'sync_settings' => ['connected_at' => now()->toIso8601String()],
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
                                'post_id' => 'old_post_1',
                                'comment_id' => 'old_comment_1',
                                'message' => 'Comentario anterior a la integracion',
                                'sender_id' => 'facebook_user_1',
                                'sender_name' => 'Carlos Cliente',
                                'created_time' => now()->subMinute()->timestamp,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame(0, $summary['comments']);
        $this->assertSame(1, $summary['ignored']);
        $this->assertDatabaseMissing('social_comments', [
            'platform' => SocialPlatform::Facebook->value,
            'external_comment_id' => 'old_comment_1',
        ]);
    }

    public function test_process_webhook_payload_dispatches_auto_reply_job_for_sales_lead_when_enabled(): void
    {
        $this->setting('social_auto_reply_enabled', true, 'boolean');
        Queue::fake();

        SocialAccount::create([
            'platform' => SocialPlatform::Facebook,
            'account_name' => 'Clinica Dental',
            'external_account_id' => 'page_1',
            'page_id' => 'page_1',
            'is_active' => true,
        ]);

        app(MetaSocialService::class)->processWebhookPayload([
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
                                'comment_id' => 'comment_auto_reply_1',
                                'message' => 'Info por favor',
                                'sender_id' => 'facebook_user_1',
                                'sender_name' => 'Carlos Cliente',
                                'created_time' => now()->timestamp,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $comment = SocialComment::where('external_comment_id', 'comment_auto_reply_1')->firstOrFail();

        Queue::assertPushed(
            SendSocialCommentAutoReply::class,
            fn (SendSocialCommentAutoReply $job): bool => $job->socialCommentId === $comment->id,
        );
    }

    public function test_process_webhook_payload_does_not_dispatch_auto_reply_job_when_disabled(): void
    {
        $this->setting('social_auto_reply_enabled', false, 'boolean');
        Queue::fake();

        SocialAccount::create([
            'platform' => SocialPlatform::Facebook,
            'account_name' => 'Clinica Dental',
            'external_account_id' => 'page_1',
            'page_id' => 'page_1',
            'is_active' => true,
        ]);

        app(MetaSocialService::class)->processWebhookPayload([
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
                                'comment_id' => 'comment_auto_reply_disabled_1',
                                'message' => 'Info por favor',
                                'sender_id' => 'facebook_user_1',
                                'sender_name' => 'Carlos Cliente',
                                'created_time' => now()->timestamp,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        Queue::assertNotPushed(SendSocialCommentAutoReply::class);
    }

    public function test_process_webhook_payload_does_not_dispatch_auto_reply_job_for_sensitive_comment(): void
    {
        $this->setting('social_auto_reply_enabled', true, 'boolean');
        Queue::fake();

        SocialAccount::create([
            'platform' => SocialPlatform::Facebook,
            'account_name' => 'Clinica Dental',
            'external_account_id' => 'page_1',
            'page_id' => 'page_1',
            'is_active' => true,
        ]);

        app(MetaSocialService::class)->processWebhookPayload([
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
                                'comment_id' => 'comment_auto_reply_sensitive_1',
                                'message' => 'Tengo dolor fuerte y sangrado',
                                'sender_id' => 'facebook_user_1',
                                'sender_name' => 'Carlos Cliente',
                                'created_time' => now()->timestamp,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        Queue::assertNotPushed(SendSocialCommentAutoReply::class);
    }

    public function test_process_webhook_payload_does_not_dispatch_auto_reply_job_for_account_outside_rollout_allowlist(): void
    {
        $this->setting('social_auto_reply_enabled', true, 'boolean');
        $this->setting('social_auto_reply_allowed_social_account_ids', [999999], 'array');
        Queue::fake();

        SocialAccount::create([
            'platform' => SocialPlatform::Facebook,
            'account_name' => 'Clinica Dental',
            'external_account_id' => 'page_1',
            'page_id' => 'page_1',
            'is_active' => true,
        ]);

        app(MetaSocialService::class)->processWebhookPayload([
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
                                'comment_id' => 'comment_auto_reply_blocked_account_1',
                                'message' => 'Info por favor',
                                'sender_id' => 'facebook_user_1',
                                'sender_name' => 'Carlos Cliente',
                                'created_time' => now()->timestamp,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        Queue::assertNotPushed(SendSocialCommentAutoReply::class);
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

    public function test_process_webhook_payload_ignores_own_instagram_comment(): void
    {
        SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'macbotdata',
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
                                'id' => 'ig_comment_own_1',
                                'text' => '@detanlinfodeunaec Hola! Gracias por escribirnos.',
                                'media' => ['id' => 'ig_media_1'],
                                'from' => [
                                    'id' => 'ig_1',
                                    'username' => 'macbotdata',
                                ],
                                'timestamp' => now()->toIso8601String(),
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame(0, $summary['comments']);
        $this->assertSame(1, $summary['ignored']);
        $this->assertDatabaseMissing('social_comments', [
            'external_comment_id' => 'ig_comment_own_1',
        ]);
    }

    public function test_process_webhook_payload_enriches_instagram_related_post(): void
    {
        config([
            'services.meta.access_token' => 'test-token',
            'services.meta.api_url' => 'https://graph.facebook.com/v25.0',
        ]);

        SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'Clinica Dental IG',
            'external_account_id' => 'ig_1',
            'instagram_business_account_id' => 'ig_1',
            'access_token' => 'page-token',
            'is_active' => true,
        ]);

        Http::fake([
            'https://graph.facebook.com/v25.0/ig_media_1*' => Http::response([
                'id' => 'ig_media_1',
                'caption' => 'Ortodoncia invisible para adultos. Consulta si eres candidato.',
                'media_url' => 'https://cdn.example.test/ig_media_1.jpg',
                'permalink' => 'https://instagram.com/p/abc123',
                'timestamp' => now()->toIso8601String(),
            ]),
        ]);

        app(MetaSocialService::class)->processWebhookPayload([
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => 'ig_1',
                    'changes' => [
                        [
                            'field' => 'comments',
                            'value' => [
                                'id' => 'ig_comment_1',
                                'text' => 'Me interesa esta promocion',
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

        $this->assertDatabaseHas('social_posts', [
            'platform' => SocialPlatform::Instagram->value,
            'external_post_id' => 'ig_media_1',
            'caption' => 'Ortodoncia invisible para adultos. Consulta si eres candidato.',
            'media_url' => 'https://cdn.example.test/ig_media_1.jpg',
            'permalink' => 'https://instagram.com/p/abc123',
        ]);
    }

    public function test_process_webhook_payload_enriches_facebook_related_post(): void
    {
        config([
            'services.meta.access_token' => 'test-token',
            'services.meta.api_url' => 'https://graph.facebook.com/v25.0',
        ]);

        SocialAccount::create([
            'platform' => SocialPlatform::Facebook,
            'account_name' => 'Clinica Dental',
            'external_account_id' => 'page_1',
            'page_id' => 'page_1',
            'access_token' => 'page-token',
            'is_active' => true,
        ]);

        Http::fake([
            'https://graph.facebook.com/v25.0/post_1*' => Http::response([
                'id' => 'post_1',
                'message' => 'Promocion especial de implantes dentales.',
                'full_picture' => 'https://cdn.example.test/post_1.jpg',
                'permalink_url' => 'https://facebook.com/post_1',
                'created_time' => now()->toIso8601String(),
            ]),
        ]);

        app(MetaSocialService::class)->processWebhookPayload([
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
                                'message' => 'Cuanto cuesta?',
                                'sender_id' => 'facebook_user_1',
                                'sender_name' => 'Carlos Cliente',
                                'created_time' => now()->timestamp,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseHas('social_posts', [
            'platform' => SocialPlatform::Facebook->value,
            'external_post_id' => 'post_1',
            'caption' => 'Promocion especial de implantes dentales.',
            'media_url' => 'https://cdn.example.test/post_1.jpg',
            'permalink' => 'https://facebook.com/post_1',
        ]);
    }

    public function test_sync_account_classifies_synced_facebook_comments(): void
    {
        config([
            'services.ai.provider' => 'gemini',
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
            'https://graph.facebook.com/v25.0/page_1/feed*' => Http::response([
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

    public function test_sync_account_dispatches_auto_reply_job_for_synced_sales_lead_when_enabled(): void
    {
        $this->setting('social_auto_reply_enabled', true, 'boolean');
        Queue::fake();

        config([
            'services.ai.provider' => 'gemini',
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
            'https://graph.facebook.com/v25.0/page_1/feed*' => Http::response([
                'data' => [
                    [
                        'id' => 'post_sync_auto_reply_1',
                        'message' => 'Promo limpieza dental',
                        'created_time' => now()->toIso8601String(),
                    ],
                ],
            ]),
            'https://graph.facebook.com/v25.0/post_sync_auto_reply_1/comments*' => Http::response([
                'data' => [
                    [
                        'id' => 'comment_sync_auto_reply_1',
                        'message' => 'Precio de una limpieza dental',
                        'from' => ['id' => 'fb_user_1', 'name' => 'Cliente Facebook'],
                        'created_time' => now()->toIso8601String(),
                    ],
                ],
            ]),
        ]);

        app(MetaSocialService::class)->syncAccount($account);

        $comment = SocialComment::where('external_comment_id', 'comment_sync_auto_reply_1')->firstOrFail();

        Queue::assertPushed(
            SendSocialCommentAutoReply::class,
            fn (SendSocialCommentAutoReply $job): bool => $job->socialCommentId === $comment->id,
        );
    }

    public function test_sync_account_captures_new_comments_on_old_facebook_posts(): void
    {
        config([
            'services.ai.provider' => 'gemini',
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
            'sync_settings' => ['connected_at' => now()->toIso8601String()],
        ]);

        Http::fake([
            'https://graph.facebook.com/v25.0/page_1/feed?fields=id%2Cmessage%2Cpermalink_url%2Cfull_picture%2Ccreated_time&since=*' => Http::response(['data' => []]),
            'https://graph.facebook.com/v25.0/page_1/feed*' => Http::response([
                'data' => [
                    [
                        'id' => 'old_post_1',
                        'message' => 'Blanqueamiento dental',
                        'created_time' => now()->subMonth()->toIso8601String(),
                    ],
                ],
            ]),
            'https://graph.facebook.com/v25.0/old_post_1/comments*' => Http::response([
                'data' => [
                    [
                        'id' => 'new_comment_on_old_post_1',
                        'message' => 'Me pueden dar informacion del blanqueamiento',
                        'from' => ['id' => 'fb_user_old_post_1', 'name' => 'Cliente Facebook'],
                        'created_time' => now()->addMinute()->toIso8601String(),
                    ],
                ],
            ]),
        ]);

        $summary = app(MetaSocialService::class)->syncAccount($account);

        $this->assertSame(['posts' => 1, 'comments' => 1], $summary);
        $this->assertDatabaseHas('social_posts', [
            'platform' => SocialPlatform::Facebook->value,
            'external_post_id' => 'old_post_1',
        ]);
        $this->assertDatabaseHas('social_comments', [
            'platform' => SocialPlatform::Facebook->value,
            'external_comment_id' => 'new_comment_on_old_post_1',
        ]);
    }

    public function test_sync_account_captures_new_comments_on_old_instagram_posts(): void
    {
        config([
            'services.ai.provider' => 'gemini',
            'services.meta.access_token' => 'test-token',
            'services.meta.api_url' => 'https://graph.facebook.com/v25.0',
            'services.gemini.api_key' => null,
        ]);

        $account = SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'clinica_dental',
            'external_account_id' => 'ig_1',
            'page_id' => 'page_1',
            'instagram_business_account_id' => 'ig_1',
            'access_token' => 'page-token',
            'is_active' => true,
            'sync_settings' => ['connected_at' => now()->toIso8601String()],
        ]);

        Http::fake([
            'https://graph.facebook.com/v25.0/ig_1/media?fields=id%2Ccaption%2Cmedia_url%2Cpermalink%2Ctimestamp&since=*' => Http::response(['data' => []]),
            'https://graph.facebook.com/v25.0/ig_1/media*' => Http::response([
                'data' => [
                    [
                        'id' => 'old_ig_media_1',
                        'caption' => 'Blanqueamiento dental',
                        'timestamp' => now()->subMonth()->toIso8601String(),
                    ],
                ],
            ]),
            'https://graph.facebook.com/v25.0/old_ig_media_1/comments*' => Http::response([
                'data' => [
                    [
                        'id' => 'new_comment_on_old_ig_media_1',
                        'text' => 'Me interesa el blanqueamiento',
                        'username' => 'cliente_ig',
                        'timestamp' => now()->addMinute()->toIso8601String(),
                    ],
                ],
            ]),
        ]);

        $summary = app(MetaSocialService::class)->syncAccount($account);

        $this->assertSame(['posts' => 1, 'comments' => 1], $summary);
        $this->assertDatabaseHas('social_posts', [
            'platform' => SocialPlatform::Instagram->value,
            'external_post_id' => 'old_ig_media_1',
        ]);
        $this->assertDatabaseHas('social_comments', [
            'platform' => SocialPlatform::Instagram->value,
            'external_comment_id' => 'new_comment_on_old_ig_media_1',
        ]);
    }

    public function test_sync_all_ignores_whatsapp_accounts(): void
    {
        config([
            'services.meta.access_token' => 'test-token',
            'services.meta.api_url' => 'https://graph.facebook.com/v25.0',
            'services.gemini.api_key' => null,
        ]);

        SocialAccount::create([
            'platform' => SocialPlatform::Whatsapp,
            'account_name' => 'WhatsApp Dental',
            'external_account_id' => 'whatsapp_1',
            'access_token' => 'invalid-whatsapp-token',
            'is_active' => true,
        ]);

        SocialAccount::create([
            'platform' => SocialPlatform::Facebook,
            'account_name' => 'Clinica Dental',
            'external_account_id' => 'page_1',
            'page_id' => 'page_1',
            'access_token' => 'page-token',
            'is_active' => true,
        ]);

        Http::fake([
            'https://graph.facebook.com/v25.0/page_1/feed*' => Http::response(['data' => []]),
        ]);

        $summary = app(MetaSocialService::class)->syncAll();

        $this->assertSame(1, $summary['accounts']);
        $this->assertSame(0, $summary['errors']);
        Http::assertNotSent(fn ($request): bool => $request->url() === 'https://graph.facebook.com/v25.0/posts');
    }

    public function test_reply_to_comment_posts_facebook_comment_reply(): void
    {
        config(['services.meta.api_url' => 'https://graph.facebook.com/v25.0']);

        $comment = $this->socialComment(SocialPlatform::Facebook, 'fb_comment_1');

        Http::fake([
            'https://graph.facebook.com/v25.0/fb_comment_1/comments' => Http::response([
                'id' => 'fb_reply_1',
            ]),
        ]);

        $response = app(MetaSocialService::class)->replyToComment($comment, 'Hola, te dejamos información: https://example.test/v/DNT-123');

        $this->assertSame(['id' => 'fb_reply_1'], $response);
        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && $request->url() === 'https://graph.facebook.com/v25.0/fb_comment_1/comments'
            && $request['message'] === 'Hola, te dejamos información: https://example.test/v/DNT-123'
            && $request->hasHeader('Authorization', 'Bearer page-token'));
    }

    public function test_reply_to_comment_posts_instagram_comment_reply(): void
    {
        config(['services.meta.api_url' => 'https://graph.facebook.com/v25.0']);

        $comment = $this->socialComment(SocialPlatform::Instagram, 'ig_comment_1');

        Http::fake([
            'https://graph.facebook.com/v25.0/ig_comment_1/replies' => Http::response([
                'id' => 'ig_reply_1',
            ]),
        ]);

        $response = app(MetaSocialService::class)->replyToComment($comment, '👋 Te saluda Clínica Dental');

        $this->assertSame(['id' => 'ig_reply_1'], $response);
        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && $request->url() === 'https://graph.facebook.com/v25.0/ig_comment_1/replies'
            && $request['message'] === '👋 Te saluda Clínica Dental'
            && $request->hasHeader('Authorization', 'Bearer page-token'));
    }

    public function test_reply_to_comment_throws_when_comment_has_no_external_id(): void
    {
        $comment = new SocialComment([
            'platform' => SocialPlatform::Instagram,
            'comment_text' => 'Info por favor',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('external_comment_id');

        app(MetaSocialService::class)->replyToComment($comment, 'Mensaje');
    }

    public function test_reply_to_comment_throws_meta_http_errors(): void
    {
        config(['services.meta.api_url' => 'https://graph.facebook.com/v25.0']);

        $comment = $this->socialComment(SocialPlatform::Facebook, 'fb_comment_1');

        Http::fake([
            'https://graph.facebook.com/v25.0/fb_comment_1/comments' => Http::response([
                'error' => [
                    'message' => 'Invalid OAuth access token.',
                    'type' => 'OAuthException',
                    'code' => 190,
                ],
            ], 400),
        ]);

        $this->expectException(\Illuminate\Http\Client\RequestException::class);

        app(MetaSocialService::class)->replyToComment($comment, 'Mensaje');
    }

    private function socialComment(SocialPlatform $platform, ?string $externalCommentId): SocialComment
    {
        $account = SocialAccount::create([
            'platform' => $platform,
            'account_name' => 'Clinica Dental',
            'external_account_id' => $platform === SocialPlatform::Instagram ? 'ig_1' : 'page_1',
            'page_id' => $platform === SocialPlatform::Facebook ? 'page_1' : null,
            'instagram_business_account_id' => $platform === SocialPlatform::Instagram ? 'ig_1' : null,
            'access_token' => 'page-token',
            'is_active' => true,
        ]);

        $post = SocialPost::create([
            'social_account_id' => $account->id,
            'platform' => $platform,
            'external_post_id' => 'post_'.uniqid(),
            'caption' => 'Post de prueba',
        ]);

        return SocialComment::create([
            'social_account_id' => $account->id,
            'social_post_id' => $post->id,
            'platform' => $platform,
            'external_comment_id' => $externalCommentId,
            'author_name' => 'Paciente Test',
            'author_username' => 'paciente_test',
            'author_external_id' => 'user_'.uniqid(),
            'comment_text' => 'Info por favor',
        ]);
    }

    private function setting(string $key, mixed $value, string $type = 'string'): void
    {
        SocialCrmSetting::updateOrCreate(
            ['key' => $key],
            [
                'setting_group' => 'auto_reply',
                'label' => $key,
                'value_type' => $type,
                'value' => $value,
                'is_active' => true,
            ],
        );

        app(SocialCrmSettingsService::class)->clearCache();
    }
}
