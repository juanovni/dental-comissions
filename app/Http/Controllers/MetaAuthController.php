<?php

namespace App\Http\Controllers;

use App\Enums\SocialPlatform;
use App\Models\SocialAccount;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MetaAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $config = $this->config();

        $state = Str::random(40);
        $request->session()->put('meta_oauth_state', $state);

        $query = http_build_query([
            'client_id' => $config['app_id'],
            'redirect_uri' => $config['redirect_uri'],
            'state' => $state,
            'response_type' => 'code',
            'auth_type' => 'rerequest',
            'scope' => implode(',', [
                'pages_show_list',
                'pages_manage_metadata',
                'pages_read_engagement',
                'pages_manage_engagement',
                'pages_read_user_content',
                'instagram_basic',
                'instagram_manage_comments',
            ]),
        ]);

        return redirect()->away("https://www.facebook.com/{$config['graph_version']}/dialog/oauth?{$query}");
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_if($request->filled('error'), 400, $request->string('error_description')->toString());
        abort_unless($request->filled('code'), 400, 'Meta no devolvio codigo de autorizacion.');
        abort_unless(
            hash_equals((string) $request->session()->pull('meta_oauth_state'), (string) $request->query('state')),
            403,
            'Estado OAuth invalido.',
        );

        try {
            $shortToken = $this->exchangeCodeForToken($request->string('code')->toString());
            $longToken = $this->exchangeForLongLivedToken($shortToken['access_token']);
            $userAccessToken = $this->selectTokenWithPageAccess(
                $shortToken['access_token'],
                $longToken['access_token'] ?? null,
            );

            $this->logGrantedPermissions($userAccessToken);

            $summary = $this->storeAuthorizedAccounts($userAccessToken, $longToken['expires_in'] ?? $shortToken['expires_in'] ?? null);
        } catch (ConnectionException $e) {
            Log::error('OAuth Meta no pudo conectar con Graph API.', [
                'error' => $e->getMessage(),
            ]);

            return redirect('/admin/social-accounts')->with(
                'error',
                'No se pudo conectar con Meta Graph API. Revisa DNS/conectividad del servidor e intenta conectar Meta nuevamente.',
            );
        } catch (\Throwable $e) {
            Log::error('OAuth Meta fallo durante la integracion.', [
                'error' => $e->getMessage(),
            ]);

            return redirect('/admin/social-accounts')->with(
                'error',
                'No se pudo completar la conexión con Meta. Intenta nuevamente desde Integraciones.',
            );
        }

        Log::info('OAuth Meta completado.', $summary);

        return redirect('/admin/social-accounts')->with(
            'status',
            "Meta conectado. Paginas: {$summary['pages']}. Instagram: {$summary['instagram']}.",
        );
    }

    private function selectTokenWithPageAccess(string $shortAccessToken, ?string $longAccessToken): string
    {
        $tokens = array_filter([
            'long' => $longAccessToken,
            'short' => $shortAccessToken,
        ]);

        foreach ($tokens as $type => $token) {
            try {
                $pages = $this->getAllPages('/me/accounts', [
                    'fields' => 'id,name',
                ], $token);

                Log::info('Revision de acceso a paginas Meta por tipo de token.', [
                    'token_type' => $type,
                    'pages_count' => count($pages),
                ]);

                if (count($pages) > 0) {
                    return $token;
                }
            } catch (\Throwable $e) {
                Log::warning('No se pudo revisar paginas Meta por tipo de token.', [
                    'token_type' => $type,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $longAccessToken ?: $shortAccessToken;
    }

    private function exchangeCodeForToken(string $code): array
    {
        $config = $this->config();

        return $this->graphGet('/oauth/access_token', [
            'client_id' => $config['app_id'],
            'client_secret' => $config['app_secret'],
            'redirect_uri' => $config['redirect_uri'],
            'code' => $code,
        ]);
    }

    private function exchangeForLongLivedToken(string $accessToken): array
    {
        $config = $this->config();

        return $this->graphGet('/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $config['app_id'],
            'client_secret' => $config['app_secret'],
            'fb_exchange_token' => $accessToken,
        ]);
    }

    private function storeAuthorizedAccounts(string $userAccessToken, ?int $expiresIn): array
    {
        $summary = ['pages' => 0, 'instagram' => 0];
        $expiresAt = $expiresIn ? now()->addSeconds($expiresIn) : null;

        $pages = $this->getAllPages('/me/accounts', [
            'fields' => 'id,name,access_token,tasks',
        ], $userAccessToken);

        if (empty($pages)) {
            $pages = $this->getConfiguredPages($userAccessToken);
        }

        Log::info('Paginas devueltas por OAuth Meta.', [
            'count' => count($pages),
            'pages' => collect($pages)
                ->map(fn (array $page): array => [
                    'id' => $page['id'] ?? null,
                    'name' => $page['name'] ?? null,
                    'has_access_token' => filled($page['access_token'] ?? null),
                    'tasks' => $page['tasks'] ?? [],
                ])
                ->values()
                ->all(),
        ]);

        foreach ($pages as $page) {
            if (blank($page['id'] ?? null)) {
                continue;
            }

            $pageAccessToken = $page['access_token'] ?? $userAccessToken;
            $existingPageAccount = SocialAccount::query()
                ->where('platform', SocialPlatform::Facebook->value)
                ->where('external_account_id', $page['id'])
                ->first();

            $pageAccount = SocialAccount::updateOrCreate(
                [
                    'platform' => SocialPlatform::Facebook->value,
                    'external_account_id' => $page['id'],
                ],
                [
                    'account_name' => $page['name'] ?? 'Facebook Page',
                    'page_id' => $page['id'],
                    'access_token' => $pageAccessToken,
                    'token_expires_at' => $expiresAt,
                    'is_active' => true,
                    'sync_settings' => $this->syncSettingsWithConnectedAt($existingPageAccount, 'meta_oauth'),
                ],
            );

            $summary['pages']++;

            $instagramAccount = $this->getInstagramAccount($page['id'], $pageAccessToken);

            if (! $instagramAccount) {
                continue;
            }

            $existingInstagramAccount = SocialAccount::query()
                ->where('platform', SocialPlatform::Instagram->value)
                ->where('external_account_id', $instagramAccount['id'])
                ->first();

            SocialAccount::updateOrCreate(
                [
                    'platform' => SocialPlatform::Instagram->value,
                    'external_account_id' => $instagramAccount['id'],
                ],
                [
                    'account_name' => $instagramAccount['username']
                        ?? $instagramAccount['name']
                        ?? 'Instagram Business',
                    'page_id' => $pageAccount->page_id,
                    'instagram_business_account_id' => $instagramAccount['id'],
                    'access_token' => $pageAccessToken,
                    'token_expires_at' => $expiresAt,
                    'is_active' => true,
                    'sync_settings' => $this->syncSettingsWithConnectedAt($existingInstagramAccount, 'meta_oauth_instagram_business_account'),
                ],
            );

            $summary['instagram']++;
        }

        return $summary;
    }

    private function syncSettingsWithConnectedAt(?SocialAccount $account, string $source): array
    {
        $settings = $account?->sync_settings ?? [];

        return array_merge($settings, [
            'source' => $source,
            'connected_at' => $settings['connected_at'] ?? now()->toIso8601String(),
        ]);
    }

    private function getConfiguredPages(string $userAccessToken): array
    {
        $pages = [];

        foreach (config('services.meta.page_ids', []) as $pageId) {
            try {
                $page = $this->graphGet("/{$pageId}", [
                    'fields' => 'id,name,access_token',
                ], $userAccessToken);

                if (filled($page['id'] ?? null)) {
                    $pages[] = $page;
                }
            } catch (\Throwable $e) {
                Log::warning('No se pudo consultar pagina Meta configurada.', [
                    'page_id' => $pageId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Paginas Meta configuradas consultadas como fallback.', [
            'count' => count($pages),
            'page_ids' => collect($pages)->pluck('id')->all(),
        ]);

        return $pages;
    }

    private function logGrantedPermissions(string $userAccessToken): void
    {
        try {
            $profile = $this->graphGet('/me', [
                'fields' => 'id,name',
            ], $userAccessToken);
            $permissions = $this->graphGet('/me/permissions', [], $userAccessToken);

            Log::info('Permisos OAuth Meta concedidos.', [
                'profile' => [
                    'id' => $profile['id'] ?? null,
                    'name' => $profile['name'] ?? null,
                ],
                'permissions' => collect($permissions['data'] ?? [])
                    ->map(fn (array $permission): array => [
                        'permission' => $permission['permission'] ?? null,
                        'status' => $permission['status'] ?? null,
                    ])
                    ->values()
                    ->all(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudieron auditar permisos OAuth Meta.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getInstagramAccount(string $pageId, string $pageAccessToken): ?array
    {
        try {
            $response = $this->graphGet("/{$pageId}", [
                'fields' => 'instagram_business_account{id,username,name}',
            ], $pageAccessToken);
        } catch (\Throwable $e) {
            Log::warning('No se pudo consultar Instagram Business durante OAuth Meta.', [
                'page_id' => $pageId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        return $response['instagram_business_account'] ?? null;
    }

    private function getAllPages(string $path, array $params, string $accessToken): array
    {
        $results = [];
        $url = $this->url($path);

        do {
            $response = $this->graphGet($url, $params, $accessToken);
            $results = array_merge($results, $response['data'] ?? []);
            $url = $response['paging']['next'] ?? null;
            $params = [];
        } while ($url);

        return $results;
    }

    private function graphGet(string $path, array $params = [], ?string $accessToken = null): array
    {
        $request = Http::acceptJson()
            ->withOptions($this->httpOptions());

        if ($accessToken) {
            $request = $request->withToken($accessToken);
        }

        $response = $request->get($this->url($path), $params);

        if ($response->failed()) {
            Log::error('Error OAuth Meta Graph API', [
                'url' => $this->url($path),
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

        return rtrim((string) config('services.meta.api_url'), '/').'/'.ltrim($path, '/');
    }

    private function config(): array
    {
        $config = [
            'app_id' => config('services.meta.app_id'),
            'app_secret' => config('services.meta.app_secret'),
            'redirect_uri' => config('services.meta.redirect_uri'),
            'graph_version' => config('services.meta.graph_version', 'v25.0'),
        ];

        abort_if(blank($config['app_id']), 500, 'META_APP_ID no configurado.');
        abort_if(blank($config['app_secret']), 500, 'META_APP_SECRET no configurado.');
        abort_if(blank($config['redirect_uri']), 500, 'META_REDIRECT_URI no configurado.');

        return $config;
    }
}
