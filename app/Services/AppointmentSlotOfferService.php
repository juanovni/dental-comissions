<?php

namespace App\Services;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\SocialCommentActionType;
use App\Models\Appointment;
use App\Models\AppointmentSlotHold;
use App\Models\AppointmentSlotOffer;
use App\Models\Professional;
use App\Models\SocialComment;
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

        $slots = app(AppointmentSlotSearchService::class)->search([
            'date' => $candidate['preferred_date_parsed'] ?? null,
            'time' => $candidate['preferred_time_parsed'] ?? null,
            'period' => $candidate['preferred_period'] ?? null,
            'doctor_id' => $comment->suggested_doctor_id,
            'procedure_id' => $comment->suggested_procedure_id,
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
                'procedure_id' => $comment->suggested_procedure_id,
                'doctor_id' => $comment->suggested_doctor_id,
                'options' => $this->indexedOptions($slots, $comment),
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

        $appointment = $this->confirmOption($offer, $option);

        return [
            'appointment' => $appointment,
            'reply' => app(WhatsappService::class)->buildAppointmentCreatedReply($appointment),
        ];
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

        return "Sí, con gusto te ayudamos a agendar tu cita para {$procedure}.\n\n".
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
                $doctorId = $option['doctor_id'] ?? $comment?->suggested_doctor_id;
                $doctor = $doctorId ? Professional::find($doctorId) : null;

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
        $doctorId = $option['doctor_id'] ?? $comment->suggested_doctor_id;
        $doctor = $doctorId ? Professional::find($doctorId) : null;

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

    private function indexedOptions(array $slots, SocialComment $comment): array
    {
        return array_values(array_map(function (array $slot, int $i) use ($comment): array {
            return array_merge($slot, [
                'index' => $i + 1,
                'procedure_id' => $comment->suggested_procedure_id,
            ]);
        }, $slots, array_keys($slots)));
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
}
