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
            [
                'role' => 'user',
                'parts' => [['text' => $this->buildSystemPrompt()]],
            ],
            [
                'role' => 'model',
                'parts' => [['text' => 'Entendido. Esperando al primer mensaje del paciente.']],
            ],
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

        $this->messages[] = [
            'role' => 'user',
            'parts' => [['text' => $userMessage]],
        ];

        $toolResults = [];

        for ($i = 0; $i < 5; $i++) {
            $response = $this->callGemini();

            $text = $response['text'] ?? null;
            $functionCall = $response['function_call'] ?? null;

            if ($functionCall) {
                $result = $this->executeTool($functionCall['name'], $functionCall['args']);

                $this->sessionService->addToolCall($call, $functionCall['name'], $functionCall['args'], $result);

                $toolResults[] = [
                    'tool' => $functionCall['name'],
                    'arguments' => $functionCall['args'],
                    'result' => $result,
                ];

                $this->messages[] = [
                    'role' => 'model',
                    'parts' => [['functionCall' => ['name' => $functionCall['name'], 'args' => $functionCall['args']]]],
                ];

                $this->messages[] = [
                    'role' => 'function',
                    'parts' => [['functionResponse' => ['name' => $functionCall['name'], 'response' => ['result' => $result]]]],
                ];

                continue;
            }

            if ($text) {
                $this->messages[] = [
                    'role' => 'model',
                    'parts' => [['text' => $text]],
                ];

                $this->sessionService->addMessage($call, \App\Enums\VoiceEventType::AssistantMessage, $text);
            }

            $hasHandoff = str_contains($text ?? '', 'transferir') || str_contains($text ?? '', 'handoff');

            $isEnding = str_contains($text ?? '', 'gracias por llamar') || str_contains($text ?? '', 'que tengas buen');

            if ($hasHandoff) {
                $this->sessionService->markHandoff($call, 'human_requested');
            }

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
            'contents' => $this->messages,
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

            return ['text' => 'Disculpa, tengo problemas tecnicos. Te transfiero con un agente humano.'];
        }
    }

    private function executeTool(string $name, array $args): array
    {
        try {
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
                    'description' => 'Busca horarios disponibles para agendar una cita. Puedes filtrar por procedimiento, fecha preferida, periodo (morning/afternoon) o doctor.',
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
                    'description' => 'Retiene temporalmente un horario para evitar que otro canal lo tome mientras el paciente confirma.',
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
                    'description' => 'Crea la cita definitiva usando un hold_token valido. El hold_token se obtiene de hold_slot.',
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
                            'procedure_id' => [
                                'type' => 'integer',
                                'description' => 'ID del procedimiento (opcional si ya se definio en hold_slot)',
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
Eres Pity, una recepcionista virtual de una clinica dental llamada OdonCRM.
Tu personalidad es amable, profesional y empatica. Hablas en espanol.

INSTRUCCIONES:
- Saluda al paciente y preguntale en que puedes ayudarle.
- Para identificar al paciente, usa la herramienta identify_patient con su numero de telefono.
- Pregunta el motivo de la consulta y el procedimiento que necesita.
- Usa get_available_slots para buscar horarios disponibles.
- Ofrece maximamente 3 opciones de horario al paciente.
- Cuando el paciente elija un horario, usa hold_slot para retenerlo.
- Confirma con el paciente antes de crear la cita.
- Usa create_appointment solo cuando el paciente haya confirmado el horario.
- Despues de crear la cita, agradece al paciente y despidese amablemente.

DEBES TRANSFERIR (usa request_handoff) cuando:
- El paciente reporta dolor intenso o emergencia
- El paciente esta molesto o hizo un reclamo
- El paciente solicita explicitamente hablar con una persona
- El paciente hace una pregunta clinica compleja
- Las herramientas fallan repetidamente

NO PUEDES:
- Diagnosticar enfermedades
- Dar precios sin confirmacion
- Inventar disponibilidad si las herramientas fallan
- Pedir datos clinicos sensibles innecesarios

IMPORTANTE: Usa las herramientas disponibles cuando sea necesario. No intentes adivinar disponibilidad.
PROMPT;
    }
}
