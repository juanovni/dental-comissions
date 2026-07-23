<?php

namespace App\Services;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\ProfessionalRole;
use App\Enums\SocialCommentActionType;
use App\Enums\SocialIdentityStatus;
use App\Models\Appointment;
use App\Models\AppointmentSlotHold;
use App\Models\AppointmentSlotOffer;
use App\Models\Professional;
use App\Models\SocialComment;
use App\Models\SocialIdentity;
use App\Models\WhatsappMessage;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AppointmentSlotOfferService
{
    public function createFromAgentResponse(SocialComment $comment, WhatsappMessage $message, array $agentResponse): ?AppointmentSlotOffer
    {
        $candidate = $agentResponse['appointment_candidate'] ?? [];
        $isBookingIntent = in_array($candidate['intent_type'] ?? $agentResponse['intent'] ?? '', ['appointment_interest', 'ready_to_book'], true)
            || ($candidate['wants_appointment'] ?? false)
            || ($agentResponse['intent'] ?? null) === 'ready_to_book';

        if (! $isBookingIntent || ! app(SocialCrmSettingsService::class)->appointmentProposeSlots()) {
            return null;
        }

        $isDefaultProcedure = ! (bool) $comment->suggested_procedure_id;
        $resolvedProcedure = $comment->suggestedProcedure
            ?: app(AppointmentProcedureResolver::class)->defaultProcedure();

        if (! $resolvedProcedure) {
            return null;
        }

        if (! $comment->suggested_procedure_id) {
            $comment->forceFill(['suggested_procedure_id' => $resolvedProcedure->id])->save();
            $comment->setRelation('suggestedProcedure', $resolvedProcedure);
        }

        $slots = app(AppointmentSlotSearchService::class)->search([
            'date' => $candidate['preferred_date_parsed'] ?? null,
            'time' => $candidate['preferred_time_parsed'] ?? null,
            'period' => $candidate['preferred_period'] ?? null,
            'doctor_id' => $comment->suggested_doctor_id,
            'procedure_id' => $resolvedProcedure->id,
        ]);

        if ($slots === []) {
            return null;
        }

        $offer = AppointmentSlotOffer::create([
            'social_comment_id' => $comment->id,
            'whatsapp_message_id' => $message->id,
            'token' => Str::random(48),
            'status' => 'pending',
            'expires_at' => now()->addMinutes(app(SocialCrmSettingsService::class)->appointmentOfferLinkMinutes()),
            'metadata' => [
                'requested_date' => $candidate['preferred_date_parsed'] ?? null,
                'requested_time' => $candidate['preferred_time_parsed'] ?? null,
                'requested_period' => $candidate['preferred_period'] ?? null,
                'procedure_id' => $resolvedProcedure->id,
                'is_default_procedure' => $isDefaultProcedure,
                'doctor_id' => $comment->suggested_doctor_id,
                'options' => $this->indexedOptions($slots, $comment, $resolvedProcedure->id),
            ],
        ]);

        $comment->actions()->create([
            'action' => SocialCommentActionType::AppointmentSlotsOffered,
            'performed_by' => null,
            'notes' => 'Horarios disponibles ofrecidos al paciente.',
            'external_response' => [
                'offer_id' => $offer->id,
                'options' => $offer->metadata['options'],
            ],
        ]);

        return $offer;
    }

    public function pendingOfferFor(SocialComment $comment): ?AppointmentSlotOffer
    {
        return $comment->appointmentSlotOffers()
            ->where('status', 'pending')
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('id')
            ->first();
    }

    public function handleSelection(SocialComment $comment, WhatsappMessage $message): ?array
    {
        $offer = $this->pendingOfferFor($comment);

        if (! $offer) {
            return null;
        }

        $option = $this->selectedOption($offer, $message->message_body ?? '');

        if (! $option) {
            return null;
        }

        if (in_array($offer->metadata['patient_info_state'] ?? null, ['awaiting_name', 'awaiting_name_confirmation', 'awaiting_phone'], true)) {
            return null;
        }

        if (! $this->hasRealPatientName($comment)) {
            $this->savePendingSelection($offer, $option['index'], 'awaiting_name');
            return [
                'pending_patient_info' => true,
                'reply' => 'Antes de confirmar, ¿a nombre de quién registramos la cita?',
            ];
        }

        $patientName = $this->getPatientName($comment);
        if ($patientName && ! ($offer->metadata['patient_name_confirmed'] ?? false)) {
            $this->savePendingSelection($offer, $option['index'], 'awaiting_name_confirmation');

            return [
                'pending_patient_info' => true,
                'reply' => "La cita quedará a nombre de {$patientName}. ¿Está correcto o deseas cambiarlo?\n\nResponde *sí* para mantenerlo o escribe el nombre correcto.",
            ];
        }

        $appointment = $this->confirmOption($offer, $option);

        return [
            'appointment' => $appointment,
            'reply' => app(WhatsappService::class)->buildAppointmentCreatedReply($appointment),
        ];
    }

    public function pendingPatientInfoOffer(SocialComment $comment): ?AppointmentSlotOffer
    {
        $offer = $this->pendingOfferFor($comment);

        if (! $offer) {
            return null;
        }

        $state = $offer->metadata['patient_info_state'] ?? null;

        if (in_array($state, ['awaiting_name', 'awaiting_name_confirmation', 'awaiting_phone'], true)) {
            return $offer;
        }

        return null;
    }

    public function handlePatientInfoReply(AppointmentSlotOffer $offer, SocialComment $comment, WhatsappMessage $message): ?array
    {
        $metadata = $offer->metadata;
        $state = $metadata['patient_info_state'] ?? null;
        $pendingIndex = $metadata['pending_option_index'] ?? null;
        $body = trim($message->message_body ?? '');

        if (! $pendingIndex || ! $state) {
            return null;
        }

        $option = collect($metadata['options'] ?? [])->firstWhere('index', $pendingIndex);

        if (! $option) {
            return null;
        }

        if ($state === 'awaiting_name') {
            if (blank($body)) {
                return [
                    'pending_patient_info' => true,
                    'reply' => 'Por favor, indícanos el nombre del paciente para registrar la cita.',
                ];
            }

            $this->savePatientName($comment, $body);

            $phone = $this->getPhoneForConfirmation($comment);

            if ($phone) {
                $formattedPhone = $this->formatPhoneForDisplay($phone);
                $this->updateOfferState($offer, 'awaiting_phone');
                return [
                    'pending_patient_info' => true,
                    'reply' => "Perfecto, {$body}.\n\nUsaremos este número para recordatorios:\n{$formattedPhone}\n\n¿Es correcto?",
                ];
            }

            $this->clearPendingInfo($offer);
            $appointment = $this->confirmOption($offer, $option);

            return [
                'appointment' => $appointment,
                'reply' => app(WhatsappService::class)->buildAppointmentCreatedReply($appointment),
            ];
        }

        if ($state === 'awaiting_name_confirmation') {
            if (blank($body)) {
                $patientName = $this->getPatientName($comment) ?? 'el nombre registrado';

                return [
                    'pending_patient_info' => true,
                    'reply' => "La cita quedará a nombre de {$patientName}. ¿Está correcto o deseas cambiarlo?\n\nResponde *sí* para mantenerlo o escribe el nombre correcto.",
                ];
            }

            if (! $this->isConfirmResponse($body)) {
                $this->savePatientName($comment, $body);
            }

            $this->clearPendingInfo($offer, true);
            $appointment = $this->confirmOption($offer->refresh(), $option);

            return [
                'appointment' => $appointment,
                'reply' => app(WhatsappService::class)->buildAppointmentCreatedReply($appointment),
            ];
        }

        if ($state === 'awaiting_phone') {
            $isConfirm = $this->isConfirmResponse($body);

            if ($isConfirm) {
                $this->clearPendingInfo($offer);
                $appointment = $this->confirmOption($offer, $option);

                return [
                    'appointment' => $appointment,
                    'reply' => app(WhatsappService::class)->buildAppointmentCreatedReply($appointment),
                ];
            }

            return [
                'pending_patient_info' => true,
                'reply' => 'Si el número no es correcto, por favor contáctanos directamente para actualizarlo.',
            ];
        }

        return null;
    }

    public function buildOfferReply(AppointmentSlotOffer $offer): string
    {
        $comment = $offer->socialComment;
        $procedure = $comment->suggestedProcedure?->name ?? 'valoracion dental';
        $options = $offer->metadata['options'] ?? [];
        $dateText = $offer->metadata['requested_date']
            ? Carbon::parse($offer->metadata['requested_date'])->isoFormat('D [de] MMMM')
            : 'los próximos días';
        $period = $this->periodLabel($offer->metadata['requested_period'] ?? null);
        $lines = [];

        foreach ($options as $option) {
            $slot = Carbon::parse($option['datetime']);
            $lines[] = $option['index'].'. '.$slot->isoFormat('dddd D [de] MMMM').' - '.$slot->format('g:i A');
        }

        $calendarLink = route('social-appointments.show', ['token' => $offer->token]);
        $requested = $period ? "para el {$dateText} en la {$period}" : "para {$dateText}";
        $intro = ($offer->metadata['is_default_procedure'] ?? false)
            ? "Como aún no tenemos un procedimiento específico, podemos ayudarte a agendar una {$procedure} para que el doctor revise tu caso."
            : "Sí, con gusto te ayudamos a agendar tu cita para {$procedure}.";

        return $intro."\n\n".
            "Estas son las opciones disponibles {$requested}:\n\n".
            implode("\n", $lines).
            "\n\nResponde con el número de la opción o abre este enlace para ver más horarios:\n{$calendarLink}";
    }

    public function confirmFromToken(AppointmentSlotOffer $offer, int $optionIndex): Appointment
    {
        $option = collect($offer->metadata['options'] ?? [])->firstWhere('index', $optionIndex);

        if (! $option) {
            throw new \RuntimeException('La opción seleccionada no existe.');
        }

        return $this->confirmOption($offer, $option);
    }

    public function validOptionsForOffer(AppointmentSlotOffer $offer): array
    {
        if (! $offer->isPending()) {
            return [];
        }

        $comment = $offer->socialComment;
        $duration = app(SocialCrmSettingsService::class)->appointmentSlotDuration();

        return collect($offer->metadata['options'] ?? [])
            ->filter(function (array $option) use ($comment, $duration): bool {
                $start = Carbon::parse($option['datetime']);
                $end = $start->copy()->addMinutes($duration);
                $doctor = $this->doctorForOption($option, $comment);

                return $doctor
                    && app(AppointmentAvailabilityService::class)->isSlotAvailableForDoctor($doctor, $start, $end);
            })
            ->values()
            ->all();
    }

    private function confirmOption(AppointmentSlotOffer $offer, array $option): Appointment
    {
        $comment = $offer->socialComment()->with(['suggestedProcedure', 'suggestedDoctor'])->firstOrFail();
        $settings = app(SocialCrmSettingsService::class);
        $start = Carbon::parse($option['datetime']);
        $duration = $settings->appointmentSlotDuration();
        $end = $start->copy()->addMinutes($duration);
        $doctor = $this->doctorForOption($option, $comment);

        if (! $doctor || ! app(AppointmentAvailabilityService::class)->isSlotAvailableForDoctor($doctor, $start, $end)) {
            $offer->update(['status' => 'expired']);
            throw new \RuntimeException('Ese horario acaba de ocuparse.');
        }

        $patient = app(SocialPatientConversionService::class)->ensurePatientForLead($comment);

        $appointment = app(AppointmentCreationService::class)->createFromSocialLead($comment, [
            'patient_id' => $patient?->id,
            'scheduled_at' => $start,
            'duration_minutes' => $duration,
            'doctor_id' => $doctor->id,
            'procedure_id' => $comment->suggested_procedure_id,
            'status' => $settings->appointmentAutoConfirm() ? AppointmentStatus::Confirmed : AppointmentStatus::PendingConfirmation,
            'source' => AppointmentSource::WhatsappAi,
            'notes' => 'Cita creada desde opción seleccionada por WhatsApp/enlace móvil.',
            'created_by' => null,
            'audit_notes' => 'Cita creada desde oferta de horarios.',
            'metadata' => [
                'slot_offer_id' => $offer->id,
                'selected_option' => $option,
            ],
        ]);

        app(AppointmentWorkflowService::class)->syncToCalendar($appointment);

        $hold = AppointmentSlotHold::create([
            'appointment_slot_offer_id' => $offer->id,
            'social_comment_id' => $comment->id,
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'procedure_id' => $comment->suggested_procedure_id,
            'starts_at' => $start,
            'ends_at' => $end,
            'expires_at' => now()->addMinutes($settings->appointmentSlotHoldMinutes()),
            'status' => 'confirmed',
            'metadata' => ['option' => $option],
        ]);

        $offer->update([
            'status' => 'selected',
            'appointment_id' => $appointment->id,
            'selected_option_index' => $option['index'],
        ]);

        $comment->actions()->create([
            'action' => SocialCommentActionType::AppointmentSlotHeld,
            'performed_by' => null,
            'notes' => 'Horario seleccionado y confirmado desde oferta.',
            'external_response' => [
                'offer_id' => $offer->id,
                'hold_id' => $hold->id,
                'appointment_id' => $appointment->id,
                'selected_option' => $option,
            ],
        ]);

        app(SocialLeadScoringService::class)->scoreWhatsappSlotSelected($comment->refresh());

        return $appointment;
    }

    private function selectedOption(AppointmentSlotOffer $offer, string $message): ?array
    {
        $normalized = str($message)->lower()->ascii()->squish()->toString();
        $index = $this->selectedIndex($normalized);

        if (! $index) {
            return $this->selectedOptionByLabel($offer, $normalized);
        }

        return collect($offer->metadata['options'] ?? [])->firstWhere('index', $index);
    }

    private function selectedIndex(string $normalizedMessage): ?int
    {
        if (preg_match('/^(?:opcion\s*)?(\d+)$/', $normalizedMessage, $matches) === 1) {
            return (int) $matches[1];
        }

        if (preg_match('/\b(?:opcion|numero)\s+(\d+)\b/', $normalizedMessage, $matches) === 1) {
            return (int) $matches[1];
        }

        return match (true) {
            preg_match('/\b(?:el|la)\s+primer[ao]?\b/', $normalizedMessage) === 1 => 1,
            preg_match('/\b(?:el|la)\s+segund[ao]\b/', $normalizedMessage) === 1 => 2,
            preg_match('/\b(?:el|la)\s+tercer[ao]?\b/', $normalizedMessage) === 1 => 3,
            default => null,
        };
    }

    private function selectedOptionByLabel(AppointmentSlotOffer $offer, string $normalizedMessage): ?array
    {
        foreach ($offer->metadata['options'] ?? [] as $option) {
            $slot = Carbon::parse($option['datetime']);
            $labels = [
                $option['label'] ?? null,
                $slot->isoFormat('dddd D [de] MMMM').' - '.$slot->format('g:i A'),
                $slot->isoFormat('dddd D [de] MMMM [a las] h:mm A'),
            ];

            foreach (array_filter($labels) as $label) {
                if ($normalizedMessage === str($label)->lower()->ascii()->squish()->toString()) {
                    return $option;
                }
            }
        }

        return null;
    }

    private function indexedOptions(array $slots, SocialComment $comment, int $procedureId): array
    {
        return array_values(array_map(function (array $slot, int $i) use ($comment, $procedureId): array {
            $doctor = $this->doctorForOption($slot, $comment);

            return array_merge($slot, [
                'index' => $i + 1,
                'doctor_id' => $doctor?->id,
                'procedure_id' => $procedureId,
            ]);
        }, $slots, array_keys($slots)));
    }

    private function doctorForOption(array $option, ?SocialComment $comment): ?Professional
    {
        $doctorId = $option['doctor_id'] ?? $comment?->suggested_doctor_id;

        if ($doctorId) {
            return Professional::find($doctorId);
        }

        return Professional::query()
            ->where('role', ProfessionalRole::Doctor->value)
            ->where('is_active', true)
            ->first();
    }

    private function periodLabel(?string $period): ?string
    {
        return match ($period) {
            'morning' => 'mañana',
            'afternoon' => 'tarde',
            'night' => 'noche',
            default => null,
        };
    }

    public function hasRealPatientName(SocialComment $comment): bool
    {
        $name = $this->getPatientName($comment);

        if (blank($name)) {
            return false;
        }

        if (preg_match('/^\+?\d{7,15}$/', $name)) {
            return false;
        }

        return true;
    }

    public function getPatientName(SocialComment $comment): ?string
    {
        $comment->loadMissing('socialIdentity');

        return $comment->socialIdentity?->display_name
            ?? $comment->author_name
            ?? $comment->author_username;
    }

    public function getPhoneForConfirmation(SocialComment $comment): ?string
    {
        return $comment->socialIdentity?->phone;
    }

    private function formatPhoneForDisplay(string $phone): string
    {
        $cleaned = preg_replace('/\D/', '', $phone);

        if (strlen($cleaned) >= 10) {
            $country = substr($cleaned, 0, strlen($cleaned) - 10);
            $rest = substr($cleaned, -10);
            $formatted = $country ? '+'.$country.' ' : '';
            $formatted .= substr($rest, 0, 3).' '.substr($rest, 3, 3).' '.substr($rest, 6);

            return $formatted;
        }

        return $phone;
    }

    private function savePatientName(SocialComment $comment, string $name): void
    {
        $identity = $comment->socialIdentity;
        if ($identity) {
            $identity->updateQuietly(['display_name' => $name]);
        }
    }

    private function updateOfferState(AppointmentSlotOffer $offer, string $state): void
    {
        $metadata = $offer->metadata;
        $metadata['patient_info_state'] = $state;
        $offer->updateQuietly(['metadata' => $metadata]);
    }

    private function savePendingSelection(AppointmentSlotOffer $offer, int $optionIndex, string $state): void
    {
        $metadata = $offer->metadata;
        $metadata['pending_option_index'] = $optionIndex;
        $metadata['patient_info_state'] = $state;
        $offer->updateQuietly(['metadata' => $metadata]);
    }

    private function clearPendingInfo(AppointmentSlotOffer $offer, bool $nameConfirmed = false): void
    {
        $metadata = $offer->metadata;
        unset($metadata['patient_info_state'], $metadata['pending_option_index']);
        if ($nameConfirmed) {
            $metadata['patient_name_confirmed'] = true;
        }
        $offer->updateQuietly(['metadata' => $metadata]);
    }

    private function isConfirmResponse(string $body): bool
    {
        $confirming = [
            'si', 'sí', 'ok', 'okay', 'dale', 'confirmo', 'confirmar',
            'adelante', 'esta bien', 'está bien', 'de acuerdo', 'perfecto',
            'excelente', 'claro', 'si gracias', 'sí gracias', 'confirmado',
            'listo', 'hecho', 'confirm', 'yes', 'simon', 'simón', 'sep', 'sipo',
            'correcto', 'así es', 'asi es', 'tal cual', '👍', '👌', '✅', '✔',
        ];

        $lower = mb_strtolower($body);
        $lower = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'u', 'n'], $lower);

        foreach ($confirming as $keyword) {
            if ($lower === $keyword || str_starts_with($lower, $keyword.' ') || str_starts_with($lower, $keyword.',')) {
                return true;
            }
        }

        return false;
    }
}
