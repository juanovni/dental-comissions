<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->validateCsrfTokens(except: [
            'webhook/whatsapp',
            'webhook/meta/social',
            'webhook/telnyx/voice/events',
            'test/whatsapp',
            'test/meta/comment',
        ]);

        $middleware->alias([
            'tenant.auth' => \App\Http\Middleware\EnsureAuthenticatedUserCanAccessTenant::class,
            'tenant.host' => \App\Http\Middleware\ResolveClinicFromHost::class,
            'tenant.match' => \App\Http\Middleware\EnsureTenantMatchesHost::class,
            'tenant.request' => \App\Http\Middleware\ResolveClinicFromRequest::class,
            'voice.tool' => \App\Http\Middleware\VerifyVoiceToolToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
