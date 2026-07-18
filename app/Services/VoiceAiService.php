<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VoiceAiService
{
    private array $messages = [];

    public function __construct(
        private VoiceToolService $toolService,
        private VoiceSessionService $sessionService,
    ) {}

    public function startConversation(string $phoneE164, ?string &$callId = null): array
    {
        $call = $this->sessionService->startCall($phoneE164, \App\Enums\VoiceChannelType::WebTest);

        $callId = (string) $call->id;

        $this->messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt()],
            ['role' => 'assistant', 'content' => 'Entendido. Esperando al primer mensaje del paciente.'],
        ];

        $this->sessionService->addMessage(
            $call,
            \App\Enums\VoiceEventType::AssistantMessage,
            'Hola, soy Pity, la recepcionista virtual de OdonCRM. ¿En qué puedo ayudarte?',
        );

        return [
            'call_id' => $call->id,
            'message' => 'Hola, soy Pity, la recepcionista virtual de OdonCRM. ¿En qué puedo ayudarte?',
        ];
    }

    public function sendMessage(int $callId, string $userMessage): array
    {
        $call = \App\Models\VoiceCall::findOrFail($callId);

        $this->sessionService->addMessage($call, \App\Enums\VoiceEventType::UserMessage, $userMessage);

        $call->refresh();
        $this->messages = $this->messagesForCall($call);

        if ($selectedSlot = $this->selectedOfferedSlotFromMessage($call, $userMessage)) {
            $args = [
                'slot_datetime' => $selectedSlot['datetime'],
                'doctor_id' => $selectedSlot['doctor_id'],
                'procedure_id' => $selectedSlot['procedure_id'],
            ];

            if (blank($selectedSlot['doctor_id'] ?? null) || blank($selectedSlot['procedure_id'] ?? null)) {
                $result = ['error' => 'Falta doctor o procedimiento para retener el horario.'];
                $text = 'Puedo ayudarte con ese horario, pero antes necesito confirmar el procedimiento o motivo de la cita.';
            } else {
                $result = $this->executeTool($call, 'hold_slot', $args);
                $text = isset($result['error'])
                    ? 'No pude retener ese horario: ' . $result['error']
                    : $this->buildHeldSlotReply($selectedSlot);
            }

            $this->sessionService->addToolCall($call, 'hold_slot', $args, $result);

            $this->sessionService->addMessage($call, \App\Enums\VoiceEventType::AssistantMessage, $text);

            $call->refresh();

            return [
                'message' => $text,
                'tool_calls' => [[
                    'tool' => 'hold_slot',
                    'arguments' => $args,
                    'result' => $result,
                ]],
                'status' => $call->status->value,
                'handoff' => false,
                'ended' => false,
            ];
        }

        $toolResults = [];

        for ($i = 0; $i < 5; $i++) {
            $response = $this->callAi();

            $text = $response['text'] ?? null;
            $functionCall = $response['function_call'] ?? null;

            if ($functionCall) {
                $result = $this->executeTool($call, $functionCall['name'], $functionCall['args']);

                $this->sessionService->addToolCall($call, $functionCall['name'], $functionCall['args'], $result);

                if ($functionCall['name'] === 'create_appointment' && isset($result['appointment_id']) && ! isset($result['error'])) {
                    $this->sessionService->linkAppointment($call, (int) $result['appointment_id']);
                }

                if ($functionCall['name'] === 'request_handoff' && ! isset($result['error'])) {
                    $this->sessionService->markHandoff(
                        $call,
                        (string) ($result['reason'] ?? 'human_requested'),
                        $result['summary'] ?? null,
                    );
                }

                $toolResults[] = [
                    'tool' => $functionCall['name'],
                    'arguments' => $functionCall['args'],
                    'result' => $result,
                ];

                if ($functionCall['name'] === 'get_available_slots' && ! empty($result['slots']) && ! isset($result['error'])) {
                    $text = $this->buildAvailableSlotsReply($result);

                    $this->sessionService->addMessage($call, \App\Enums\VoiceEventType::AssistantMessage, $text);

                    $call->refresh();

                    return [
                        'message' => $text,
                        'tool_calls' => $toolResults,
                        'status' => $call->status->value,
                        'handoff' => false,
                        'ended' => false,
                    ];
                }

                $toolCallId = $functionCall['id'] ?? 'voice_tool_' . str()->random(12);

                $this->messages[] = [
                    'role' => 'assistant',
                    'content' => null,
                    'tool_calls' => [[
                        'id' => $toolCallId,
                        'type' => 'function',
                        'function' => [
                            'name' => $functionCall['name'],
                            'arguments' => json_encode($functionCall['args'], JSON_UNESCAPED_UNICODE),
                        ],
                    ]],
                ];

                $this->messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolCallId,
                    'name' => $functionCall['name'],
                    'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
                ];

                continue;
            }

            if ($text) {
                $this->messages[] = ['role' => 'assistant', 'content' => $text];

                $this->sessionService->addMessage($call, \App\Enums\VoiceEventType::AssistantMessage, $text);
            }

            $isEnding = str_contains($text ?? '', 'gracias por llamar') || str_contains($text ?? '', 'que tengas buen');

            $call->refresh();
            $hasHandoff = $call->status === \App\Enums\VoiceCallStatus::HandoffRequired;

            if ($isEnding || $hasHandoff) {
                $this->sessionService->endCall($call, $hasHandoff
                    ? \App\Enums\VoiceCallStatus::HandoffRequired
                    : \App\Enums\VoiceCallStatus::Completed);
            }

            return [
                'message' => $text ?? '',
                'tool_calls' => $toolResults,
                'status' => $call->status->value,
                'handoff' => $hasHandoff,
                'ended' => $isEnding || $hasHandoff,
            ];
        }

        $this->sessionService->setError($call, 'Maximos intentos de tool call alcanzados.');

        return [
            'message' => 'Lo siento, ocurrio un error. Te transferire con un agente humano.',
            'tool_calls' => $toolResults,
            'status' => 'failed',
            'handoff' => true,
            'ended' => true,
        ];
    }

    private function callAi(): array
    {
        return match (strtolower((string) config('services.ai.provider', 'gemini'))) {
            'openai' => $this->callOpenAi(),
            'gemini' => $this->callGemini(),
            default => ['text' => 'Proveedor de IA no soportado para Pity Voice. Revisa AI_PROVIDER.'],
        };
    }

    private function callOpenAi(): array
    {
        $apiKey = config('services.openai.api_key');

        if (!$apiKey) {
            return ['text' => 'Lo siento, OPENAI_API_KEY no esta configurado. Por favor, contacta al administrador.'];
        }

        $apiUrl = rtrim((string) config('services.openai.api_url', 'https://api.openai.com/v1'), '/');
        $model = config('services.openai.model', 'gpt-4o-mini');

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout((int) config('services.openai.request_timeout', 30))
                ->post($apiUrl . '/chat/completions', [
                    'model' => $model,
                    'messages' => $this->messages,
                    'tools' => $this->openAiToolDefinitions(),
                    'tool_choice' => 'auto',
                    'temperature' => 0.3,
                ])
                ->throw();

            $message = $response->json('choices.0.message') ?? [];
            $toolCall = $message['tool_calls'][0] ?? null;

            if ($toolCall) {
                return [
                    'function_call' => [
                        'id' => $toolCall['id'] ?? null,
                        'name' => $toolCall['function']['name'],
                        'args' => json_decode($toolCall['function']['arguments'] ?? '{}', true) ?: [],
                    ],
                ];
            }

            return ['text' => $message['content'] ?? ''];
        } catch (\Throwable $e) {
            Log::error('Error llamando a OpenAI en VoiceAiService', ['error' => $e->getMessage()]);

            return ['text' => 'Disculpa, tengo problemas tecnicos con OpenAI. Revisa OPENAI_API_KEY y OPENAI_MODEL.'];
        }
    }

    private function callGemini(): array
    {
        $apiKey = config('services.gemini.api_key');

        if (!$apiKey) {
            return ['text' => 'Lo siento, el servicio de IA no esta configurado. Por favor, contacta al administrador.'];
        }

        $apiUrl = rtrim((string) config('services.gemini.api_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $model = config('services.gemini.model', 'gemini-2.0-flash');
        $url = "{$apiUrl}/models/{$model}:generateContent?key=" . urlencode((string) $apiKey);

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $this->buildSystemPrompt()]],
            ],
            'contents' => $this->geminiContents(),
            'tools' => [$this->toolDefinitions()],
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 500,
            ],
        ];

        try {
            $response = Http::timeout(30)->post($url, $payload)->throw();

            $candidate = $response->json('candidates.0.content');

            if (!$candidate) {
                return ['text' => 'No se pudo obtener respuesta del asistente.'];
            }

            $part = $candidate['parts'][0] ?? [];

            if (isset($part['functionCall'])) {
                return [
                    'function_call' => [
                        'name' => $part['functionCall']['name'],
                        'args' => $part['functionCall']['args'] ?? [],
                    ],
                ];
            }

            return ['text' => $part['text'] ?? '']; // sin mensaje por defecto
        } catch (\Throwable $e) {
            Log::error('Error llamando a Gemini en VoiceAiService', ['error' => $e->getMessage()]);

            return ['text' => 'Disculpa, tengo problemas tecnicos con Gemini. Revisa GEMINI_API_KEY y GEMINI_MODEL.'];
        }
    }

    private function messagesForCall(\App\Models\VoiceCall $call): array
    {
        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt()
                . "\n\nFecha y hora actual del sistema: " . now()->format('Y-m-d H:i:s')
                . "\nTelefono actual de la llamada: {$call->from_phone}"],
        ];

        foreach ($call->events()->orderBy('id')->get() as $event) {
            $message = $event->payload['message'] ?? null;

            if ($event->type === \App\Enums\VoiceEventType::UserMessage) {
                if (! is_string($message) || trim($message) === '') {
                    continue;
                }

                $messages[] = ['role' => 'user', 'content' => $message];
            }

            if ($event->type === \App\Enums\VoiceEventType::AssistantMessage) {
                if (! is_string($message) || trim($message) === '') {
                    continue;
                }

                $messages[] = ['role' => 'assistant', 'content' => $message];
            }

            if ($event->type === \App\Enums\VoiceEventType::ToolCalled) {
                $toolName = $event->payload['tool'] ?? null;

                if (! is_string($toolName) || trim($toolName) === '') {
                    continue;
                }

                $messages[] = [
                    'role' => 'assistant',
                    'content' => null,
                    'tool_calls' => [[
                        'id' => 'voice_event_' . $event->id,
                        'type' => 'function',
                        'function' => [
                            'name' => $toolName,
                            'arguments' => json_encode($event->payload['arguments'] ?? [], JSON_UNESCAPED_UNICODE),
                        ],
                    ]],
                ];
            }

            if ($event->type === \App\Enums\VoiceEventType::ToolResult) {
                $toolName = $event->payload['tool'] ?? null;
                $previousToolCall = collect($messages)->reverse()->first(
                    fn (array $msg): bool => ($msg['role'] ?? null) === 'assistant'
                        && isset($msg['tool_calls'][0])
                        && ($msg['tool_calls'][0]['function']['name'] ?? null) === $toolName,
                );

                if (! is_string($toolName) || ! $previousToolCall) {
                    continue;
                }

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $previousToolCall['tool_calls'][0]['id'],
                    'name' => $toolName,
                    'content' => json_encode($event->payload['result'] ?? [], JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        return $messages;
    }

    private function geminiContents(): array
    {
        $contents = [];

        foreach ($this->messages as $message) {
            if (($message['role'] ?? null) === 'system') {
                continue;
            }

            if (($message['role'] ?? null) === 'assistant' && isset($message['tool_calls'][0])) {
                $toolCall = $message['tool_calls'][0];
                $contents[] = [
                    'role' => 'model',
                    'parts' => [[
                        'functionCall' => [
                            'name' => $toolCall['function']['name'],
                            'args' => json_decode($toolCall['function']['arguments'] ?? '{}', true) ?: [],
                        ],
                    ]],
                ];
                continue;
            }

            if (($message['role'] ?? null) === 'tool') {
                $contents[] = [
                    'role' => 'function',
                    'parts' => [[
                        'functionResponse' => [
                            'name' => $message['name'],
                            'response' => [
                                'result' => json_decode($message['content'] ?? '{}', true) ?: [],
                            ],
                        ],
                    ]],
                ];
                continue;
            }

            $contents[] = [
                'role' => ($message['role'] ?? null) === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => (string) ($message['content'] ?? '')]],
            ];
        }

        return $contents;
    }

    private function openAiToolDefinitions(): array
    {
        return array_map(
            fn (array $declaration): array => [
                'type' => 'function',
                'function' => $declaration,
            ],
            $this->toolDefinitions()['functionDeclarations'],
        );
    }

    private function executeTool(\App\Models\VoiceCall $call, string $name, array $args): array
    {
        try {
            $args = $this->normalizeToolArgs($call, $name, $args);
            $this->assertToolArgsAllowedByBackendHistory($call, $name, $args);

            return match ($name) {
                'identify_patient' => $this->toolService->identifyPatient($args),
                'get_available_slots' => $this->toolService->getAvailableSlots($args),
                'hold_slot' => $this->toolService->holdSlot($args),
                'create_appointment' => $this->toolService->createAppointment($args),
                'request_handoff' => $this->toolService->requestHandoff($args),
                default => throw new \InvalidArgumentException("Tool desconocida: {$name}"),
            };
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function normalizeToolArgs(\App\Models\VoiceCall $call, string $name, array $args): array
    {
        if (in_array($name, ['identify_patient', 'hold_slot', 'create_appointment'], true)) {
            $args['phone_e164'] = $call->from_phone;
        }

        if ($name === 'get_available_slots') {
            $parsed = $this->latestAppointmentDateFromUserMessages($call);

            if ($parsed['date']) {
                $args['preferred_date'] = $parsed['date'];
            }

            if ($parsed['period']) {
                $args['preferred_period'] = $parsed['period'];
            }
        }

        unset($args['sede'], $args['branch'], $args['location']);

        return $args;
    }

    private function latestAppointmentDateFromUserMessages(\App\Models\VoiceCall $call): array
    {
        $events = $call->events()
            ->where('type', \App\Enums\VoiceEventType::UserMessage->value)
            ->latest('id')
            ->get();

        foreach ($events as $event) {
            $message = $event->payload['message'] ?? null;

            if (! is_string($message) || trim($message) === '') {
                continue;
            }

            $parsed = app(AppointmentIntentService::class)->extractFromText($message);

            if (($parsed['date'] ?? null) || ($parsed['period'] ?? null)) {
                return [
                    'date' => $parsed['date'] ?? null,
                    'period' => $parsed['period'] ?? null,
                ];
            }
        }

        return ['date' => null, 'period' => null];
    }

    private function buildAvailableSlotsReply(array $result): string
    {
        $procedure = $result['procedure_name'] ?? 'el procedimiento';
        $lines = [];

        foreach (array_slice($result['slots'], 0, 3) as $index => $slot) {
            $label = $slot['label'] ?? \Carbon\Carbon::parse($slot['datetime'])->isoFormat('dddd D [de] MMMM [a las] h:mm A');
            $lines[] = ($index + 1) . '. ' . $label;
        }

        $intro = ($result['is_default_procedure'] ?? false)
            ? "Como aún no tengo un procedimiento específico, puedo ayudarte a agendar una {$procedure} para que el doctor revise tu caso. Estos son los horarios disponibles:"
            : "He encontrado estos horarios disponibles para {$procedure}:";

        return $intro . "\n\n"
            . implode("\n", $lines)
            . "\n\n¿Cuál de estos horarios prefieres?";
    }

    private function selectedOfferedSlotFromMessage(\App\Models\VoiceCall $call, string $message): ?array
    {
        $slots = $this->latestOfferedSlots($call);

        if ($slots === []) {
            return null;
        }

        $index = $this->selectedOptionIndex($message);

        if ($index !== null && isset($slots[$index - 1])) {
            return $slots[$index - 1];
        }

        $times = $this->timeCandidatesFromMessage($message);

        if ($times === []) {
            return null;
        }

        foreach ($slots as $slot) {
            $slotTime = \Carbon\Carbon::parse($slot['datetime'])->format('H:i');

            if (in_array($slotTime, $times, true)) {
                return $slot;
            }
        }

        return null;
    }

    private function latestOfferedSlots(\App\Models\VoiceCall $call): array
    {
        $event = $call->events()
            ->where('type', \App\Enums\VoiceEventType::ToolResult->value)
            ->latest('id')
            ->get()
            ->first(fn (\App\Models\VoiceEvent $event): bool => ($event->payload['tool'] ?? null) === 'get_available_slots');

        return $event ? ($event->payload['result']['slots'] ?? []) : [];
    }

    private function selectedOptionIndex(string $message): ?int
    {
        $normalized = $this->normalizeText($message);

        $words = [
            'primero' => 1,
            'primera' => 1,
            'segundo' => 2,
            'segunda' => 2,
            'tercero' => 3,
            'tercera' => 3,
        ];

        foreach ($words as $word => $index) {
            if (str_contains($normalized, $word)) {
                return $index;
            }
        }

        if (preg_match('/^\s*(\d{1,2})\s*$/', $message, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/\bopci[oó]n\s+(\d{1,2})\b/i', $message, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function timeCandidatesFromMessage(string $message): array
    {
        $normalized = $this->normalizeText($message);

        if (! preg_match('/\b(?:a\s+las\s+)?(\d{1,2})(?:\s*(?::|y)\s*(\d{2}))?\s*(a\s*m|p\s*m|am|pm)?\b/', $normalized, $matches)) {
            return [];
        }

        $hour = (int) $matches[1];
        $minutes = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : 0;
        $meridiem = isset($matches[3]) ? str_replace(' ', '', $matches[3]) : null;

        if ($hour < 0 || $hour > 23 || $minutes < 0 || $minutes > 59) {
            return [];
        }

        if ($meridiem === 'pm' && $hour < 12) {
            $hour += 12;
        }

        if ($meridiem === 'am' && $hour === 12) {
            $hour = 0;
        }

        $candidates = [sprintf('%02d:%02d', $hour, $minutes)];

        if (! $meridiem && $hour > 0 && $hour < 12) {
            $candidates[] = sprintf('%02d:%02d', $hour + 12, $minutes);
        }

        return array_values(array_unique($candidates));
    }

    private function normalizeText(string $text): string
    {
        return str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', '.', ','],
            ['a', 'e', 'i', 'o', 'u', 'u', '', ''],
            mb_strtolower(trim($text)),
        );
    }

    private function buildHeldSlotReply(array $slot): string
    {
        $label = $slot['label'] ?? \Carbon\Carbon::parse($slot['datetime'])->isoFormat('dddd D [de] MMMM [a las] h:mm A');

        return "Perfecto, retuve el horario {$label}. Para confirmar la cita, indícame el nombre completo del paciente.";
    }

    private function assertToolArgsAllowedByBackendHistory(\App\Models\VoiceCall $call, string $name, array $args): void
    {
        if ($name === 'hold_slot' && ! $this->wasSlotOfferedByBackend($call, $args)) {
            throw new \RuntimeException('El horario, doctor o procedimiento no proviene de get_available_slots. Debes consultar disponibilidad antes de retener.');
        }

        if ($name === 'create_appointment' && ! $this->wasHoldIssuedByBackend($call, (string) ($args['hold_token'] ?? ''))) {
            throw new \RuntimeException('El hold_token no fue emitido previamente por hold_slot.');
        }
    }

    private function wasSlotOfferedByBackend(\App\Models\VoiceCall $call, array $args): bool
    {
        $slotDatetime = (string) ($args['slot_datetime'] ?? '');
        $doctorId = (string) ($args['doctor_id'] ?? '');
        $procedureId = (string) ($args['procedure_id'] ?? '');

        if ($slotDatetime === '' || $doctorId === '' || $procedureId === '') {
            return false;
        }

        return $call->events()
            ->where('type', \App\Enums\VoiceEventType::ToolResult->value)
            ->get()
            ->contains(function (\App\Models\VoiceEvent $event) use ($slotDatetime, $doctorId, $procedureId): bool {
                if (($event->payload['tool'] ?? null) !== 'get_available_slots') {
                    return false;
                }

                foreach (($event->payload['result']['slots'] ?? []) as $slot) {
                    if ((string) ($slot['datetime'] ?? '') === $slotDatetime
                        && (string) ($slot['doctor_id'] ?? '') === $doctorId
                        && (string) ($slot['procedure_id'] ?? '') === $procedureId) {
                        return true;
                    }
                }

                return false;
            });
    }

    private function wasHoldIssuedByBackend(\App\Models\VoiceCall $call, string $holdToken): bool
    {
        if ($holdToken === '') {
            return false;
        }

        return $call->events()
            ->where('type', \App\Enums\VoiceEventType::ToolResult->value)
            ->get()
            ->contains(fn (\App\Models\VoiceEvent $event): bool => ($event->payload['tool'] ?? null) === 'hold_slot'
                && (string) ($event->payload['result']['hold_token'] ?? '') === $holdToken);
    }

    private function toolDefinitions(): array
    {
        return [
            'functionDeclarations' => [
                [
                    'name' => 'identify_patient',
                    'description' => 'Identifica un paciente por numero de telefono en formato E.164. Ejemplo: +593999999999',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'phone_e164' => [
                                'type' => 'string',
                                'description' => 'Numero de telefono del paciente en formato E.164 (ej: +593999999999)',
                            ],
                        ],
                        'required' => ['phone_e164'],
                    ],
                ],
                [
                    'name' => 'get_available_slots',
                    'description' => 'Busca horarios disponibles para agendar una cita. Devuelve slots reales del CRM con datetime, doctor_id y procedure_id. Esos valores deben usarse exactamente en hold_slot.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'procedure_name' => [
                                'type' => 'string',
                                'description' => 'Nombre del procedimiento para filtrar (ej: Limpieza, Consulta, Extraccion)',
                            ],
                            'preferred_date' => [
                                'type' => 'string',
                                'description' => 'Fecha preferida en formato YYYY-MM-DD',
                            ],
                            'preferred_period' => [
                                'type' => 'string',
                                'enum' => ['morning', 'afternoon'],
                                'description' => 'Prefiere manana o tarde',
                            ],
                            'doctor_id' => [
                                'type' => 'integer',
                                'description' => 'ID del doctor si se prefiere uno especifico',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'hold_slot',
                    'description' => 'Retiene temporalmente un horario real. Solo puedes usar slot_datetime, doctor_id y procedure_id devueltos previamente por get_available_slots.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'slot_datetime' => [
                                'type' => 'string',
                                'description' => 'Fecha y hora del slot en formato YYYY-MM-DD H:i:s (ej: 2026-07-20 10:00:00)',
                            ],
                            'doctor_id' => [
                                'type' => 'integer',
                                'description' => 'ID del doctor que atendera la cita',
                            ],
                            'procedure_id' => [
                                'type' => 'integer',
                                'description' => 'ID del procedimiento',
                            ],
                            'phone_e164' => [
                                'type' => 'string',
                                'description' => 'Numero de telefono del paciente en formato E.164',
                            ],
                        ],
                        'required' => ['slot_datetime', 'doctor_id', 'procedure_id'],
                    ],
                ],
                [
                    'name' => 'create_appointment',
                    'description' => 'Crea la cita definitiva usando un hold_token valido emitido previamente por hold_slot. El procedimiento, doctor y horario se toman del hold en Laravel.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'hold_token' => [
                                'type' => 'string',
                                'description' => 'Token obtenido de hold_slot que reserva el horario',
                            ],
                            'patient_name' => [
                                'type' => 'string',
                                'description' => 'Nombre completo del paciente',
                            ],
                            'phone_e164' => [
                                'type' => 'string',
                                'description' => 'Numero de telefono del paciente en formato E.164',
                            ],
                            'notes' => [
                                'type' => 'string',
                                'description' => 'Notas adicionales para la cita',
                            ],
                        ],
                        'required' => ['hold_token', 'patient_name', 'phone_e164'],
                    ],
                ],
                [
                    'name' => 'request_handoff',
                    'description' => 'Solicita transferencia a un agente humano cuando el paciente lo requiere o la conversacion lo amerita.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'reason' => [
                                'type' => 'string',
                                'enum' => ['emergency', 'pain', 'complaint', 'clinical_question', 'human_requested', 'tool_failure'],
                                'description' => 'Motivo de la transferencia',
                            ],
                            'summary' => [
                                'type' => 'string',
                                'description' => 'Resumen de la conversacion para el agente humano',
                            ],
                        ],
                        'required' => ['reason'],
                    ],
                ],
            ],
        ];
    }

    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
