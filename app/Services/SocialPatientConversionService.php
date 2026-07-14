<?php

namespace App\Services;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Models\Patient;
use App\Models\SocialComment;
use App\Models\SocialIdentity;
use Illuminate\Support\Str;

class SocialPatientConversionService
{
    public function ensurePatientForLead(SocialComment $comment, ?string $phone = null, array $data = []): ?Patient
    {
        $comment->loadMissing(['socialIdentity', 'convertedPatient']);

        if ($comment->convertedPatient) {
            $this->linkPatientToLead($comment, $comment->convertedPatient, 'Paciente ya vinculado al lead social.', SocialCommentActionType::LinkIdentity);
            return $comment->convertedPatient;
        }

        if ($comment->socialIdentity?->patient) {
            $this->linkPatientToLead($comment, $comment->socialIdentity->patient, 'Paciente existente vinculado por identidad social.', SocialCommentActionType::LinkIdentity);
            return $comment->socialIdentity->patient;
        }

        $phone = $phone ?: $comment->socialIdentity?->phone;

        if ($phone) {
            $patient = $this->findPatientByPhone($phone);

            if ($patient) {
                $this->linkPatientToLead($comment, $patient, 'Paciente existente vinculado automaticamente por telefono.', SocialCommentActionType::LinkIdentity);
                return $patient;
            }
        }

        $settings = app(SocialCrmSettingsService::class);

        if (! $settings->appointmentAutoCreatePatient()) {
            return null;
        }

        if ($settings->appointmentRequireWhatsappPhoneForPatient() && blank($phone)) {
            return null;
        }

        return $this->createPatientFromLead($comment, array_merge($data, [
            'phone' => $phone,
        ]));
    }

    public function createPatientFromLead(SocialComment $comment, array $data = []): Patient
    {
        $comment->loadMissing(['socialIdentity', 'suggestedProcedure']);

        $fullName = trim((string) ($data['full_name']
            ?? $comment->socialIdentity?->display_name
            ?? $comment->author_name
            ?? $comment->author_username
            ?? app(SocialCrmSettingsService::class)->appointmentPatientFallbackName()));

        if ($fullName === '') {
            $fullName = app(SocialCrmSettingsService::class)->appointmentPatientFallbackName();
        }

        $notes = $data['notes'] ?? $this->defaultPatientNotes($comment);

        $patient = Patient::create([
            'full_name' => $fullName,
            'normalized_name' => $this->normalizeName($fullName),
            'phone' => $data['phone'] ?? $comment->socialIdentity?->phone,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'notes' => $notes,
        ]);

        $this->linkPatientToLead($comment, $patient, 'Ficha de paciente creada automaticamente desde lead social.', SocialCommentActionType::CreatePatientFromLead);

        return $patient;
    }

    public function linkPatientToLead(
        SocialComment $comment,
        Patient $patient,
        string $notes,
        SocialCommentActionType $action = SocialCommentActionType::LinkIdentity,
    ): void {
        $identity = $comment->socialIdentity;

        if (! $identity) {
            $identity = SocialIdentity::firstOrCreate(
                [
                    'platform' => $comment->platform?->value ?? SocialPlatform::Instagram->value,
                    'platform_user_id' => $comment->author_external_id ?: 'comment_'.$comment->id,
                ],
                [
                    'username' => $comment->author_username,
                    'display_name' => $comment->author_name,
                    'first_seen_at' => $comment->published_at ?: now(),
                    'last_seen_at' => now(),
                    'metadata' => ['source' => 'auto_patient_conversion'],
                ],
            );

            $comment->update(['social_identity_id' => $identity->id]);
        }

        $identity->update([
            'patient_id' => $patient->id,
            'phone' => $identity->phone ?: $patient->phone,
            'normalized_phone' => $identity->normalized_phone ?: $this->normalizePhone((string) $patient->phone),
            'status' => SocialIdentityStatus::LinkedPatient,
            'linked_at' => $identity->linked_at ?: now(),
            'last_seen_at' => now(),
        ]);

        $comment->update([
            'conversion_status' => SocialConversionStatus::IdentityLinked,
            'converted_patient_id' => $patient->id,
            'converted_at' => $comment->converted_at ?: now(),
        ]);

        $alreadyAudited = $comment->actions()
            ->where('action', $action->value)
            ->where('external_response->patient_id', $patient->id)
            ->exists();

        if (! $alreadyAudited) {
            $comment->actions()->create([
                'action' => $action,
                'performed_by' => auth()->id(),
                'notes' => $notes,
                'external_response' => ['patient_id' => $patient->id],
            ]);
        }
    }

    public function findPatientByPhone(string $phone): ?Patient
    {
        $normalizedPhone = $this->normalizePhone($phone);
        $phones = array_values(array_unique([$phone, $normalizedPhone, '+'.$normalizedPhone]));

        return Patient::whereIn('phone', $phones)->first();
    }

    public function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? $phone;
    }

    public function normalizeName(string $name): string
    {
        return Str::of($name)->lower()->ascii()->squish()->toString();
    }

    public function defaultPatientNotes(SocialComment $comment): string
    {
        $procedure = $comment->suggestedProcedure?->name ?: 'No especificado';
        $token = $comment->tracking_token ?: 'Sin token';

        return "Ficha creada automaticamente desde lead social.\nToken: {$token}\nProcedimiento de interes: {$procedure}\nConfirmar nombre real y datos clinicos en recepcion.";
    }
}
