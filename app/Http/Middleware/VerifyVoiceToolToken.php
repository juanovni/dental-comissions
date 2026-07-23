<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyVoiceToolToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        $expected = config('services.voice.tool_token');

        if (blank($expected) || $token !== $expected) {
            abort(401, 'Token de herramientas de voz invalido.');
        }

        return $next($request);
    }
}
