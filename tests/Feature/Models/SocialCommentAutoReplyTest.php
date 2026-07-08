<?php

namespace Tests\Feature\Models;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialCommentClassification;
use App\Enums\SocialCommentStatus;
use App\Enums\SocialPlatform;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialCommentAction;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialCommentAutoReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_reply_fields_are_nullable_by_default(): void
    {
        $comment = $this->socialComment();

        $this->assertNull($comment->auto_replied_at);
        $this->assertNull($comment->auto_reply_external_id);
        $this->assertNull($comment->auto_reply_error);
        $this->assertNull($comment->auto_reply_message);
        $this->assertSame(0, $comment->fresh()->auto_reply_attempts);
    }

    public function test_auto_reply_fields_can_be_set(): void
    {
        $comment = $this->socialComment();

        $comment->update([
            'auto_replied_at' => now(),
            'auto_reply_external_id' => '123456789',
            'auto_reply_message' => '👋 Te saluda Clínica Dental\n\nHola, con gusto te ayudamos.',
        ]);

        $comment->refresh();

        $this->assertNotNull($comment->auto_replied_at);
        $this->assertSame('123456789', $comment->auto_reply_external_id);
        $this->assertStringContainsString('Te saluda', $comment->auto_reply_message);
    }

    public function test_auto_reply_error_can_be_set(): void
    {
        $comment = $this->socialComment();

        $comment->update([
            'auto_reply_error' => 'OAuthException: Invalid access token',
            'auto_reply_attempts' => 2,
        ]);

        $comment->refresh();

        $this->assertSame('OAuthException: Invalid access token', $comment->auto_reply_error);
        $this->assertSame(2, $comment->auto_reply_attempts);
    }

    public function test_auto_reply_attempts_is_cast_to_integer(): void
    {
        $comment = $this->socialComment();

        $comment->update(['auto_reply_attempts' => 3]);
        $comment->refresh();

        $this->assertIsInt($comment->auto_reply_attempts);
        $this->assertSame(3, $comment->auto_reply_attempts);
    }

    public function test_auto_replied_at_is_cast_to_datetime(): void
    {
        $comment = $this->socialComment();

        $comment->update(['auto_replied_at' => now()]);
        $comment->refresh();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $comment->auto_replied_at);
    }

    public function test_auto_reply_generated_action_type_has_label(): void
    {
        $action = SocialCommentActionType::AutoReplyGenerated;

        $this->assertSame('auto_reply_generated', $action->value);
        $this->assertSame('Respuesta automática generada', $action->label());
    }

    public function test_auto_reply_sent_action_type_has_label(): void
    {
        $action = SocialCommentActionType::AutoReplySent;

        $this->assertSame('auto_reply_sent', $action->value);
        $this->assertSame('Respuesta automática enviada', $action->label());
    }

    public function test_auto_reply_failed_action_type_has_label(): void
    {
        $action = SocialCommentActionType::AutoReplyFailed;

        $this->assertSame('auto_reply_failed', $action->value);
        $this->assertSame('Respuesta automática fallida', $action->label());
    }

    public function test_auto_reply_skipped_action_type_has_label(): void
    {
        $action = SocialCommentActionType::AutoReplySkipped;

        $this->assertSame('auto_reply_skipped', $action->value);
        $this->assertSame('Respuesta automática omitida', $action->label());
    }

    public function test_auto_reply_action_can_be_created_for_comment(): void
    {
        $comment = $this->socialComment();

        $action = SocialCommentAction::create([
            'social_comment_id' => $comment->id,
            'action' => SocialCommentActionType::AutoReplySent,
            'notes' => 'Auto-reply publicado en Instagram',
            'external_response_data' => [
                'external_id' => '987654321',
                'platform' => 'instagram',
            ],
        ]);

        $this->assertDatabaseHas('social_comment_actions', [
            'social_comment_id' => $comment->id,
            'action' => SocialCommentActionType::AutoReplySent->value,
        ]);

        $this->assertSame($comment->id, $action->social_comment_id);
    }

    public function test_auto_reply_failed_action_can_be_created(): void
    {
        $comment = $this->socialComment();

        SocialCommentAction::create([
            'social_comment_id' => $comment->id,
            'action' => SocialCommentActionType::AutoReplyFailed,
            'notes' => 'Error de Meta API',
            'external_response_data' => [
                'error' => 'OAuthException: Invalid token',
                'code' => 190,
            ],
        ]);

        $this->assertDatabaseHas('social_comment_actions', [
            'social_comment_id' => $comment->id,
            'action' => SocialCommentActionType::AutoReplyFailed->value,
        ]);
    }

    public function test_auto_reply_skipped_action_can_be_created(): void
    {
        $comment = $this->socialComment();

        SocialCommentAction::create([
            'social_comment_id' => $comment->id,
            'action' => SocialCommentActionType::AutoReplySkipped,
            'notes' => 'Comentario requiere revisión humana',
        ]);

        $this->assertDatabaseHas('social_comment_actions', [
            'social_comment_id' => $comment->id,
            'action' => SocialCommentActionType::AutoReplySkipped->value,
        ]);
    }

    public function test_can_detect_if_comment_was_auto_replied(): void
    {
        $comment = $this->socialComment();

        $this->assertNull($comment->auto_replied_at);

        $comment->update(['auto_replied_at' => now()]);
        $comment->refresh();

        $this->assertNotNull($comment->auto_replied_at);
    }

    public function test_can_detect_if_auto_reply_failed(): void
    {
        $comment = $this->socialComment();

        $this->assertNull($comment->auto_reply_error);

        $comment->update(['auto_reply_error' => 'Token expired']);
        $comment->refresh();

        $this->assertNotNull($comment->auto_reply_error);
    }

    public function test_auto_reply_attempts_can_be_incremented(): void
    {
        $comment = $this->socialComment();

        $this->assertSame(0, $comment->fresh()->auto_reply_attempts);

        $comment->increment('auto_reply_attempts');
        $this->assertSame(1, $comment->fresh()->auto_reply_attempts);

        $comment->increment('auto_reply_attempts');
        $this->assertSame(2, $comment->fresh()->auto_reply_attempts);
    }

    private function socialComment(array $overrides = []): SocialComment
    {
        $account = SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'external_account_id' => 'ig_account_'.uniqid(),
            'account_name' => 'clinica_test',
            'access_token' => 'token_'.uniqid(),
        ]);

        $post = SocialPost::create([
            'social_account_id' => $account->id,
            'platform' => SocialPlatform::Instagram,
            'external_post_id' => 'post_'.uniqid(),
            'caption' => 'Test post',
        ]);

        $identity = SocialIdentity::create([
            'social_account_id' => $account->id,
            'platform' => SocialPlatform::Instagram,
            'platform_user_id' => 'user_'.uniqid(),
            'display_name' => 'Paciente Test',
            'status' => 'new_lead',
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
            'classification' => SocialCommentClassification::SalesLead,
            'status' => SocialCommentStatus::New,
        ], $overrides));
    }
}
