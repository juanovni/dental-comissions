<?php

namespace App\Http\Controllers;

use App\Services\WhatsappService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private WhatsappService $whatsappService,
    ) {}

    public function verify(Request $request): Response|JsonResponse
    {
        $queryString = $request->getQueryString() ?? '';
        $params = [];
        foreach (explode('&', $queryString) as $pair) {
            $parts = explode('=', $pair, 2);
            if (count($parts) === 2) {
                $params[urldecode($parts[0])] = urldecode($parts[1]);
            }
        }

        $mode = $params['hub.mode'] ?? '';
        $token = $params['hub.verify_token'] ?? '';
        $challenge = $params['hub.challenge'] ?? '';

        if ($this->whatsappService->verifyWebhook($mode, $token)) {
            if ($request->expectsJson()) {
                return response()->json(['challenge' => $challenge]);
            }

            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response()->json(['error' => 'Verification failed'], 403);
    }

    public function receive(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();

            Log::info('Webhook WhatsApp recibido', [
                'object' => $payload['object'] ?? null,
            ]);

            if (($payload['object'] ?? '') !== 'whatsapp_business_account') {
                return response()->json(['status' => 'ignored']);
            }

            $entries = $payload['entry'] ?? [];

            foreach ($entries as $entry) {
                $changes = $entry['changes'] ?? [];

                foreach ($changes as $change) {
                    if (($change['field'] ?? '') !== 'messages') {
                        continue;
                    }

                    $value = $change['value'] ?? [];

                    if (!empty($value['messages'])) {
                        foreach ($value['messages'] as $message) {
                            $singlePayload = ['messages' => [$message]];
                            $this->whatsappService->processIncomingMessage($singlePayload);
                        }
                    }
                }
            }

            return response()->json(['status' => 'ok']);
        } catch (\Throwable $e) {
            Log::error('Error procesando webhook WhatsApp', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['status' => 'error'], 500);
        }
    }
}
