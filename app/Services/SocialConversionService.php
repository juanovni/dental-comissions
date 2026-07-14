<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\SocialCommentActionType;
use App\Enums\SocialCommentClassification;
use App\Enums\SocialCommentStatus;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPipelineStage;
use App\Enums\SocialPlatform;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\SocialAccount;
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

    public function findOrCreateWhatsappLead(WhatsappMessage $message): SocialComment
    {
        $normalized = $this->normalizePhone($message->from_phone);
        $procedureId = $this->resolveProcedureIdFromText($message->message_body ?? '');
        $identity = SocialIdentity::query()
            ->where('platform', SocialPlatform::Whatsapp->value)
            ->where(function (Builder $query) use ($normalized, $message): void {
                $query->where('normalized_phone', $normalized)
                    ->orWhere('phone', $message->from_phone)
                    ->orWhere('phone', '+'.$normalized);
            })
            ->first();

        if (! $identity) {
            $identity = SocialIdentity::create([
                'platform' => SocialPlatform::Whatsapp,
                'platform_user_id' => $normalized,
                'display_name' => $message->from_phone,
                'phone' => $message->from_phone,
                'normalized_phone' => $normalized,
                'status' => SocialIdentityStatus::NewLead,
                'first_seen_at' => $message->created_at ?? now(),
                'last_seen_at' => now(),
                'metadata' => ['source' => 'whatsapp_first_message'],
            ]);
        } else {
            $identity->update([
                'phone' => $message->from_phone,
                'normalized_phone' => $normalized,
                'last_seen_at' => now(),
            ]);
        }

        $comment = $identity->comments()
            ->where('platform', SocialPlatform::Whatsapp->value)
            ->where('is_hidden', false)
            ->whereHas('appointments', function (Builder $query): void {
                $query->whereIn('status', [
                    AppointmentStatus::PendingConfirmation->value,
                    AppointmentStatus::Scheduled->value,
                    AppointmentStatus::Confirmed->value,
                    AppointmentStatus::Rescheduled->value,
                ])->whereNotNull('scheduled_at');
            })
            ->latest('id')
            ->first();

        if ($comment) {
            if (! $comment->suggested_procedure_id && $procedureId) {
                $comment->update(['suggested_procedure_id' => $procedureId]);
            }

            $message->update(['social_comment_id' => $comment->id]);

            return $comment->refresh();
        }

        $comment = $identity->comments()
            ->where('platform', SocialPlatform::Whatsapp->value)
            ->where('is_hidden', false)
            ->whereNotIn('conversion_status', [
                SocialConversionStatus::AppointmentCreated->value,
                SocialConversionStatus::Converted->value,
                SocialConversionStatus::Lost->value,
            ])
            ->latest('id')
            ->first();

        if ($comment) {
            if (! $comment->suggested_procedure_id && $procedureId) {
                $comment->update(['suggested_procedure_id' => $procedureId]);
            }

            $message->update(['social_comment_id' => $comment->id]);

            return $comment->refresh();
        }

        $comment = SocialComment::create([
            'social_account_id' => $this->whatsappSocialAccount()->id,
            'social_identity_id' => $identity->id,
            'suggested_procedure_id' => $procedureId,
            'platform' => SocialPlatform::Whatsapp,
            'external_comment_id' => 'whatsapp-'.($message->message_sid ?: $message->id),
            'author_name' => $message->from_phone,
            'author_username' => $message->from_phone,
            'author_external_id' => $normalized,
            'comment_text' => $message->message_body,
            'classification' => SocialCommentClassification::CommercialQuestion,
            'status' => SocialCommentStatus::New,
            'is_hidden' => false,
            'pipeline_stage' => SocialPipelineStage::New,
            'conversion_status' => SocialConversionStatus::WhatsappStarted,
            'published_at' => $message->created_at ?? now(),
            'processed_at' => now(),
            'raw_payload' => [
                'source' => 'whatsapp_first_message',
                'whatsapp_message_id' => $message->id,
                'from_phone' => $message->from_phone,
            ],
        ]);

        $message->update(['social_comment_id' => $comment->id]);

        $comment->actions()->create([
            'action' => SocialCommentActionType::WhatsappHandshake,
            'notes' => 'Lead creado automaticamente desde primer mensaje directo de WhatsApp.',
            'external_response' => [
                'whatsapp_message_id' => $message->id,
                'from_phone' => $message->from_phone,
            ],
        ]);

        app(SocialLeadScoringService::class)->scoreWhatsappFirstMessage($comment->refresh());

        return $comment->refresh();
    }

    public function applyProcedureFromMessage(SocialComment $comment, WhatsappMessage $message): SocialComment
    {
        if ($comment->suggested_procedure_id) {
            return $comment->refresh();
        }

        $procedureId = $this->resolveProcedureIdFromText($message->message_body ?? '');

        if (! $procedureId) {
            return $comment->refresh();
        }

        $comment->update(['suggested_procedure_id' => $procedureId]);

        return $comment->refresh();
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

    private function resolveProcedureIdFromText(string $text): ?int
    {
        $normalized = str($text)->ascii()->lower()->squish()->toString();

        if ($normalized === '') {
            return null;
        }

        $category = match (true) {
            str_contains($normalized, 'odontologia invisible'),
            str_contains($normalized, 'ortodoncia invisible'),
            str_contains($normalized, 'invisalign'),
            str_contains($normalized, 'alineador'),
            str_contains($normalized, 'alineadores'),
            str_contains($normalized, 'bracket invisible'),
            str_contains($normalized, 'brackets invisibles') => 'ortodoncia_invisible',
            str_contains($normalized, 'ortodoncia'),
            str_contains($normalized, 'bracket'),
            str_contains($normalized, 'brackets') => 'ortodoncia',
            str_contains($normalized, 'implante'),
            str_contains($normalized, 'diente perdido'),
            str_contains($normalized, 'pieza perdida') => 'implantes',
            str_contains($normalized, 'limpieza'),
            str_contains($normalized, 'profilaxis'),
            str_contains($normalized, 'sarro') => 'limpieza',
            str_contains($normalized, 'diseno de sonrisa'),
            str_contains($normalized, 'carilla'),
            str_contains($normalized, 'estetica'),
            str_contains($normalized, 'blanqueamiento') => 'diseno_sonrisa',
            default => null,
        };

        if (! $category) {
            return null;
        }

        $terms = match ($category) {
            'ortodoncia_invisible' => ['ortodoncia invisible', 'invisalign', 'alineador', 'alineadores', 'ortodoncia'],
            'ortodoncia' => ['ortodoncia', 'bracket', 'brackets'],
            'implantes' => ['implante', 'implantes'],
            'limpieza' => ['limpieza', 'profilaxis'],
            'diseno_sonrisa' => ['diseno', 'sonrisa', 'carilla', 'blanqueamiento'],
            default => [$category],
        };

        return Procedure::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($category, $terms): void {
                $query->whereRaw('lower(category) = ?', [$category])
                    ->orWhereRaw('lower(code) = ?', [$category]);

                foreach ($terms as $term) {
                    $query->orWhereRaw('lower(name) like ?', ['%'.$term.'%'])
                        ->orWhereRaw('lower(code) like ?', ['%'.$term.'%'])
                        ->orWhereRaw('lower(category) like ?', ['%'.$term.'%']);
                }
            })
            ->orderByRaw('case when lower(name) like ? then 0 else 1 end', ['%ortodoncia invisible%'])
            ->value('id');
    }

    private function whatsappSocialAccount(): SocialAccount
    {
        $externalAccountId = 'whatsapp-'.(config('services.whatsapp.phone_number_id') ?: 'business');

        return SocialAccount::firstOrCreate(
            [
                'platform' => SocialPlatform::Whatsapp,
                'external_account_id' => $externalAccountId,
            ],
            [
                'account_name' => 'WhatsApp Business',
                'is_active' => true,
                'sync_settings' => ['source' => 'whatsapp_first_leads'],
            ],
        );
    }
}
