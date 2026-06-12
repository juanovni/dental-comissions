<?php

namespace App\Services;

use App\Enums\SocialCommentStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaSocialService
{
    private function config(): array
    {
        return [
            'api_url' => rtrim((string) config('services.meta.api_url', 'https://graph.facebook.com/v25.0'), '/'),
            'access_token' => config('services.meta.access_token'),
            'sync_days' => (int) config('services.meta.sync_days', 30),
        ];
    }

    public function syncAll(bool $refreshAccounts = false): array
    {
        if ($refreshAccounts) {
            $this->syncAuthorizedAccounts();
        }

        $summary = [
            'accounts' => 0,
            'posts' => 0,
            'comments' => 0,
            'errors' => 0,
        ];

        SocialAccount::query()
            ->where('is_active', true)
            ->each(function (SocialAccount $account) use (&$summary): void {
                $summary['accounts']++;

                try {
                    $result = $this->syncAccount($account);
                    $summary['posts'] += $result['posts'];
                    $summary['comments'] += $result['comments'];
                } catch (\Throwable $e) {
                    $summary['errors']++;

                    Log::error('Error sincronizando cuenta social Meta', [
                        'account_id' => $account->id,
                        'platform' => $account->platform->value,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

        return $summary;
    }

    public function processWebhookPayload(array $payload): array
    {
        $summary = [
            'entries' => count($payload['entry'] ?? []),
            'comments' => 0,
            'ignored' => 0,
            'fallback_sync' => false,
        ];

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $webhookComment = $this->normalizeWebhookComment($payload, $entry, $change);

                if (! $webhookComment) {
                    $summary['ignored']++;

                    continue;
                }

                $account = $this->resolveWebhookAccount(
                    $webhookComment['platform'],
                    $webhookComment['account_id'],
                );

                $post = $this->storeWebhookPost($account, $webhookComment['post']);
                $comment = $this->storeComment($account, $post, $webhookComment['comment']);
                app(SocialCommentClassificationService::class)->classify($comment);

                $summary['comments']++;
            }
        }

        if ($summary['comments'] === 0 && $summary['ignored'] === 0) {
            $summary['fallback_sync'] = true;
            $syncSummary = $this->syncAll();
            $summary = array_merge($summary, $syncSummary);
        }

        return $summary;
    }

    public function syncAuthorizedAccounts(): void
    {
        foreach ($this->getPages() as $page) {
            $pageAccount = SocialAccount::updateOrCreate(
                [
                    'platform' => SocialPlatform::Facebook->value,
                    'external_account_id' => $page['id'],
                ],
                [
                    'account_name' => $page['name'] ?? 'Facebook Page',
                    'page_id' => $page['id'],
                    'access_token' => $page['access_token'] ?? $this->config()['access_token'],
                    'is_active' => true,
                    'sync_settings' => ['source' => 'meta_pages'],
                ],
            );

            $instagramAccount = $this->getInstagramAccountForPage($page['id'], $pageAccount);

            if (! $instagramAccount) {
                continue;
            }

            SocialAccount::updateOrCreate(
                [
                    'platform' => SocialPlatform::Instagram->value,
                    'external_account_id' => $instagramAccount['id'],
                ],
                [
                    'account_name' => $instagramAccount['username']
                        ?? $instagramAccount['name']
                        ?? 'Instagram Business',
                    'page_id' => $page['id'],
                    'instagram_business_account_id' => $instagramAccount['id'],
                    'access_token' => $page['access_token'] ?? $this->config()['access_token'],
                    'is_active' => true,
                    'sync_settings' => ['source' => 'meta_instagram_business_account'],
                ],
            );
        }
    }

    public function getPages(): array
    {
        try {
            return $this->getAllPages('/me/accounts', [
                'fields' => 'id,name,access_token',
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo listar /me/accounts. Intentando usar el token como token de pagina.', [
                'error' => $e->getMessage(),
            ]);

            $page = $this->get('/me', [
                'fields' => 'id,name,category',
            ]);

            if (empty($page['id'])) {
                throw $e;
            }

            $page['access_token'] = $this->config()['access_token'];

            return [$page];
        }
    }

    public function getInstagramAccountForPage(string $pageId, ?SocialAccount $account = null): ?array
    {
        try {
            $response = $this->get("/{$pageId}", [
                'fields' => 'instagram_business_account{id,username,name}',
            ], $account);
        } catch (\Throwable $e) {
            Log::warning('No se pudo consultar Instagram Business conectado a la pagina.', [
                'page_id' => $pageId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        return $response['instagram_business_account'] ?? null;
    }

    public function syncAccount(SocialAccount $account): array
    {
        $posts = $this->getRecentPosts($account);
        $summary = ['posts' => 0, 'comments' => 0];

        foreach ($posts as $postData) {
            $post = $this->storePost($account, $postData);
            $summary['posts']++;

            foreach ($this->getPostComments($post, $account) as $commentData) {
                $this->storeComment($account, $post, $commentData);
                $summary['comments']++;
            }
        }

        $account->update(['last_synced_at' => now()]);

        return $summary;
    }

    public function getRecentPosts(SocialAccount $account): array
    {
        $since = now()->subDays($this->config()['sync_days'])->timestamp;

        if ($account->platform === SocialPlatform::Instagram) {
            return $this->getAllPages("/{$account->external_account_id}/media", [
                'fields' => 'id,caption,media_url,permalink,timestamp',
                'since' => $since,
            ], $account);
        }

        return $this->getAllPages("/{$account->page_id}/posts", [
            'fields' => 'id,message,permalink_url,full_picture,created_time',
            'since' => $since,
        ], $account);
    }

    public function getPostComments(SocialPost $post, SocialAccount $account): array
    {
        if ($account->platform === SocialPlatform::Instagram) {
            return $this->getAllPages("/{$post->external_post_id}/comments", [
                'fields' => 'id,text,username,timestamp',
            ], $account);
        }

        return $this->getAllPages("/{$post->external_post_id}/comments", [
            'fields' => 'id,message,from,created_time,parent',
            'filter' => 'stream',
        ], $account);
    }

    public function storePost(SocialAccount $account, array $postData): SocialPost
    {
        $publishedAt = $postData['created_time'] ?? $postData['timestamp'] ?? null;

        return SocialPost::updateOrCreate(
            [
                'platform' => $account->platform->value,
                'external_post_id' => $postData['id'],
            ],
            [
                'social_account_id' => $account->id,
                'caption' => $postData['message'] ?? $postData['caption'] ?? null,
                'media_url' => $postData['full_picture'] ?? $postData['media_url'] ?? null,
                'permalink' => $postData['permalink_url'] ?? $postData['permalink'] ?? null,
                'raw_payload' => $postData,
                'published_at' => $publishedAt ? Carbon::parse($publishedAt) : null,
                'last_synced_at' => now(),
            ],
        );
    }

    public function storeComment(SocialAccount $account, SocialPost $post, array $commentData): SocialComment
    {
        $publishedAt = $commentData['created_time'] ?? $commentData['timestamp'] ?? null;
        $externalParentId = Arr::get($commentData, 'parent.id');
        $identity = $this->resolveSocialIdentity($account, $commentData, $publishedAt);

        $parent = $externalParentId
            ? SocialComment::where('platform', $account->platform->value)
                ->where('external_comment_id', $externalParentId)
                ->first()
            : null;

        $comment = SocialComment::firstOrNew(
            [
                'platform' => $account->platform->value,
                'external_comment_id' => $commentData['id'],
            ],
        );

        $comment->fill([
            'social_account_id' => $account->id,
            'social_identity_id' => $identity?->id,
            'social_post_id' => $post->id,
            'parent_comment_id' => $parent?->id,
            'external_parent_comment_id' => $externalParentId,
            'author_name' => Arr::get($commentData, 'from.name'),
            'author_username' => $commentData['username'] ?? null,
            'author_external_id' => Arr::get($commentData, 'from.id'),
            'comment_text' => $commentData['message'] ?? $commentData['text'] ?? '',
            'raw_payload' => $commentData,
            'published_at' => $publishedAt ? Carbon::parse($publishedAt) : null,
        ]);

        if (! $comment->exists) {
            $comment->status = SocialCommentStatus::New;
        }

        $comment->save();

        return $comment;
    }

    private function normalizeWebhookComment(array $payload, array $entry, array $change): ?array
    {
        $value = $change['value'] ?? [];

        if (($value['verb'] ?? 'add') !== 'add') {
            return null;
        }

        $platform = ($payload['object'] ?? '') === 'instagram'
            ? SocialPlatform::Instagram
            : SocialPlatform::Facebook;

        if ($platform === SocialPlatform::Instagram) {
            $commentId = $value['id'] ?? $value['comment_id'] ?? null;
            $message = $value['text'] ?? $value['message'] ?? null;
            $postId = Arr::get($value, 'media.id') ?? $value['media_id'] ?? $value['post_id'] ?? null;
            $accountId = (string) ($entry['id'] ?? Arr::get($value, 'media.owner.id') ?? '');

            if (blank($commentId) || blank($message) || blank($accountId)) {
                return null;
            }

            return [
                'platform' => SocialPlatform::Instagram,
                'account_id' => $accountId,
                'post' => [
                    'id' => $postId ?: 'instagram-comment-'.$commentId,
                    'caption' => null,
                    'timestamp' => $value['timestamp'] ?? null,
                    'raw_payload' => $value,
                ],
                'comment' => [
                    'id' => $commentId,
                    'text' => $message,
                    'username' => Arr::get($value, 'from.username') ?? $value['username'] ?? null,
                    'from' => [
                        'id' => Arr::get($value, 'from.id') ?? Arr::get($value, 'from.username') ?? null,
                        'name' => Arr::get($value, 'from.username') ?? $value['username'] ?? null,
                    ],
                    'timestamp' => $value['timestamp'] ?? null,
                    'parent' => ['id' => $value['parent_id'] ?? null],
                    'raw_webhook_payload' => $value,
                ],
            ];
        }

        if (($value['item'] ?? null) !== 'comment' && ! isset($value['comment_id'])) {
            return null;
        }

        $commentId = $value['comment_id'] ?? $value['id'] ?? null;
        $message = $value['message'] ?? $value['text'] ?? null;
        $postId = $value['post_id'] ?? $value['parent_id'] ?? null;
        $accountId = (string) ($entry['id'] ?? $value['page_id'] ?? '');

        if (blank($commentId) || blank($message) || blank($accountId)) {
            return null;
        }

        return [
            'platform' => SocialPlatform::Facebook,
            'account_id' => $accountId,
            'post' => [
                'id' => $postId ?: 'facebook-comment-'.$commentId,
                'caption' => null,
                'created_time' => isset($value['created_time']) && is_numeric($value['created_time'])
                    ? Carbon::createFromTimestamp((int) $value['created_time'])->toIso8601String()
                    : ($value['created_time'] ?? null),
                'raw_payload' => $value,
            ],
            'comment' => [
                'id' => $commentId,
                'message' => $message,
                'from' => [
                    'id' => Arr::get($value, 'from.id') ?? $value['sender_id'] ?? null,
                    'name' => Arr::get($value, 'from.name') ?? $value['sender_name'] ?? null,
                ],
                'created_time' => isset($value['created_time']) && is_numeric($value['created_time'])
                    ? Carbon::createFromTimestamp((int) $value['created_time'])->toIso8601String()
                    : ($value['created_time'] ?? null),
                'parent' => ['id' => $value['parent_id'] ?? null],
                'raw_webhook_payload' => $value,
            ],
        ];
    }

    private function resolveWebhookAccount(SocialPlatform $platform, string $externalAccountId): SocialAccount
    {
        $query = SocialAccount::query()->where('platform', $platform->value);

        if ($platform === SocialPlatform::Instagram) {
            $query->where(function ($query) use ($externalAccountId): void {
                $query->where('external_account_id', $externalAccountId)
                    ->orWhere('instagram_business_account_id', $externalAccountId);
            });
        } else {
            $query->where(function ($query) use ($externalAccountId): void {
                $query->where('external_account_id', $externalAccountId)
                    ->orWhere('page_id', $externalAccountId);
            });
        }

        $account = $query->first();

        if ($account) {
            return $account;
        }

        return SocialAccount::create([
            'platform' => $platform->value,
            'account_name' => $platform === SocialPlatform::Instagram ? 'Instagram Business' : 'Facebook Page',
            'external_account_id' => $externalAccountId,
            'page_id' => $platform === SocialPlatform::Facebook ? $externalAccountId : null,
            'instagram_business_account_id' => $platform === SocialPlatform::Instagram ? $externalAccountId : null,
            'is_active' => true,
            'sync_settings' => ['source' => 'meta_webhook'],
        ]);
    }

    private function storeWebhookPost(SocialAccount $account, array $postData): SocialPost
    {
        return $this->storePost($account, [
            'id' => $postData['id'],
            'message' => $postData['caption'] ?? null,
            'caption' => $postData['caption'] ?? null,
            'created_time' => $postData['created_time'] ?? $postData['timestamp'] ?? null,
            'timestamp' => $postData['timestamp'] ?? $postData['created_time'] ?? null,
            'raw_payload' => $postData['raw_payload'] ?? $postData,
        ]);
    }

    private function resolveSocialIdentity(SocialAccount $account, array $commentData, mixed $publishedAt = null): ?SocialIdentity
    {
        $platformUserId = Arr::get($commentData, 'from.id') ?: ($commentData['username'] ?? null);

        if (blank($platformUserId)) {
            return null;
        }

        $seenAt = $publishedAt ? Carbon::parse($publishedAt) : now();

        $identity = SocialIdentity::firstOrNew([
            'platform' => $account->platform->value,
            'platform_user_id' => (string) $platformUserId,
        ]);

        $metadata = $identity->metadata ?? [];
        $metadata['last_source'] = 'meta_comment_sync';
        $metadata['social_account_id'] = $account->id;

        $identity->fill([
            'username' => $commentData['username'] ?? $identity->username,
            'display_name' => Arr::get($commentData, 'from.name') ?: ($commentData['username'] ?? $identity->display_name),
            'status' => $identity->status?->value ?? SocialIdentityStatus::NewLead->value,
            'first_seen_at' => $identity->first_seen_at ?: $seenAt,
            'last_seen_at' => $seenAt,
            'metadata' => $metadata,
        ]);

        $identity->save();

        return $identity;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAllPages(string $path, array $params = [], ?SocialAccount $account = null): array
    {
        $results = [];
        $url = $path;

        do {
            $response = str_starts_with($url, 'http')
                ? $this->getAbsolute($url, [], $account)
                : $this->get($url, $params, $account);

            $results = array_merge($results, $response['data'] ?? []);
            $url = $response['paging']['next'] ?? null;
            $params = [];
        } while ($url);

        return $results;
    }

    private function get(string $path, array $params = [], ?SocialAccount $account = null): array
    {
        return $this->getAbsolute($this->url($path), $params, $account);
    }

    private function getAbsolute(string $url, array $params = [], ?SocialAccount $account = null): array
    {
        $token = $account?->access_token ?: $this->config()['access_token'];

        if (blank($token)) {
            throw new \RuntimeException('META_ACCESS_TOKEN no configurado.');
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->withOptions($this->httpOptions())
            ->get($url, $params);

        if ($response->failed()) {
            Log::error('Error consultando Meta Graph API', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $response->throw();
        }

        return $response->json() ?? [];
    }

    private function httpOptions(): array
    {
        return defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')
            ? ['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]]
            : [];
    }

    private function url(string $path): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return $this->config()['api_url'].'/'.ltrim($path, '/');
    }
}
