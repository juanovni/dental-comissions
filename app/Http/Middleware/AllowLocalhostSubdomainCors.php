<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowLocalhostSubdomainCors
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! app()->environment('local')) {
            return $response;
        }

        $origin = (string) $request->headers->get('Origin', '');

        if ($origin === '') {
            return $response;
        }

        if (! preg_match('/^http:\/\/([a-z0-9-]+\.)?localhost:8080$/i', $origin)) {
            return $response;
        }

        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Vary', 'Origin');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, X-CSRF-TOKEN, Authorization');

        return $response;
    }
}
