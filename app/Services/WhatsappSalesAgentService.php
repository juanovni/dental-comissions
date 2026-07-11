<?php

namespace App\Services;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialPipelineStage;
use App\Events\ClosingOpportunityDetected;
use App\Models\Procedure;
use App\Models\Professional;
use App\Models\SocialComment;
use App\Models\SocialCrmSetting;
use App\Models\WhatsappMessage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappSalesAgentService
{
    private const MAX_RETRIES = 2;

    public function respond(SocialComment $comment, WhatsappMessage $message, int $retryCount = 0): array
    {
        $leadContext = $this->buildLeadContext($comment, $message);

        $localReply = $this->buildFallbackReply($leadContext);

        if ($this->isReadyToBookLocally($message->message_body ?? '', $leadContext)) {
            return $this->buildReadyToBookResponse($comment, $message, $leadContext);
        }

        if (config('services.ai.provider') === 'local') {
            $response = [
                'source' => 'fallback',
                'reply' => $localReply,
                'intent' => 'information_seeking',
                'closing_opportunity_score' => 30,
                'requires_human_handoff' => false,
                'handoff_reason' => '',
                'suggested_pipeline_stage' => 'lead',
                'clinical_safety_flag' => false,
                'appointment_candidate' => [
                    'wants_appointment' => false,
                    'preferred_date_text' => null,
                    'preferred_time_text' => null,
                ],
            ];

            $this->persistAiResponse($message, $response);

            return $response;
        }

        try {
            $aiResponse = $this->callGeminiApi($leadContext);

            $closingScore = $aiResponse['closing_opportunity_score'] ?? 0;

            $response = [
                'source' => 'ai',
                'reply' => $aiResponse['reply'] ?? $localReply,
                'intent' => $aiResponse['intent'] ?? 'information_seeking',
                'closing_opportunity_score' => $closingScore,
                'requires_human_handoff' => $aiResponse['requires_human_handoff'] ?? false,
                'handoff_reason' => $aiResponse['handoff_reason'] ?? '',
                'suggested_pipeline_stage' => $aiResponse['suggested_pipeline_stage'] ?? 'lead',
                'clinical_safety_flag' => $aiResponse['clinical_safety_flag'] ?? false,
                'appointment_candidate' => $aiResponse['appointment_candidate'] ?? [
                    'wants_appointment' => false,
                    'preferred_date_text' => null,
                    'preferred_time_text' => null,
                ],
            ];

            $intentResult = app(AppointmentIntentService::class)->analyze(
                $comment, $message,
                $response['intent'],
                $response['appointment_candidate'],
            );

            $response['appointment_candidate'] = array_merge(
                $response['appointment_candidate'],
                [
                    'preferred_date_parsed' => $intentResult['preferred_date_parsed'],
                    'preferred_time_parsed' => $intentResult['preferred_time_parsed'],
                    'intent_confidence' => $intentResult['confidence'],
                    'intent_type' => $intentResult['intent_type'],
                    'extraction_source' => $intentResult['extraction_source'],
                ],
            );

            $this->persistAiResponse($message, $response);

            if ($closingScore >= 70) {
                event(new ClosingOpportunityDetected($comment, $response));
            }

            return $response;
        } catch (ConnectionException $e) {
            if ($retryCount < self::MAX_RETRIES) {
                return $this->respond($comment, $message, $retryCount + 1);
            }

            Log::warning('WhatsappSalesAgentService: Gemini API connection failed after retries', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsappSalesAgentService: AI response failed', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);
        }

        $fallbackResponse = [
            'source' => 'fallback',
            'reply' => $localReply,
            'intent' => 'information_seeking',
            'closing_opportunity_score' => 30,
            'requires_human_handoff' => false,
            'handoff_reason' => 'ai_failure',
            'suggested_pipeline_stage' => 'lead',
            'clinical_safety_flag' => false,
        ];

        $this->persistAiResponse($message, $fallbackResponse);

        return $fallbackResponse;
    }

    private function isReadyToBookLocally(string $body, array $leadContext): bool
    {
        $normalized = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'u'],
            mb_strtolower(trim($body)),
        );

        foreach (['agendar', 'cita', 'reservar', 'programar', 'booking', 'schedule appointment'] as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        if (($leadContext['etapa_embudo'] ?? null) !== SocialPipelineStage::Appointment->value) {
            return false;
        }

        $parsed = app(AppointmentIntentService::class)->extractFromText($body);

        if (($parsed['date'] ?? null) || ($parsed['time'] ?? null)) {
            return true;
        }

        $affirmatives = [
            'si', 'si por favor', 'sí', 'sí por favor', 'ok', 'dale', 'claro',
            'de acuerdo', 'perfecto', 'quiero', 'si quiero', 'me interesa',
            'puede ser', 'ese', 'ese horario', 'ese esta bien', 'ese está bien',
            'el primero', 'la primera', 'el segundo', 'la segunda', 'el tercero', 'la tercera',
        ];

        return in_array($normalized, $affirmatives, true)
            || str_starts_with($normalized, 'si ')
            || str_starts_with($normalized, 'sí ')
            || str_starts_with($normalized, 'puede ser ');
    }

    private function buildReadyToBookResponse(
        SocialComment $comment,
        WhatsappMessage $message,
        array $leadContext,
    ): array {
        $intentResult = app(AppointmentIntentService::class)->analyze(
            $comment,
            $message,
            'appointment_interest',
            ['wants_appointment' => true, 'preferred_date_text' => null, 'preferred_time_text' => null],
        );

        $appointmentSlots = $this->resolveAppointmentSlots($comment);
        $reply = $this->formatReadyToBookReply($leadContext, $appointmentSlots);

        $response = [
            'source' => 'fallback',
            'reply' => $reply['text'],
            'intent' => 'appointment_interest',
            'closing_opportunity_score' => $reply['score'],
            'requires_human_handoff' => $reply['requires_handoff'],
            'handoff_reason' => $reply['handoff_reason'],
            'suggested_pipeline_stage' => 'appointment',
            'clinical_safety_flag' => false,
            'appointment_candidate' => [
                'wants_appointment' => true,
                'preferred_date_text' => $intentResult['preferred_date_text'],
                'preferred_time_text' => $intentResult['preferred_time_text'],
                'preferred_date_parsed' => $intentResult['preferred_date_parsed'],
                'preferred_time_parsed' => $intentResult['preferred_time_parsed'],
                'intent_confidence' => $intentResult['confidence'],
                'intent_type' => $intentResult['intent_type'],
                'extraction_source' => $intentResult['extraction_source'],
            ],
        ];

        $this->persistAiResponse($message, $response);

        if ($response['closing_opportunity_score'] >= 70) {
            event(new ClosingOpportunityDetected($comment, $response));
        }

        return $response;
    }

    public function buildLeadContext(SocialComment $comment, WhatsappMessage $message): array
    {
        $procedure = $comment->suggestedProcedure;

        $context = [
            'tipo_mensaje' => 'whatsapp',
            'nombre_paciente' => $comment->author_name ?? $comment->author_username ?? 'Paciente',
            'username' => $comment->author_username ?? '',
            'procedimiento_de_interes' => $procedure?->name ?? 'No especificado',
            'costo_procedimiento' => $procedure?->base_cost ?? 'No disponible',
            'mensaje_usuario' => $message->message_body ?? '',
            'historial_conversacion' => $this->buildRecentHistory($comment, $message),
            'etapa_embudo' => $comment->pipeline_stage?->value ?? 'lead',
            'tiene_cita_agendada' => !is_null($comment->appointment_scheduled_at),
            'cita_agendada' => $comment->appointment_scheduled_at?->format('d/m/Y H:i'),
        ];

        return $context;
    }

    public function buildFallbackReply(array $leadContext): string
    {
        $procedimiento = $leadContext['procedimiento_de_interes'] ?? 'procedimiento';
        $nombre = $leadContext['nombre_paciente'] ?? '';

        $baseMessages = [
            "Hola {$nombre}, gracias por contactarnos. El procedimiento de {$procedimiento} es uno de los mas solicitados en nuestra clinica. ¿Te gustaria agendar una valoracion gratuita para evaluar tu caso?",
            "¡Hola {$nombre}! Gracias por escribirnos. Con gusto podemos ayudarte con informacion sobre {$procedimiento}. ¿Que te gustaria saber? ¿Te gustaria agendar una cita de valoracion?",
            "Hola {$nombre}, gracias por tu interes en {$procedimiento}. En nuestra clinica ofrecemos este tratamiento con los mejores especialistas. ¿Te gustaria agendar una valoracion para que te evalue uno de nuestros doctores?",
        ];

        return $baseMessages[array_rand($baseMessages)];
    }

    public function buildFallbackReplyForDirectWhatsApp(array $leadContext): string
    {
        $procedimiento = $leadContext['procedimiento_de_interes'] ?? 'procedimiento';
        $nombre = $leadContext['nombre_paciente'] ?? '';

        $messages = [
            "Hola {$nombre}, gracias por contactarnos. El procedimiento de {$procedimiento} es uno de los mas solicitados en nuestra clinica. ¿Te gustaria agendar una valoracion gratuita?",
            "¡Hola {$nombre}! Gracias por escribirnos. Con gusto podemos ayudarte con {$procedimiento}. ¿Que te gustaria saber?",
        ];

        return $messages[array_rand($messages)];
    }

    private function buildRecentHistory(SocialComment $comment, WhatsappMessage $currentMessage): array
    {
        try {
            $recentMessages = WhatsappMessage::where('social_comment_id', $comment->id)
                ->where('id', '!=', $currentMessage->id)
                ->latest()
                ->take(5)
                ->get()
                ->reverse();
        } catch (\Exception $e) {
            return [];
        }

        $history = [];
        foreach ($recentMessages as $msg) {
            $history[] = [
                'role' => $msg->direction->value === 'incoming' ? 'user' : 'assistant',
                'content' => $msg->message_body,
                'timestamp' => $msg->created_at->toIso8601String(),
            ];
        }

        return $history;
    }

    private function callGeminiApi(array $context): array
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-2.0-flash-exp');

        $prompt = $this->buildAiPrompt($context);

        $response = Http::retry(self::MAX_RETRIES, 1000)
            ->timeout(30)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'maxOutputTokens' => 1000,
                ],
            ]);

        if (!$response->successful()) {
            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Gemini API returned status '.$response->status());
        }

        $data = $response->json();

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        $parsed = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Gemini response was not valid JSON, using fallback', [
                'text' => substr($text, 0, 500),
            ]);

            return [
                'reply' => $text,
                'intent' => 'information_seeking',
                'closing_opportunity_score' => 30,
                'requires_human_handoff' => false,
                'handoff_reason' => '',
                'suggested_pipeline_stage' => 'lead',
                'clinical_safety_flag' => false,
                'appointment_candidate' => [
                    'wants_appointment' => false,
                    'preferred_date_text' => null,
                    'preferred_time_text' => null,
                ],
            ];
        }

        return $parsed;
    }

    private function buildAiPrompt(array $context): string
    {
        $history = '';
        foreach ($context['historial_conversacion'] as $msg) {
            $role = $msg['role'] === 'user' ? 'Paciente' : 'Asistente';
            $history .= "{$role}: {$msg['content']}\n";
        }

        $tieneCita = $context['tiene_cita_agendada'] ? 'Si' : 'No';
        $citaAgendada = $context['cita_agendada'] ?: 'N/A';

        $bookingInstruction = '';
        if ($context['tiene_cita_agendada']) {
            $bookingInstruction = <<<BOOKING

INSTRUCCION ADICIONAL - RESPUESTA A PROPUESTA DE CITA:
El paciente tiene una cita PENDIENTE. Clasifica si su mensaje responde a la propuesta de cita:
- "confirmed": el paciente ACEPTA la cita (dice si, ok, confirmo, dale, esta bien, etc.)
- "rejected": el paciente RECHAZA la cita (dice no, no quiero, cancelalo, etc.)
- "modified": el paciente pide CAMBIAR fecha/hora (menciona otro dia, otra hora, etc.)
- "propose_alternatives": el paciente pide otras opciones de horario
- null: el mensaje NO es una respuesta directa a la propuesta de cita (sigue preguntando, saluda, etc.)

Incluye el campo "booking_response" en tu JSON con uno de esos valores.
BOOKING;
        }

        $bookingField = $context['tiene_cita_agendada']
            ? ',
    "booking_response": null'
            : '';

        return <<<PROMPT
Eres un asistente de ventas para una clinica dental. Debes responder preguntas sobre procedimientos dentales y ayudar a agendar citas.
Importante: NUNCA debes proporcionar diagnosticos clinicos ni recomendar tratamientos especificos. Siempre debes sugerir una valoracion presencial con un especialista.

Contexto del lead:
- Nombre del paciente: {$context['nombre_paciente']}
- Username: {$context['username']}
- Procedimiento de interes: {$context['procedimiento_de_interes']}
- Costo del procedimiento: {$context['costo_procedimiento']}
- Etapa del embudo: {$context['etapa_embudo']}
- Tiene cita agendada: {$tieneCita}
- Cita agendada: {$citaAgendada}

Mensaje del paciente: {$context['mensaje_usuario']}

Historial de la conversacion:
{$history}
{$bookingInstruction}
Responde SOLO con un JSON valido con esta estructura exacta:
{
    "reply": "tu respuesta amigable y profesional aqui",
    "intent": "appointment_interest|information_seeking|pricing_question|greeting|complaint|other",
    "closing_opportunity_score": 0-100,
    "requires_human_handoff": false,
    "handoff_reason": "solo si requiere escalamiento",
    "suggested_pipeline_stage": "new|qualified|appointment|proposal|won|lost",
    "clinical_safety_flag": false,
    "appointment_candidate": {
        "wants_appointment": false,
        "preferred_date_text": null,
        "preferred_time_text": null
    }{$bookingField}
}

NO incluyas markdown ni bloques de codigo. Solo el JSON.
PROMPT;
    }

    private function persistAiResponse(WhatsappMessage $message, array $response): void
    {
        $message->update([
            'ai_response' => $response,
            'status' => 'processed',
        ]);
    }

    private function resolveAppointmentSlots(SocialComment $comment): array
    {
        $setting = SocialCrmSetting::where('key', 'social_appointment_propose_slots')->first();

        if (!$setting || !$setting->value) {
            return [];
        }

        try {
            $doctor = $comment->suggestedDoctor;

            if ($doctor) {
                $availabilityService = app(AppointmentAvailabilityService::class);
                $slots = $availabilityService->nextAvailableSlotsForDoctor($doctor);
            } else {
                $availabilityService = app(AppointmentAvailabilityService::class);
                $slots = $availabilityService->nextAvailableSlots();
            }

            return array_map(fn ($slot) => [
                'datetime' => $slot->format('Y-m-d H:i'),
                'formatted' => $slot->isoFormat('dddd D [de] MMMM [a las] h:mm A'),
            ], $slots);
        } catch (\Exception $e) {
            Log::warning('WhatsappSalesAgentService: could not resolve appointment slots', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function formatReadyToBookReply(array $leadContext, array $appointmentSlots): array
    {
        $nombre = $leadContext['nombre_paciente'] ?? '';
        $procedimiento = $leadContext['procedimiento_de_interes'] ?? 'tratamiento';

        $score = 85;

        if (!empty($appointmentSlots)) {
            $slotsText = '';
            foreach ($appointmentSlots as $i => $slot) {
                $slotsText .= ($i + 1).". {$slot['formatted']}\n";
            }

            $text = "¡Hola {$nombre}! Claro que podemos ayudarte con el agendamiento de tu cita para {$procedimiento}. Estos son nuestros horarios disponibles:\n\n{$slotsText}\n\n¿Alguno de estos horarios te queda bien? Confirma cual y con gusto reservamos tu cita.";

            return [
                'text' => $text,
                'score' => $score,
                'requires_handoff' => false,
                'handoff_reason' => '',
            ];
        }

        $text = "¡Hola {$nombre}! Claro que podemos ayudarte a agendar una cita. ¿Que dia y horario prefieres? Por favor indicanos tu disponibilidad y te confirmamos.";

        return [
            'text' => $text,
            'score' => $score,
            'requires_handoff' => true,
            'handoff_reason' => 'no_appointment_slots_available',
        ];
    }
}
