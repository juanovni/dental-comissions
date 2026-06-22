<?php

namespace App\Services;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialCommentClassification;
use App\Enums\SocialCommentStatus;
use App\Enums\SocialReputationRisk;
use App\Models\SocialComment;
use Illuminate\Support\Str;

class SocialAutoReplyService
{
    public function shouldQueue(SocialComment $comment): bool
    {
        $settings = app(SocialCrmSettingsService::class);

        if (! $settings->autoReplyEnabled()) {
            return false;
        }

        return $this->skipReason($comment->refresh(), $settings) === null;
    }

    public function handle(SocialComment $comment): array
    {
        $comment = $comment->refresh();
        $settings = app(SocialCrmSettingsService::class);

        if (! $settings->autoReplyEnabled()) {
            return $this->skip($comment, 'auto_reply_disabled', 'Auto-respuestas desactivadas.');
        }

        $skipReason = $this->skipReason($comment, $settings);

        if ($skipReason) {
            return $this->skip($comment, $skipReason['reason'], $skipReason['notes']);
        }

        $generated = app(SocialAutoReplyMessageService::class)->generate($comment);

        if ($generated['requires_human_review']) {
            return $this->skip($comment, 'human_review_required_by_ai', 'La IA solicitó revisión humana.', $generated);
        }

        $message = trim((string) ($generated['message'] ?? ''));

        if ($message === '') {
            return $this->skip($comment, 'empty_message', 'No se generó mensaje válido.', $generated);
        }

        if ($settings->autoReplyDryRun()) {
            $comment->update([
                'auto_reply_message' => $message,
                'auto_reply_error' => null,
            ]);

            $comment->actions()->create([
                'action' => SocialCommentActionType::AutoReplyGenerated,
                'notes' => 'Respuesta automática generada en modo dry-run. No se publicó en Meta.',
                'response_text' => $message,
                'external_response' => $generated,
            ]);

            return [
                'status' => 'generated',
                'published' => false,
                'dry_run' => true,
                'message' => $message,
                'source' => $generated['source'],
            ];
        }

        $comment->increment('auto_reply_attempts');

        try {
            $response = app(MetaSocialService::class)->replyToComment($comment->fresh(), $message);
            $externalId = (string) ($response['id'] ?? '');

            $comment->fresh()->update([
                'status' => SocialCommentStatus::Responded,
                'auto_replied_at' => now(),
                'auto_reply_external_id' => $externalId !== '' ? $externalId : null,
                'auto_reply_error' => null,
                'auto_reply_message' => $message,
            ]);

            $comment->actions()->create([
                'action' => SocialCommentActionType::AutoReplySent,
                'notes' => 'Respuesta automática publicada en Meta.',
                'response_text' => $message,
                'external_response' => array_merge($generated, [
                    'meta_response' => $response,
                ]),
            ]);

            return [
                'status' => 'sent',
                'published' => true,
                'dry_run' => false,
                'message' => $message,
                'source' => $generated['source'],
                'external_id' => $externalId !== '' ? $externalId : null,
            ];
        } catch (\Throwable $e) {
            $comment->fresh()->update([
                'auto_reply_error' => $e->getMessage(),
                'auto_reply_message' => $message,
            ]);

            $comment->actions()->create([
                'action' => SocialCommentActionType::AutoReplyFailed,
                'notes' => 'Falló la publicación de respuesta automática en Meta.',
                'response_text' => $message,
                'external_response' => array_merge($generated, [
                    'error' => $e->getMessage(),
                ]),
            ]);

            return [
                'status' => 'failed',
                'published' => false,
                'dry_run' => false,
                'message' => $message,
                'source' => $generated['source'],
                'error' => $e->getMessage(),
            ];
        }
    }

    private function skipReason(SocialComment $comment, SocialCrmSettingsService $settings): ?array
    {
        if (filled($comment->auto_replied_at) || $comment->actions()->where('action', SocialCommentActionType::AutoReplySent)->exists()) {
            return ['reason' => 'already_replied', 'notes' => 'El comentario ya fue auto-respondido.'];
        }

        if ($comment->auto_reply_attempts >= $settings->autoReplyMaxAttempts()) {
            return ['reason' => 'max_attempts_reached', 'notes' => 'El comentario alcanzó el máximo de intentos de auto-respuesta.'];
        }

        if (blank($comment->external_comment_id)) {
            return ['reason' => 'missing_external_comment_id', 'notes' => 'El comentario no tiene ID externo de Meta.'];
        }

        if (! $comment->classification || ! in_array($comment->classification->value, $settings->autoReplyAllowedClassifications(), true)) {
            return ['reason' => 'classification_not_allowed', 'notes' => 'La clasificación no activa auto-respuesta.'];
        }

        if ($comment->requires_human_review) {
            return ['reason' => 'human_review_required', 'notes' => 'El comentario requiere revisión humana.'];
        }

        if (in_array($comment->reputation_risk, [SocialReputationRisk::High, SocialReputationRisk::Critical], true)) {
            return ['reason' => 'reputation_risk', 'notes' => 'El comentario tiene riesgo reputacional alto o crítico.'];
        }

        if (in_array($comment->status, [
            SocialCommentStatus::Hidden,
            SocialCommentStatus::Ignored,
            SocialCommentStatus::MarkedAsSpam,
            SocialCommentStatus::Escalated,
            SocialCommentStatus::Error,
        ], true)) {
            return ['reason' => 'status_not_allowed', 'notes' => 'El estado del comentario no permite auto-respuesta.'];
        }

        if ($this->containsSensitiveText((string) $comment->comment_text)) {
            return ['reason' => 'sensitive_text', 'notes' => 'El comentario contiene texto clínico, urgente o reputacionalmente sensible.'];
        }

        if (! $comment->social_account_id) {
            return ['reason' => 'missing_social_account', 'notes' => 'El comentario no tiene cuenta social asociada.'];
        }

        return null;
    }

    private function skip(SocialComment $comment, string $reason, string $notes, array $context = []): array
    {
        $comment->actions()->create([
            'action' => SocialCommentActionType::AutoReplySkipped,
            'notes' => $notes,
            'external_response' => array_merge($context, [
                'reason' => $reason,
            ]),
        ]);

        return [
            'status' => 'skipped',
            'published' => false,
            'reason' => $reason,
        ];
    }

    private function containsSensitiveText(string $text): bool
    {
        $normalized = Str::of($text)->lower()->ascii()->toString();

        return preg_match('/\b(dolor|sangrado|infeccion|pus|hinchado|hinchazon|trauma|golpe|urgencia|emergencia|embarazada|medicamento|alergia|demanda|denuncia|estafa|mala atencion|queja|reclamo)\b/u', $normalized) === 1;
    }
}
