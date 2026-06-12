<?php

namespace App\Http\Controllers;

use App\Services\MetaSocialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MetaSocialWebhookController extends Controller
{
    public function __construct(
        private MetaSocialService $metaSocialService,
    ) {}

    public function verify(Request $request): Response|JsonResponse
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        if ($mode === 'subscribe' && $token === config('services.meta.verify_token')) {
            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response()->json(['error' => 'Verification failed'], 403);
    }

    public function receive(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Webhook Meta social recibido', [
            'object' => $payload['object'] ?? null,
            'entries' => count($payload['entry'] ?? []),
        ]);

        try {
            $summary = $this->metaSocialService->processWebhookPayload($payload);

            return response()->json([
                'status' => 'ok',
                'summary' => $summary,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error procesando webhook Meta social', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return response()->json([
                'status' => 'accepted',
                'message' => 'Webhook recibido, sincronizacion diferida por error.',
            ]);
        }
    }
}
