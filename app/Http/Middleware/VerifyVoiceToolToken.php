<?php

namespace App\Http\Middleware;

use App\Models\Clinic;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyVoiceToolToken
{
    public function __construct(private TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->bearerToken();
        $globalToken = config('services.voice.tool_token');

        if (is_string($globalToken) && $globalToken !== '' && hash_equals($globalToken, $token)) {
            return $next($request);
        }

        $clinic = $this->resolveClinicByToken($token);

        if ($clinic === null) {
            abort(401, 'Token de herramientas de voz invalido.');
        }

        $this->tenantContext->set($clinic);

        try {
            return $next($request);
        } finally {
            $this->tenantContext->clear();
        }
    }

    private function resolveClinicByToken(string $token): ?Clinic
    {
        if ($token === '') {
            return null;
        }

        return Clinic::query()
            ->get()
            ->first(function (Clinic $clinic) use ($token): bool {
                $voiceSettings = $clinic->settings['integrations']['voice'] ?? null;

                return ($voiceSettings['tool_token'] ?? null) === $token
                    || ($clinic->settings['voice_tool_token'] ?? null) === $token;
            });
    }
}
