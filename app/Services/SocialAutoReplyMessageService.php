<?php

namespace App\Services;

use App\Models\SocialComment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SocialAutoReplyMessageService
{
    public function generate(SocialComment $comment): array
    {
        $comment->loadMissing(['socialIdentity', 'socialPost.procedure', 'suggestedProcedure']);
        $variables = $this->variables($comment);
        $settings = app(SocialCrmSettingsService::class);

        if (! $settings->autoReplyUseAi()) {
            return [
                'message' => $this->templateMessage($variables),
                'source' => 'template',
                'requires_human_review' => false,
                'variables' => $variables,
            ];
        }

        try {
            $content = app(AiJsonService::class)->generate(
                $this->systemPrompt($variables),
                $this->userPrompt($comment, $variables),
            );

            $decoded = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                throw new \RuntimeException('Respuesta de IA no es JSON valido.');
            }

            return $this->validateAiResponse($decoded, $variables);
        } catch (\Throwable $e) {
            Log::warning('Error generando auto-respuesta social. Usando fallback seguro.', [
                'social_comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'message' => $this->fallbackMessage($variables),
                'source' => 'fallback',
                'requires_human_review' => false,
                'variables' => $variables,
            ];
        }
    }

    private function validateAiResponse(array $data, array $variables): array
    {
        if ((bool) ($data['requires_human_review'] ?? false)) {
            return [
                'message' => null,
                'source' => 'ai',
                'requires_human_review' => true,
                'variables' => $variables,
            ];
        }

        $message = trim((string) ($data['message'] ?? ''));

        if (Str::upper($message) === 'HUMAN_REVIEW_REQUIRED') {
            return [
                'message' => null,
                'source' => 'ai',
                'requires_human_review' => true,
                'variables' => $variables,
            ];
        }

        if (! $this->isSafeMessage($message, $variables)) {
            return [
                'message' => $this->fallbackMessage($variables),
                'source' => 'fallback',
                'requires_human_review' => false,
                'variables' => $variables,
            ];
        }

        return [
            'message' => $message,
            'source' => 'ai',
            'requires_human_review' => false,
            'variables' => $variables,
        ];
    }

    private function isSafeMessage(string $message, array $variables): bool
    {
        if ($message === '') {
            return false;
        }

        if (! str_contains($message, $this->mainLink($variables))) {
            return false;
        }

        if (! str_contains($message, $this->render(app(SocialCrmSettingsService::class)->autoReplyHeaderTemplate(), $variables))) {
            return false;
        }

        $normalized = Str::of($message)->lower()->ascii()->toString();

        if (preg_match('/(\$|usd|dolar|precio exacto|cuesta\s+\d|sale\s+\d|promocion\s+de\s+\d)/u', $normalized) === 1) {
            return false;
        }

        return preg_match('/\b(diagnostico|te recomiendo|recomendamos hacer|garantizamos|resultado garantizado|cura|tratamiento indicado)\b/u', $normalized) !== 1;
    }

    private function templateMessage(array $variables): string
    {
        $settings = app(SocialCrmSettingsService::class);

        return trim($this->render($settings->autoReplyHeaderTemplate(), $variables))
            ."\n\n"
            .trim($this->render($settings->autoReplyTemplate(), $variables));
    }

    private function fallbackMessage(array $variables): string
    {
        return $this->templateMessage($variables);
    }

    private function variables(SocialComment $comment): array
    {
        $settings = app(SocialCrmSettingsService::class);
        $conversion = app(SocialConversionService::class);
        $smartLink = $conversion->smartLink($comment);
        $whatsappLink = $conversion->whatsappLink($comment) ?? '';
        $token = $comment->refresh()->tracking_token;

        return [
            'empresa' => $settings->autoReplyCompanyName(),
            'smart_link' => $smartLink,
            'whatsapp_link' => $whatsappLink,
            'tracking_token' => (string) $token,
            'procedure_name' => $this->procedureName($comment),
            'lead_first_name' => $this->leadFirstName($comment) ?? '',
            'main_link' => $settings->autoReplyUseSmartLink() ? $smartLink : $whatsappLink,
        ];
    }

    private function mainLink(array $variables): string
    {
        return (string) ($variables['main_link'] ?: $variables['smart_link']);
    }

    private function render(string $template, array $variables): string
    {
        return strtr($template, [
            '{empresa}' => (string) $variables['empresa'],
            '{smart_link}' => (string) $variables['smart_link'],
            '{whatsapp_link}' => (string) $variables['whatsapp_link'],
            '{tracking_token}' => (string) $variables['tracking_token'],
            '{procedure_name}' => (string) $variables['procedure_name'],
            '{lead_first_name}' => (string) $variables['lead_first_name'],
        ]);
    }

    private function systemPrompt(array $variables): string
    {
        return <<<PROMPT
Eres asistente comercial de una clinica dental.

Redacta una respuesta publica breve para un comentario en redes sociales.

Reglas estrictas:
- Responde solo JSON valido con las claves: message, requires_human_review.
- Usa siempre esta cabecera exacta: "{$this->render(app(SocialCrmSettingsService::class)->autoReplyHeaderTemplate(), $variables)}".
- No des diagnostico.
- No menciones precios.
- No prometas resultados.
- No recomiendes tratamientos clinicos.
- No digas que eres IA.
- Usa tono humano, amable y sutil.
- Maximo 2 frases despues de la cabecera.
- Incluye exactamente este link: {$this->mainLink($variables)}.
- Si el comentario es clinico, urgente, queja o sensible, devuelve message: "HUMAN_REVIEW_REQUIRED" y requires_human_review: true.
PROMPT;
    }

    private function userPrompt(SocialComment $comment, array $variables): string
    {
        return <<<PROMPT
Comentario del usuario:
"{$comment->comment_text}"

Tratamiento sugerido:
"{$variables['procedure_name']}"

Nombre del lead:
"{$variables['lead_first_name']}"
PROMPT;
    }

    private function procedureName(SocialComment $comment): string
    {
        return $comment->suggestedProcedure?->name
            ?: $comment->socialPost?->procedure?->name
            ?: 'valoracion dental';
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
}
