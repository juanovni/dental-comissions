<?php

namespace App\Http\Controllers;

use App\Services\WhatsappService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestWhatsappController extends Controller
{
    public function __construct(
        private WhatsappService $whatsappService,
    ) {}

    public function test(Request $request): JsonResponse
    {
        if (!app()->environment('local', 'testing')) {
            return response()->json(['error' => 'Esta ruta solo esta disponible en entorno local'], 403);
        }

        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        $phone = $request->input('phone');
        $messageBody = $request->input('message');

        $simulatedPayload = [
            'messages' => [
                [
                    'from' => $phone,
                    'id' => 'test_' . uniqid(),
                    'timestamp' => now()->timestamp,
                    'type' => 'text',
                    'text' => ['body' => $messageBody],
                ],
            ],
        ];

        try {
            $whatsappMessage = $this->whatsappService->processIncomingMessage($simulatedPayload);

            if (!$whatsappMessage) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se pudo procesar el mensaje',
                ], 500);
            }

            $whatsappMessage->load('professional');

            $response = [
                'status' => 'ok',
                'message_id' => $whatsappMessage->id,
                'message_sid' => $whatsappMessage->message_sid,
                'professional' => $whatsappMessage->professional?->name ?? 'No identificado',
                'whatsapp_status' => $whatsappMessage->status->value,
                'ai_response' => $whatsappMessage->ai_response,
            ];

            if ($whatsappMessage->relatedMessage) {
                $response['related_to'] = [
                    'id' => $whatsappMessage->relatedMessage->id,
                    'status' => $whatsappMessage->relatedMessage->status->value,
                ];
            }

            return response()->json($response);
        } catch (\Throwable $e) {
            Log::error('Error en test WhatsApp', [
                'error' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
