<?php

namespace App\Services;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\SocialCommentActionType;
use App\Events\ClosingOpportunityDetected;
use App\Models\SocialComment;
use App\Models\WhatsappMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsappSalesAgentService
{
    public function respond(SocialComment $comment, WhatsappMessage $message): array
    {
        $comment->loadMissing(['socialPost', 'suggestedProcedure', 'socialIdentity.patient', 'linkEvents']);

        $slotsString = '';

        if (app(SocialCrmSettingsService::class)->appointmentProposeSlots()) {
            $slots = app(AppointmentAvailabilityService::class)->nextAvailableSlots();
            $slotsString = app(AppointmentAvailabilityService::class)->formatSlotsForPrompt($slots);
        }

        try {
            $content = app(AiJsonService::class)->generate(
                $this->systemPrompt($slotsString),
                $this->userPrompt($comment, $message),
            );

            $decoded = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                throw new \RuntimeException('Respuesta de IA no es JSON valido.');
            }

            $response = $this->validateResponse($decoded, $comment, $message);
        } catch (\Throwable $e) {
            Log::warning('Error generando respuesta de WhatsApp Sales Agent. Usando fallback local.', [
                'social_comment_id' => $comment->id,
                'whatsapp_message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);

            $response = $this->fallbackResponse($comment, $message);
        }

        $selectedSlot = $this->matchSelectedSlot($message->message_body, $slots ?? []);

        if ($selectedSlot) {
            $response['appointment_candidate'] = [
                'wants_appointment' => true,
                'preferred_date_text' => $selectedSlot->format('Y-m-d g:i A'),
                'preferred_time_text' => $selectedSlot->format('g:i A'),
            ];
            $response['requires_human_handoff'] = false;
        }

        $response = $this->handleAppointmentCandidate($comment, $response);

        if ($selectedSlot && ($response['appointment_candidate']['wants_appointment'] ?? false)) {
            $slotLabel = app(AppointmentAvailabilityService::class)->formatSlotsForPrompt([$selectedSlot]);
            $response['reply'] = "Perfecto, he agendado tu cita para {$slotLabel}. Te confirmaremos los detalles por este medio.";
        } elseif ($slotsString !== '' && ! str_contains($response['reply'], "\nTenemos disponible:")) {
            $response['reply'] .= "\n\nTenemos disponible:\n{$slotsString}\n\n¿Cuál te queda mejor?";
        }

        $message->update(['ai_response' => $response]);

        $comment->actions()->create([
            'action' => SocialCommentActionType::WhatsappSalesAgent,
            'notes' => 'Respuesta contextual generada para WhatsApp.',
            'external_response' => array_merge($response, [
                'whatsapp_message_id' => $message->id,
            ]),
        ]);

        if ($response['requires_human_handoff']) {
            $alert = app(SocialLeadAlertService::class)->createAlert($comment->refresh(), 'closing_opportunity', 'danger', [
                'intent' => $response['intent'],
                'closing_opportunity_score' => $response['closing_opportunity_score'],
                'handoff_reason' => $response['handoff_reason'],
                'whatsapp_message_id' => $message->id,
                'tracking_token' => $comment->tracking_token,
            ]);

            ClosingOpportunityDetected::dispatch($comment->refresh(), $response, $alert);
        }

        app(SocialPipelineAutomationService::class)->applyAgentResponse($comment->refresh(), $response);

        return $response;
    }

    private function handleAppointmentCandidate(SocialComment $comment, array $response): array
    {
        $candidate = $response['appointment_candidate'] ?? [];

        if (! ($candidate['wants_appointment'] ?? false)) {
            return $response;
        }

        $settings = app(SocialCrmSettingsService::class);

        if ($settings->appointmentAutoConfirm() && ! empty($candidate['preferred_date_text'])) {
            try {
                $parsed = Carbon::parse($candidate['preferred_date_text']);

                app(AppointmentCreationService::class)->createFromSocialLead($comment, [
                    'scheduled_at' => $parsed,
                    'source' => AppointmentSource::WhatsappAi,
                    'status' => AppointmentStatus::Scheduled,
                    'duration_minutes' => $settings->appointmentSlotDuration(),
                    'notes' => 'Cita creada automaticamente desde WhatsApp Sales Agent.',
                    'created_by' => null,
                    'metadata' => [
                        'auto_created' => true,
                        'social_comment_id' => $comment->id,
                        'original_reply' => $response['reply'] ?? null,
                    ],
                ]);

                $response['reply'] .= "\n\nListo, he agendado tu cita. Te confirmaremos los detalles por este medio.";
                $response['handoff_reason'] = 'Cita creada automaticamente.';
            } catch (\Throwable $e) {
                Log::warning('Error al crear cita automatica desde WhatsApp Sales Agent.', [
                    'social_comment_id' => $comment->id,
                    'error' => $e->getMessage(),
                ]);

                $response['requires_human_handoff'] = true;
                $response['handoff_reason'] = 'El paciente eligio un horario pero ocurrio un error al crear la cita automaticamente.';
            }
        } else {
            $response['requires_human_handoff'] = true;
            $response['handoff_reason'] = 'El paciente mostro interes en agendar una cita.';
        }

        return $response;
    }

    private function matchSelectedSlot(string $userMessage, array $slots): ?Carbon
    {
        if (empty($slots)) {
            return null;
        }

        $text = Str::of($userMessage)->lower()->ascii()->toString();

        foreach ($slots as $slot) {
            $formats = [
                strtolower($slot->format('g:i')),
                strtolower($slot->format('H:i')),
                strtolower($slot->format('g:i A')),
            ];

            foreach ($formats as $format) {
                if (str_contains($text, $format)) {
                    return $slot;
                }
            }
        }

        return null;
    }

    private function validateResponse(array $data, SocialComment $comment, WhatsappMessage $message): array
    {
        $fallback = $this->fallbackResponse($comment, $message);
        $reply = trim((string) ($data['reply'] ?? ''));

        if ($reply === '') {
            return $fallback;
        }

        $score = max(0, min(100, (int) ($data['closing_opportunity_score'] ?? $fallback['closing_opportunity_score'])));
        $clinicalSafetyFlag = (bool) ($data['clinical_safety_flag'] ?? $fallback['clinical_safety_flag']);
        $requiresHandoff = (bool) ($data['requires_human_handoff'] ?? $fallback['requires_human_handoff']);

        if ($clinicalSafetyFlag || $score >= 75) {
            $requiresHandoff = true;
        }

        return [
            'reply' => $reply,
            'intent' => $this->cleanIntent($data['intent'] ?? $fallback['intent']),
            'closing_opportunity_score' => $score,
            'requires_human_handoff' => $requiresHandoff,
            'handoff_reason' => (string) ($data['handoff_reason'] ?? $fallback['handoff_reason']),
            'suggested_pipeline_stage' => (string) ($data['suggested_pipeline_stage'] ?? $fallback['suggested_pipeline_stage']),
            'clinical_safety_flag' => $clinicalSafetyFlag,
            'appointment_candidate' => is_array($data['appointment_candidate'] ?? null)
                ? $data['appointment_candidate']
                : $fallback['appointment_candidate'],
            'source' => 'ai',
        ];
    }

    private function fallbackResponse(SocialComment $comment, WhatsappMessage $message): array
    {
        $treatment = $this->treatmentName($comment);
        $leadName = $this->leadFirstName($comment);
        $analysis = $this->localIntent($message->message_body);
        $greeting = $leadName ? "Hola {$leadName}." : 'Hola.';

        $invitations = [
            'Si gustas, puedo ayudarte a coordinar una valoracion sin costo.',
            'Cuando quieras, podemos agendar una cita para revisarlo.',
            'Quedo atento si prefieres agendar una valoracion.',
            'Por cierto, si te interesa, podemos coordinar una cita para evaluarlo.',
        ];

        $reply = "{$greeting} Vi que te intereso el contenido de {$treatment}. "
            .'Te podemos ayudar a coordinar una valoracion para que el equipo clinico revise tu caso y te explique opciones con claridad.';

        if ($analysis['clinical_safety_flag']) {
            $reply = "{$greeting} Gracias por contarnos. Para orientarte de forma responsable, prefiero que nuestro equipo clinico revise tu caso directamente. Te ayudamos a coordinar una valoracion lo antes posible.";
        } elseif ($analysis['requires_human_handoff']) {
            $reply .= ' '.($invitations[array_rand($invitations)]);
        }

        return [
            'reply' => $reply,
            'intent' => $analysis['intent'],
            'closing_opportunity_score' => $analysis['closing_opportunity_score'],
            'requires_human_handoff' => $analysis['requires_human_handoff'],
            'handoff_reason' => $analysis['handoff_reason'],
            'suggested_pipeline_stage' => $analysis['requires_human_handoff'] ? 'appointment' : 'qualified',
            'clinical_safety_flag' => $analysis['clinical_safety_flag'],
            'appointment_candidate' => [
                'wants_appointment' => in_array($analysis['intent'], ['appointment_interest', 'ready_to_book'], true),
                'preferred_date_text' => null,
                'preferred_time_text' => null,
            ],
            'source' => 'fallback',
        ];
    }

    private function localIntent(string $text): array
    {
        $normalized = Str::of($text)->lower()->ascii()->toString();
        $clinical = preg_match('/\b(dolor|sangrado|infeccion|embarazada|medicamento|alergia|trauma|urgencia)\b/u', $normalized) === 1;
        $ready = preg_match('/\b(agendar|agenda|cita|turno|horario|manana|hoy|disponibilidad|reservar)\b/u', $normalized) === 1;
        $price = preg_match('/\b(precio|costo|cuanto|valor|presupuesto)\b/u', $normalized) === 1;

        if ($clinical) {
            return [
                'intent' => 'medical_sensitive',
                'closing_opportunity_score' => 90,
                'requires_human_handoff' => true,
                'handoff_reason' => 'El paciente menciona una condicion clinica sensible.',
                'clinical_safety_flag' => true,
            ];
        }

        if ($ready) {
            return [
                'intent' => 'ready_to_book',
                'closing_opportunity_score' => 85,
                'requires_human_handoff' => true,
                'handoff_reason' => 'El paciente muestra intencion de agendar.',
                'clinical_safety_flag' => false,
            ];
        }

        if ($price) {
            return [
                'intent' => 'price_question',
                'closing_opportunity_score' => 60,
                'requires_human_handoff' => false,
                'handoff_reason' => '',
                'clinical_safety_flag' => false,
            ];
        }

        return [
            'intent' => 'general_interest',
            'closing_opportunity_score' => 40,
            'requires_human_handoff' => false,
            'handoff_reason' => '',
            'clinical_safety_flag' => false,
        ];
    }

    private function treatmentName(SocialComment $comment): string
    {
        return $comment->suggestedProcedure?->name
            ?: $comment->socialPost?->procedure?->name
            ?: $this->treatmentFromCaption($comment)
            ?: 'una valoracion dental';
    }

    private function treatmentFromCaption(SocialComment $comment): ?string
    {
        $text = Str::of((string) $comment->socialPost?->caption)->lower()->ascii()->toString();

        return match (true) {
            str_contains($text, 'implante') => 'implantes dentales',
            str_contains($text, 'ortodoncia') || str_contains($text, 'alineador') => 'ortodoncia',
            str_contains($text, 'limpieza') => 'limpieza dental',
            str_contains($text, 'diseno') || str_contains($text, 'sonrisa') => 'diseno de sonrisa',
            default => null,
        };
    }

    private function leadFirstName(SocialComment $comment): ?string
    {
        $name = trim((string) ($comment->socialIdentity?->display_name ?: $comment->author_name));

        if ($name === '' || str_starts_with($name, '@') || preg_match('/[0-9_]/', $name)) {
            return null;
        }

        $firstName = Str::of($name)->squish()->explode(' ')->first();

        return is_string($firstName) && strlen($firstName) >= 2 ? $firstName : null;
    }

    private function cleanIntent(mixed $intent): string
    {
        $intent = is_string($intent) ? $intent : 'general_interest';

        return in_array($intent, [
            'general_interest',
            'price_question',
            'appointment_interest',
            'ready_to_book',
            'objection_price',
            'objection_time',
            'medical_sensitive',
            'not_interested',
            'unknown',
        ], true) ? $intent : 'unknown';
    }

    private function userPrompt(SocialComment $comment, WhatsappMessage $message): string
    {
        $events = $comment->linkEvents()
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($event): string => "- {$event->event_type} ({$event->created_at?->diffForHumans()})")
            ->implode("\n");

        return "Lead: ".($comment->author_name ?: $comment->author_username ?: 'Sin nombre')."\n"
            .'Tratamiento: '.$this->treatmentName($comment)."\n"
            .'Red social: '.$comment->platform->label()."\n"
            .'Comentario original: '.$comment->comment_text."\n"
            .'Publicacion: '.($comment->socialPost?->caption ?: 'Sin caption')."\n"
            .'Token: '.($comment->tracking_token ?: 'Sin token')."\n"
            .'Interest score: '.(int) $comment->interest_score."\n"
            .'Recent engagement score: '.(int) $comment->recent_engagement_score."\n"
            .'Eventos recientes: '.($events ?: 'Sin eventos recientes')."\n"
            .'Mensaje WhatsApp: '.$message->message_body;
    }

    private function systemPrompt(string $availableSlots = ''): string
    {
        if ($availableSlots !== '') {
            $appointmentSection = <<<SLOTS
Tenemos estos horarios disponibles actualmente:
{$availableSlots}

Al final de tu respuesta DEBES incluir UNA frase natural invitando
a agendar una valoracion, sin presionar.
Ejemplos:
- "¿Te gustaria agendar una valoracion?"
- "Podemos agendar una cita para revisarlo."

Si el paciente elige un horario, marca en appointment_candidate:
wants_appointment = true, preferred_date_text = la fecha y hora
que eligio (ej: "Manana 10:00 AM").
SLOTS;
        } else {
            $appointmentSection = <<<'NO_SLOTS'
Al final de tu respuesta DEBES incluir UNA frase natural invitando
a agendar una valoracion, sin presionar y sin repetir exactamente
la misma redaccion.
Ejemplos:
- "Si gustas, puedo ayudarte a coordinar una valoracion sin costo."
- "Cuando quieras, podemos agendar una cita para revisarlo."
- "Quedo atento si prefieres agendar una valoracion."
- "Por cierto, si te interesa, podemos coordinar una cita para evaluarlo."
NO uses frases de urgencia ni inventes horarios.
NO_SLOTS;
        }

        return <<<PROMPT
Eres la Coordinadora de Pacientes de una clinica dental.

Tu tono debe ser profesional, empatico, claro y experto.
No preguntes "En que puedo ayudarte?" si ya existe contexto del lead.
Debes saludar usando el interes detectado, por ejemplo: "Vi que te intereso el video de [Tratamiento]...".

No diagnostiques.
No indiques tratamientos definitivos.
No prometas resultados.
No des precios definitivos.
No reemplaces al odontologo.

Tu objetivo es orientar al paciente y facilitar una valoracion.
Si detectas intencion de agenda, marca requires_human_handoff=true.
Si detectas dolor, sangrado, infeccion, embarazo, medicamento, alergia, trauma o urgencia, marca clinical_safety_flag=true y requires_human_handoff=true.

{$appointmentSection}

Retorna SOLO JSON valido con esta estructura exacta:
{
  "reply": "respuesta breve y contextual",
  "intent": "general_interest",
  "closing_opportunity_score": 0,
  "requires_human_handoff": false,
  "handoff_reason": "",
  "suggested_pipeline_stage": "qualified",
  "clinical_safety_flag": false,
  "appointment_candidate": {
    "wants_appointment": false,
    "preferred_date_text": null,
    "preferred_time_text": null
  }
}

Valores permitidos para intent: general_interest, price_question, appointment_interest, ready_to_book, objection_price, objection_time, medical_sensitive, not_interested, unknown.
PROMPT;
    }
}
