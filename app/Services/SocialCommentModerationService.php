<?php

namespace App\Services;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialCommentClassification;
use App\Enums\SocialSuggestedAction;
use App\Models\SocialComment;
use Illuminate\Support\Facades\Log;

class SocialCommentModerationService
{
    public function moderateIfNeeded(SocialComment $comment): SocialComment
    {
        $comment = $comment->refresh();

        if (! $this->shouldHideOnPlatform($comment)) {
            return $comment;
        }

        try {
            $response = app(MetaSocialService::class)->hideComment($comment);

            $comment->update([
                'platform_hidden_at' => now(),
                'platform_hide_response' => $response,
                'platform_hide_error' => null,
                'suggested_action' => SocialSuggestedAction::Hide,
            ]);

            $comment->actions()->create([
                'action' => SocialCommentActionType::Hide,
                'notes' => 'Comentario ocultado automáticamente en Meta por lenguaje ofensivo.',
                'external_response' => [
                    'automatic' => true,
                    'platform' => $comment->platform?->value,
                    'meta_response' => $response,
                ],
            ]);
        } catch (\Throwable $e) {
            $comment->update([
                'platform_hide_error' => $e->getMessage(),
                'suggested_action' => SocialSuggestedAction::Hide,
            ]);

            $comment->actions()->create([
                'action' => SocialCommentActionType::Hide,
                'notes' => 'No se pudo ocultar automáticamente el comentario ofensivo en Meta.',
                'external_response' => [
                    'automatic' => true,
                    'platform' => $comment->platform?->value,
                    'error' => $e->getMessage(),
                ],
            ]);

            Log::warning('No se pudo ocultar comentario ofensivo en Meta.', [
                'comment_id' => $comment->id,
                'platform' => $comment->platform?->value,
                'error' => $e->getMessage(),
            ]);
        }

        return $comment->refresh();
    }

    private function shouldHideOnPlatform(SocialComment $comment): bool
    {
        if ($comment->classification !== SocialCommentClassification::Offensive) {
            return false;
        }

        if (filled($comment->platform_hidden_at)) {
            return false;
        }

        return $comment->external_comment_id !== null && $comment->social_account_id !== null;
    }
}
