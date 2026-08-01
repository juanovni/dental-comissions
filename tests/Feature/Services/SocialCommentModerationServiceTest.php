<?php

namespace Tests\Feature\Services;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialCommentClassification;
use App\Enums\SocialCommentStatus;
use App\Enums\SocialPlatform;
use App\Enums\SocialReputationRisk;
use App\Enums\SocialResponseChannel;
use App\Enums\SocialSuggestedAction;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialPost;
use App\Services\AiJsonService;
use App\Services\SocialCommentClassificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SocialCommentModerationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_offensive_comment_is_hidden_on_meta_without_hiding_it_in_odoncrm(): void
    {
        config(['services.ai.provider' => 'local']);
        Http::fake([
            'graph.facebook.com/*' => Http::response(['success' => true], 200),
        ]);

        $comment = $this->socialComment([
            'comment_text' => 'Son una basura',
        ]);

        app(SocialCommentClassificationService::class)->classify($comment);

        $comment->refresh();

        $this->assertSame(SocialCommentClassification::Offensive, $comment->classification);
        $this->assertSame(SocialReputationRisk::High, $comment->reputation_risk);
        $this->assertSame(SocialCommentStatus::ReviewRequired, $comment->status);
        $this->assertSame(SocialSuggestedAction::Hide, $comment->suggested_action);
        $this->assertTrue($comment->requires_human_review);
        $this->assertFalse($comment->is_hidden);
        $this->assertNotNull($comment->platform_hidden_at);
        $this->assertSame(['success' => true], $comment->platform_hide_response);
        $this->assertNull($comment->platform_hide_error);
        $this->assertNull($comment->auto_replied_at);

        $this->assertDatabaseHas('social_comment_actions', [
            'social_comment_id' => $comment->id,
            'action' => SocialCommentActionType::Hide->value,
        ]);

        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/comment_')
            && $request['hide'] === true);
    }

    public function test_meta_hide_failure_keeps_comment_visible_for_crisis_follow_up(): void
    {
        config(['services.ai.provider' => 'local']);
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'Missing permission']], 403),
        ]);

        $comment = $this->socialComment([
            'comment_text' => 'Son unos idiotas',
        ]);

        app(SocialCommentClassificationService::class)->classify($comment);

        $comment->refresh();

        $this->assertSame(SocialCommentClassification::Offensive, $comment->classification);
        $this->assertSame(SocialCommentStatus::ReviewRequired, $comment->status);
        $this->assertFalse($comment->is_hidden);
        $this->assertNull($comment->platform_hidden_at);
        $this->assertNotNull($comment->platform_hide_error);
        $this->assertSame(SocialSuggestedAction::Hide, $comment->suggested_action);
    }

    public function test_malicious_phrase_overrides_soft_ai_classification_and_hides_on_meta(): void
    {
        $this->app->bind(AiJsonService::class, fn () => new class extends AiJsonService
        {
            public function generate(string $systemPrompt, string $userPrompt): string
            {
                return json_encode([
                    'classification' => SocialCommentClassification::NegativeOpinion->value,
                    'sentiment' => 'negative',
                    'priority' => 'medium',
                    'reputation_risk' => 'medium',
                    'suggested_action' => 'escalate',
                    'response_channel' => 'both',
                    'suggested_reply' => 'Hola, lamentamos tu experiencia.',
                    'requires_human_review' => true,
                    'reason' => 'La IA lo considero opinion negativa.',
                    'suggested_procedure_code' => null,
                ]);
            }
        });
        Http::fake([
            'graph.facebook.com/*' => Http::response(['success' => true], 200),
        ]);

        $comment = $this->socialComment([
            'comment_text' => 'Todo es una estada son los peores',
        ]);

        app(SocialCommentClassificationService::class)->classify($comment);

        $comment->refresh();

        $this->assertSame(SocialCommentClassification::Offensive, $comment->classification);
        $this->assertSame(SocialReputationRisk::High, $comment->reputation_risk);
        $this->assertSame(SocialSuggestedAction::Hide, $comment->suggested_action);
        $this->assertSame(SocialResponseChannel::NoResponse, $comment->response_channel);
        $this->assertNotNull($comment->platform_hidden_at);
        $this->assertFalse($comment->is_hidden);
    }

    private function socialComment(array $overrides = []): SocialComment
    {
        $account = SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'Clinica Dental',
            'external_account_id' => 'ig_account_1',
            'instagram_business_account_id' => 'ig_account_1',
            'access_token' => 'meta-token',
            'is_active' => true,
        ]);

        $post = SocialPost::create([
            'social_account_id' => $account->id,
            'platform' => SocialPlatform::Instagram,
            'external_post_id' => 'post_1',
            'caption' => 'Atencion dental',
        ]);

        return SocialComment::create(array_merge([
            'social_account_id' => $account->id,
            'social_post_id' => $post->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'comment_'.uniqid(),
            'author_name' => 'Usuario Molesto',
            'author_username' => 'usuario_molesto',
            'author_external_id' => 'user_1',
            'comment_text' => 'Comentario ofensivo',
            'status' => SocialCommentStatus::New,
            'is_hidden' => false,
        ], $overrides));
    }
}