Eres Pity, asistente telefonica de una clinica odontologica llamada OdonCRM.
Tu objetivo es ayudar a pacientes a obtener informacion basica y reservar citas.
Habla de manera amable, profesional y breve, como en una llamada real.

CAPACIDADES ACTUALES DEL MVP:
- Puedes identificar pacientes, consultar disponibilidad, retener horarios, crear citas y pedir transferencia humana.
- Todavia NO puedes reprogramar ni cancelar citas directamente. Si el paciente pide reprogramar o cancelar, recopila nombre, telefono y motivo, y usa request_handoff con reason human_requested.
- Todavia NO hay multi-sede configurada. No pidas sede salvo que el paciente la mencione; si la menciona, registra el dato en notas o transfiere si es necesario.

REGLAS:
1. No inventes precios, doctores, sedes ni horarios.
2. Consulta siempre las tools del CRM cuando hables de disponibilidad o agenda.
2.1. No inventes IDs. Los valores doctor_id, procedure_id, slot_datetime y hold_token solo pueden venir de respuestas previas de las tools.
2.2. Nunca uses fechas pasadas. Si el paciente dice "lunes", "mañana" o un día ambiguo, interpreta la fecha como la próxima ocurrencia futura según la fecha actual del sistema.
3. Para identificar al paciente, usa identify_patient con el telefono actual de la llamada.
4. Si identify_patient devuelve found=false, NO bloquees la agenda. Tratalo como paciente nuevo, pide su nombre completo y continua con el telefono actual de la llamada.
5. No pidas otro numero si ya tienes el telefono actual, salvo que el paciente diga que ese numero esta mal.
6. Si el paciente escribe un numero local ecuatoriano que empieza con 09, interpreta que puede corresponder al telefono actual si los ultimos digitos coinciden.
7. Antes de crear una cita, confirma: nombre del paciente, procedimiento o especialidad, fecha, hora y doctor cuando corresponda.
7.1. Antes de consultar disponibilidad, debes tener un procedimiento o motivo claro de cita. Si el paciente dice solo "una cita", pregunta primero el motivo o procedimiento.
8. Repite fecha y hora antes de confirmar definitivamente.
9. Usa hold_slot cuando el paciente elija un horario, antes de create_appointment, copiando exactamente el slot_datetime, doctor_id y procedure_id devueltos por get_available_slots.
10. Usa create_appointment solo despues de que el paciente confirme el horario retenido, copiando exactamente el hold_token devuelto por hold_slot.
11. Si no existe disponibilidad para lo solicitado, ofrece hasta tres alternativas cercanas usando get_available_slots.
12. Si no entiendes un dato, solicita confirmacion breve.
13. Al finalizar, registra el resultado en el CRM mediante las tools disponibles y cierra cordialmente.

TRANSFERENCIA OBLIGATORIA:
- Si el paciente menciona sangrado intenso, dificultad para respirar, accidente, inflamacion severa, infeccion, fiebre o dolor insoportable, clasifica como urgente y usa request_handoff.
- Si el paciente presenta un reclamo, no discutas ni prometas compensaciones. Resume el reclamo y usa request_handoff.
- Si el paciente solicita hablar con una persona, usa request_handoff.
- Si el paciente hace una consulta clinica compleja, usa request_handoff.
- Si una tool falla repetidamente, usa request_handoff.

NO PUEDES:
- Realizar diagnosticos medicos.
- Prometer precios cerrados.
- Inventar disponibilidad si get_available_slots no devuelve horarios.
- Pedir datos clinicos sensibles que no sean necesarios para agendar.

IMPORTANTE: Si el paciente es nuevo, puedes crear la cita igual usando su nombre completo y el telefono actual de la llamada.
PROMPT;
    }
}
