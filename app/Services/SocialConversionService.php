<?php

namespace App\Services;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPipelineStage;
use App\Models\Patient;
use App\Models\SocialComment;
use App\Models\SocialIdentity;
use App\Models\WhatsappMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SocialConversionService
{
    public function generateTrackingToken(SocialComment $comment): string
    {
        if (filled($comment->tracking_token)) {
            return $comment->tracking_token;
        }

        do {
            $token = 'DNT-'.Str::upper(Str::random(5));
        } while (SocialComment::where('tracking_token', $token)->exists());

        $comment->update([
            'tracking_token' => $token,
            'conversion_status' => SocialConversionStatus::TokenGenerated,
            'pipeline_stage' => SocialPipelineStage::Qualified,
        ]);

        $comment->actions()->create([
            'action' => SocialCommentActionType::GenerateWhatsappToken,
            'notes' => 'Token de rastreo generado. Lead movido a calificado.',
            'external_response' => ['tracking_token' => $token],
        ]);

        app(SocialLeadScoringService::class)->scoreTokenGenerated($comment->refresh());

        return $token;
    }

    public function whatsappLink(SocialComment $comment): ?string
    {
        $businessPhone = $this->normalizePhone((string) config('services.whatsapp.business_phone'));

        if (blank($businessPhone)) {
            return null;
        }

        $token = $this->generateTrackingToken($comment);
        $message = "Hola, vengo de redes sociales. Mi codigo es {$token}.";

        return 'https://wa.me/'.$businessPhone.'?text='.rawurlencode($message);
    }

    public function smartLink(SocialComment $comment): string
    {
        $token = $this->generateTrackingToken($comment);

        return route('social-smart-link.show', ['trackingToken' => $token]);
    }

    public function instagramReplyText(SocialComment $comment): string
    {
        $token = $this->generateTrackingToken($comment);
        $link = $this->whatsappLink($comment) ?? '';
        $smartLink = $this->smartLink($comment);
        $template = app(SocialCrmSettingsService::class)->whatsappReplyTemplate();

        return strtr($template, [
            '{token}' => $token,
            '{platform}' => $comment->platform->label(),
            '{whatsapp_link}' => $link,
            '{smart_link}' => $smartLink,
        ]);
    }

    public function markRedirectedToWhatsapp(SocialComment $comment, ?string $replyText = null): string
    {
        $token = $this->generateTrackingToken($comment);

        $comment->update([
            'conversion_status' => SocialConversionStatus::TokenGenerated,
            'whatsapp_redirected_at' => now(),
        ]);

        $comment = $comment->refresh();
        $replyText = trim((string) ($replyText ?: $this->instagramReplyText($comment)));
        $alreadyPublished = $comment->actions()
            ->where('action', SocialCommentActionType::RedirectToWhatsapp)
            ->get()
            ->contains(fn ($action): bool => ! empty($action->external_response['meta_response'] ?? null));

        if ($alreadyPublished) {
            return $token;
        }

        $metaResponse = app(MetaSocialService::class)->replyToComment($comment, $replyText);

        $comment->actions()->create([
            'action' => SocialCommentActionType::RedirectToWhatsapp,
            'notes' => 'Mensaje de derivación publicado en Meta con token de rastreo.',
            'response_text' => $replyText,
            'external_response' => [
                'tracking_token' => $token,
                'meta_response' => $metaResponse,
            ],
        ]);

        return $token;
    }

    public function processIncomingMessage(WhatsappMessage $message): ?SocialComment
    {
        $token = $this->extractTrackingToken($message->message_body);

        if (! $token) {
            return null;
        }

        $comment = SocialComment::query()
            ->with('socialIdentity')
            ->where('tracking_token', $token)
            ->first();

        if (! $comment) {
            return null;
        }

        $identity = $comment->socialIdentity;

        if (! $identity) {
            $identity = SocialIdentity::create([
                'platform' => $comment->platform->value,
                'platform_user_id' => 'unknown-'.$comment->id,
                'username' => $comment->author_username,
                'display_name' => $comment->author_name,
                'status' => SocialIdentityStatus::NewLead,
                'first_seen_at' => $comment->published_at ?? now(),
                'last_seen_at' => now(),
                'metadata' => ['source' => 'whatsapp_handshake_without_identity'],
            ]);

            $comment->update(['social_identity_id' => $identity->id]);
        }

        $this->applyHandshake($comment->refresh(), $identity->refresh(), $message);

        return $comment->refresh();
    }

    public function findLeadByPhone(string $phone): ?SocialComment
    {
        $normalized = $this->normalizePhone($phone);

        return SocialComment::whereHas('socialIdentity', function (Builder $query) use ($normalized, $phone): void {
            $query->where('normalized_phone', $normalized)
                ->orWhere('phone', $phone)
                ->orWhere('phone', '+'.$normalized);
        })
            ->whereNotNull('tracking_token')
            ->latest('created_at')
            ->first();
    }

    public function extractTrackingToken(string $text): ?string
    {
        if (preg_match('/\bDNT-[A-Z0-9]{5}\b/i', $text, $matches) !== 1) {
            return null;
        }

        return Str::upper($matches[0]);
    }

    public function hasMalformedTrackingToken(string $text): bool
    {
        if ($this->extractTrackingToken($text)) {
            return false;
        }

        return preg_match('/\bDNT(?:-[A-Z0-9]{0,4}|\b)/i', $text) === 1;
    }

    private function applyHandshake(SocialComment $comment, SocialIdentity $identity, WhatsappMessage $message): void
    {
        $normalizedPhone = $this->normalizePhone($message->from_phone);
        $patient = $this->findPatientByPhone($message->from_phone);

        if ($patient) {
            $identity->update([
                'patient_id' => $patient->id,
                'phone' => $message->from_phone,
                'normalized_phone' => $normalizedPhone,
                'status' => SocialIdentityStatus::LinkedPatient,
                'linked_at' => now(),
                'last_seen_at' => now(),
            ]);

            $comment->update([
                'conversion_status' => SocialConversionStatus::IdentityLinked,
                'converted_patient_id' => $patient->id,
                'converted_at' => now(),
                'is_hidden' => true,
            ]);
        } else {
            $identity->update([
                'phone' => $message->from_phone,
                'normalized_phone' => $normalizedPhone,
                'status' => SocialIdentityStatus::PendingPatientCreation,
                'last_seen_at' => now(),
            ]);

            $comment->update([
                'conversion_status' => SocialConversionStatus::PendingPatientCreation,
            ]);

            app(SocialLeadAlertService::class)->createAlert($comment->refresh(), 'pending_patient_creation', 'warning', [
                'from_phone' => $message->from_phone,
                'tracking_token' => $comment->tracking_token,
            ]);
        }

        $comment->actions()->create([
            'action' => SocialCommentActionType::WhatsappHandshake,
            'notes' => $patient
                ? 'Handshake recibido y paciente vinculado automaticamente por telefono.'
                : 'Handshake recibido. Lead pendiente de crear ficha de paciente.',
            'external_response' => [
                'whatsapp_message_id' => $message->id,
                'from_phone' => $message->from_phone,
                'patient_id' => $patient?->id,
            ],
        ]);
    }

    private function findPatientByPhone(string $phone): ?Patient
    {
        $normalizedPhone = $this->normalizePhone($phone);
        $phones = array_values(array_unique([$phone, $normalizedPhone, '+'.$normalizedPhone]));

        return Patient::whereIn('phone', $phones)->first();
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? $phone;
    }
}
