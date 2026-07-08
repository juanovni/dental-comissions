<?php

namespace App\Services;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialCommentClassification;
use App\Enums\SocialCommentStatus;
use App\Enums\SocialPriority;
use App\Enums\SocialReputationRisk;
use App\Enums\SocialResponseChannel;
use App\Enums\SocialSentiment;
use App\Enums\SocialSuggestedAction;
use App\Models\SocialComment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SocialCommentClassificationService
{
    public function classify(SocialComment $comment): SocialComment
    {
        try {
            $result = $this->classifyWithAi($comment);
        } catch (\Throwable $e) {
            Log::warning('Error clasificando comentario social con IA. Usando fallback local.', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);

            $result = $this->classifyLocally($comment);
            $result['reason'] .= ' Fallback local usado por error de IA.';
        }

        $validated = $this->validateResult($result);
        $status = $validated['requires_human_review']
            ? SocialCommentStatus::ReviewRequired
            : SocialCommentStatus::Classified;

        $procedureId = in_array($validated['classification'], ['sales_lead', 'commercial_question'])
            ? $this->resolveProcedureId($validated['suggested_procedure_code'] ?? null)
            : null;

        $comment->update([
            'classification' => $validated['classification'],
            'sentiment' => $validated['sentiment'],
            'priority' => $validated['priority'],
            'reputation_risk' => $validated['reputation_risk'],
            'status' => $status,
            'suggested_action' => $validated['suggested_action'],
            'response_channel' => $validated['response_channel'],
            'suggested_reply' => $validated['suggested_reply'],
            'suggested_procedure_id' => $procedureId,
            'requires_human_review' => $validated['requires_human_review'],
            'ai_reason' => $validated['reason'],
            'ai_response' => $validated,
            'processed_at' => now(),
        ]);

        $comment->actions()->create([
            'action' => SocialCommentActionType::Classify,
            'notes' => 'Comentario clasificado automaticamente.',
            'external_response' => $validated,
        ]);

        return $comment->refresh();
    }

    private function classifyWithAi(SocialComment $comment): array
    {
        $content = app(AiJsonService::class)->generate($this->systemPrompt(), $this->userPrompt($comment));
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw new \RuntimeException('Respuesta de IA no es JSON valido.');
        }

        return $decoded;
    }

    private function classifyLocally(SocialComment $comment): array
    {
        $text = Str::of($comment->comment_text)->lower()->ascii()->toString();

        if (str_contains($text, 'http') || str_contains($text, 'gana dinero') || str_contains($text, 'promociona')) {
            return $this->result(
                SocialCommentClassification::Spam,
                SocialSentiment::Negative,
                SocialPriority::High,
                SocialReputationRisk::Low,
                SocialSuggestedAction::MarkAsSpam,
                SocialResponseChannel::NoResponse,
                '',
                true,
                'El comentario parece spam o promocion externa.',
            );
        }

        if (preg_match('/\b(estafa|ladrones|idiotas|mierda|basura|asco)\b/u', $text) === 1) {
            return $this->result(
                SocialCommentClassification::Offensive,
                SocialSentiment::Negative,
                SocialPriority::High,
                SocialReputationRisk::High,
                SocialSuggestedAction::Review,
                SocialResponseChannel::NoResponse,
                '',
                true,
                'El comentario contiene lenguaje ofensivo o agresivo.',
            );
        }

        if (preg_match('/\b(denuncia|demandar|abogado|legal|ministerio|juicio)\b/u', $text) === 1) {
            return $this->result(
                SocialCommentClassification::LegalSensitive,
                SocialSentiment::Negative,
                SocialPriority::Critical,
                SocialReputationRisk::Critical,
                SocialSuggestedAction::Escalate,
                SocialResponseChannel::Both,
                'Hola, lamentamos lo ocurrido. Por favor escribenos por mensaje privado para revisar tu caso con prioridad.',
                true,
                'El comentario menciona un tema legal o una posible denuncia.',
            );
        }

        if (preg_match('/\b(dolor|embarazada|medicamento|tomar|sangrado|infeccion|urgencia)\b/u', $text) === 1) {
            return $this->result(
                SocialCommentClassification::MedicalSensitive,
                SocialSentiment::Neutral,
                SocialPriority::High,
                SocialReputationRisk::Medium,
                SocialSuggestedAction::Review,
                SocialResponseChannel::Private,
                'Hola, para orientarte correctamente es mejor revisarlo directamente con un profesional. Escribenos por privado o WhatsApp para ayudarte.',
                true,
                'El comentario contiene una consulta medica sensible.',
            );
        }

        if (preg_match('/\b(mala atencion|mal servicio|esperando|no contestan|cobraron|reclamo|pesimo|pesima)\b/u', $text) === 1) {
            return $this->result(
                SocialCommentClassification::Complaint,
                SocialSentiment::Negative,
                SocialPriority::High,
                SocialReputationRisk::High,
                SocialSuggestedAction::Escalate,
                SocialResponseChannel::Both,
                'Hola, lamentamos tu experiencia. Por favor escribenos por privado para revisar lo ocurrido y ayudarte.',
                true,
                'El comentario expresa una queja real sobre la experiencia del cliente.',
            );
        }

        if (preg_match('/\b(precio|costo|cuanto|info|informacion|cita|turno|agenda|ubicacion|horario|whatsapp)\b/u', $text) === 1) {
            return $this->result(
                SocialCommentClassification::SalesLead,
                SocialSentiment::Neutral,
                SocialPriority::Medium,
                SocialReputationRisk::Low,
                SocialSuggestedAction::ReplyAndRouteToWhatsapp,
                SocialResponseChannel::Public,
                'Hola, con gusto te ayudamos. Escribenos por WhatsApp para darte informacion personalizada y revisar disponibilidad.',
                false,
                'El comentario muestra interes comercial o intencion de agendar.',
            );
        }

        if (preg_match('/\b(excelente|gracias|recomendado|recomiendo|buena atencion|me encanto)\b/u', $text) === 1) {
            return $this->result(
                SocialCommentClassification::Positive,
                SocialSentiment::Positive,
                SocialPriority::Low,
                SocialReputationRisk::Low,
                SocialSuggestedAction::ThankUser,
                SocialResponseChannel::Public,
                'Muchas gracias por tu comentario. Nos alegra saber que tu experiencia fue positiva.',
                false,
                'El comentario es positivo o testimonial.',
            );
        }

        return $this->result(
            SocialCommentClassification::Normal,
            SocialSentiment::Neutral,
            SocialPriority::Low,
            SocialReputationRisk::Low,
            SocialSuggestedAction::Ignore,
            SocialResponseChannel::NoResponse,
            '',
            false,
            'No se detecta riesgo ni intencion comercial clara.',
        );
    }

    private function validateResult(array $data): array
    {
        $classification = $this->enumValue(SocialCommentClassification::class, $data['classification'] ?? null, SocialCommentClassification::NeedsHumanReview);
        $sentiment = $this->enumValue(SocialSentiment::class, $data['sentiment'] ?? null, SocialSentiment::Neutral);
        $priority = $this->enumValue(SocialPriority::class, $data['priority'] ?? null, SocialPriority::Medium);
        $risk = $this->enumValue(SocialReputationRisk::class, $data['reputation_risk'] ?? null, SocialReputationRisk::Medium);
        $action = $this->enumValue(SocialSuggestedAction::class, $data['suggested_action'] ?? null, SocialSuggestedAction::Review);
        $channel = $this->enumValue(SocialResponseChannel::class, $data['response_channel'] ?? null, SocialResponseChannel::NoResponse);

        $requiresReview = (bool) ($data['requires_human_review'] ?? false);

        if (in_array($priority, [SocialPriority::High, SocialPriority::Critical], true)
            || in_array($risk, [SocialReputationRisk::High, SocialReputationRisk::Critical], true)
        ) {
            $requiresReview = true;
        }

        $procedureCode = is_string($data['suggested_procedure_code'] ?? null)
            ? $data['suggested_procedure_code']
            : null;

        return [
            'classification' => $classification->value,
            'sentiment' => $sentiment->value,
            'priority' => $priority->value,
            'reputation_risk' => $risk->value,
            'suggested_action' => $action->value,
            'response_channel' => $channel->value,
            'suggested_reply' => (string) ($data['suggested_reply'] ?? ''),
            'requires_human_review' => $requiresReview,
            'reason' => (string) ($data['reason'] ?? 'Clasificacion sin motivo especificado.'),
            'suggested_procedure_code' => $procedureCode,
        ];
    }

    /**
     * @template T of \BackedEnum
     *
     * @param  class-string<T>  $enum
     * @param  T  $fallback
     * @return T
     */
    private function enumValue(string $enum, mixed $value, \BackedEnum $fallback): \BackedEnum
    {
        if (! is_string($value)) {
            return $fallback;
        }

        return $enum::tryFrom($value) ?? $fallback;
    }

    private function resolveProcedureId(?string $code): ?int
    {
        if (! $code || $code === 'null') {
            return null;
        }

        $procedure = \App\Models\Procedure::where('code', $code)
            ->where('is_active', true)
            ->first();

        return $procedure?->id;
    }

    private function result(
        SocialCommentClassification $classification,
        SocialSentiment $sentiment,
        SocialPriority $priority,
        SocialReputationRisk $risk,
        SocialSuggestedAction $action,
        SocialResponseChannel $channel,
        string $reply,
        bool $requiresReview,
        string $reason,
    ): array {
        return [
            'classification' => $classification->value,
            'sentiment' => $sentiment->value,
            'priority' => $priority->value,
            'reputation_risk' => $risk->value,
            'suggested_action' => $action->value,
            'response_channel' => $channel->value,
            'suggested_reply' => $reply,
            'requires_human_review' => $requiresReview,
            'reason' => $reason,
            'suggested_procedure_code' => null,
        ];
    }

    private function userPrompt(SocialComment $comment): string
    {
        $postCaption = $comment->socialPost?->caption ?: 'Sin texto de publicacion';
        $platform = $comment->platform->value;
        $proceduresContext = $this->buildProceduresContext();

        return <<<PROMPT
Plataforma: {$platform}
Publicacion: {$postCaption}
Autor: {$comment->author_name} / {$comment->author_username}
Comentario: {$comment->comment_text}

Procedimientos disponibles en la clinica:
{$proceduresContext}
PROMPT;
    }

    private function buildProceduresContext(): string
    {
        $procedures = \App\Models\Procedure::where('is_active', true)
            ->select('id', 'name', 'code', 'category')
            ->get();

        if ($procedures->isEmpty()) {
            return '(No hay procedimientos configurados)';
        }

        return $procedures
            ->map(fn ($p) => "- code: {$p->code} | nombre: {$p->name} | categoria: {$p->category}")
            ->implode("\n");
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Eres un especialista en reputacion digital y account management para una clinica dental.

Debes clasificar comentarios de Facebook e Instagram para proteger la reputacion de la clinica y detectar oportunidades comerciales sin automatizar acciones sensibles.

Retorna SOLO JSON valido con esta estructura exacta:
{
  "classification": "sales_lead",
  "sentiment": "neutral",
  "priority": "medium",
  "reputation_risk": "low",
  "suggested_action": "reply_and_route_to_whatsapp",
  "response_channel": "public",
  "suggested_reply": "respuesta breve y editable",
  "requires_human_review": false,
  "reason": "motivo breve",
  "suggested_procedure_code": "ORT002"
}

Valores permitidos:
- classification: normal, sales_lead, commercial_question, complaint, negative_opinion, spam, offensive, positive, medical_sensitive, legal_sensitive, needs_human_review
- sentiment: positive, neutral, negative, mixed
- priority: low, medium, high, critical
- reputation_risk: low, medium, high, critical
- suggested_action: reply, reply_and_route_to_whatsapp, hide, review, ignore, mark_as_spam, escalate, thank_user
- response_channel: public, private, both, no_response
- suggested_procedure_code: usa el code exacto de la lista de procedimientos disponibles si el comentario menciona o consulta sobre un procedimiento especifico. Si no esta claro o es una pregunta general, usa null.

Reglas:
- Quejas, insultos, temas medicos sensibles, amenazas, asuntos legales o riesgo reputacional high/critical siempre requieren revision humana.
- No sugieras eliminar comentarios.
- No des diagnosticos ni recomendaciones medicas.
- No ocultes quejas legitimas por defecto; escala y sugiere respuesta cuidadosa.
- Los leads comerciales o preguntas de precio/cita/ubicacion deben derivarse a WhatsApp cuando convenga.
- Las respuestas sugeridas deben ser profesionales, amables y breves.
- Si el comentario menciona un procedimiento dental (ej: "ortodoncia", "blanqueamiento", "implante"), asigna el suggested_procedure_code correspondiente.
PROMPT;
    }
}
