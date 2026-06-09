<?php

namespace App\Http\Controllers;

use App\Enums\SocialCommentStatus;
use App\Enums\SocialPlatform;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TestMetaSocialController extends Controller
{
    public function comment(Request $request): JsonResponse
    {
        if (!app()->environment('local', 'testing')) {
            return response()->json(['error' => 'Esta ruta solo esta disponible en entorno local'], 403);
        }

        $data = $request->validate([
            'platform' => ['nullable', 'string', Rule::enum(SocialPlatform::class)],
            'account_id' => ['nullable', 'string'],
            'account_name' => ['nullable', 'string'],
            'post_id' => ['nullable', 'string'],
            'post_message' => ['nullable', 'string'],
            'comment_id' => ['nullable', 'string'],
            'comment_text' => ['required', 'string'],
            'author_name' => ['nullable', 'string'],
            'author_username' => ['nullable', 'string'],
        ]);

        $platform = SocialPlatform::from($data['platform'] ?? SocialPlatform::Facebook->value);
        $accountId = $data['account_id'] ?? 'test_account_' . $platform->value;
        $postId = $data['post_id'] ?? 'test_post_' . now()->timestamp;
        $commentId = $data['comment_id'] ?? 'test_comment_' . uniqid();

        $account = SocialAccount::updateOrCreate(
            [
                'platform' => $platform->value,
                'external_account_id' => $accountId,
            ],
            [
                'account_name' => $data['account_name'] ?? 'Cuenta de prueba ' . $platform->label(),
                'page_id' => $platform === SocialPlatform::Facebook ? $accountId : null,
                'instagram_business_account_id' => $platform === SocialPlatform::Instagram ? $accountId : null,
                'is_active' => true,
                'sync_settings' => ['source' => 'local_test'],
            ],
        );

        $post = SocialPost::updateOrCreate(
            [
                'platform' => $platform->value,
                'external_post_id' => $postId,
            ],
            [
                'social_account_id' => $account->id,
                'caption' => $data['post_message'] ?? 'Publicacion de prueba para reputacion digital',
                'permalink' => 'https://example.test/social-posts/' . $postId,
                'raw_payload' => [
                    'source' => 'local_test',
                    'id' => $postId,
                ],
                'published_at' => now(),
                'last_synced_at' => now(),
            ],
        );

        $comment = SocialComment::updateOrCreate(
            [
                'platform' => $platform->value,
                'external_comment_id' => $commentId,
            ],
            [
                'social_account_id' => $account->id,
                'social_post_id' => $post->id,
                'author_name' => $data['author_name'] ?? 'Cliente Prueba',
                'author_username' => $data['author_username'] ?? 'cliente_prueba',
                'comment_text' => $data['comment_text'],
                'status' => SocialCommentStatus::New,
                'raw_payload' => [
                    'source' => 'local_test',
                    'id' => $commentId,
                    'message' => $data['comment_text'],
                ],
                'published_at' => now(),
            ],
        );

        return response()->json([
            'status' => 'ok',
            'account' => [
                'id' => $account->id,
                'platform' => $account->platform->value,
                'external_account_id' => $account->external_account_id,
            ],
            'post' => [
                'id' => $post->id,
                'external_post_id' => $post->external_post_id,
            ],
            'comment' => [
                'id' => $comment->id,
                'external_comment_id' => $comment->external_comment_id,
                'status' => $comment->status->value,
            ],
        ]);
    }
}
