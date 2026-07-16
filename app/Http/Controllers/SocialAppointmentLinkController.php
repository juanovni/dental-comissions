<?php

namespace App\Http\Controllers;

use App\Models\AppointmentSlotOffer;
use App\Models\Appointment;
use App\Services\AppointmentAvailabilityService;
use App\Services\AppointmentSlotOfferService;
use App\Services\GoogleCalendarService;
use App\Services\SocialCrmSettingsService;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SocialAppointmentLinkController extends Controller
{
    public function show(string $token): View
    {
        $offer = AppointmentSlotOffer::query()
            ->with(['appointment.doctor', 'appointment.procedure', 'socialComment.suggestedProcedure', 'socialComment.suggestedDoctor'])
            ->where('token', $token)
            ->firstOrFail();

        $comment = $offer->socialComment;
        $settings = app(SocialCrmSettingsService::class);
        $showDoctor = $settings->appointmentShowDoctor();
        $confirmedAppointment = $offer->appointment;
        $offerService = app(AppointmentSlotOfferService::class);
        $availabilityService = app(AppointmentAvailabilityService::class);
        $duration = $settings->appointmentSlotDuration();

        $preferredDate = null;
        $requestedDate = $offer->metadata['requested_date'] ?? null;
        if ($requestedDate) {
            try {
                $preferredDate = Carbon::parse($requestedDate)->startOfDay();
            } catch (\Throwable) {
                $preferredDate = null;
            }
        }

        $offerOptions = $offerService->validOptionsForOffer($offer);

        $window = $availabilityService->availabilityWindow($preferredDate, 5, 21);

        $needsPatientName = ! $offerService->hasRealPatientName($comment);
        $patientName = $needsPatientName ? '' : ($offerService->getPatientName($comment) ?? '');
        $patientPhone = $offerService->getPhoneForConfirmation($comment);

        return view('social.appointments.show', [
            'offer' => $offer,
            'comment' => $comment,
            'confirmedAppointment' => $confirmedAppointment,
            'procedure' => $comment->suggestedProcedure,
            'doctor' => $showDoctor ? $comment->suggestedDoctor : null,
            'availabilityWindow' => $window,
            'offerOptions' => $offerOptions,
            'duration' => $duration,
            'preferredDate' => $preferredDate,
            'expired' => ! $offer->isPending(),
            'hasAvailableOptions' => $offerOptions !== [],
            'needsPatientName' => $needsPatientName,
            'patientName' => $patientName,
            'patientPhone' => $patientPhone,
        ]);
    }

    public function confirm(Request $request, string $token): RedirectResponse
    {
        $offer = AppointmentSlotOffer::query()
            ->with(['socialComment.socialIdentity', 'whatsappMessage', 'socialComment.suggestedProcedure', 'socialComment.suggestedDoctor'])
            ->where('token', $token)
            ->firstOrFail();

        if (! $offer->isPending()) {
            return back()->with('appointment_error', 'Este enlace ya expiró o fue utilizado.');
        }

        $comment = $offer->socialComment;
        $offerService = app(AppointmentSlotOfferService::class);

        $patientName = trim((string) $request->input('patient_name', ''));
        $phoneConfirmed = (bool) $request->input('phone_confirmed', false);
        $selectedDatetime = $request->input('selected_datetime');
        $selectedOptionIndex = $request->input('option');

        if (! $offerService->hasRealPatientName($comment)) {
            if (blank($patientName)) {
                return back()->with('appointment_error', 'Por favor ingresa el nombre del paciente.');
            }
            $identity = $comment->socialIdentity;
            if ($identity) {
                $identity->updateQuietly(['display_name' => $patientName]);
            }
            if (! $phoneConfirmed && $offerService->getPhoneForConfirmation($comment)) {
                return back()->with('appointment_error', 'Por favor confirma que el número de teléfono es correcto.');
            }
        }

        try {
            if (filled($selectedDatetime)) {
                $appointment = $this->confirmFromDatetime($offer, $comment, Carbon::parse($selectedDatetime));
            } elseif (filled($selectedOptionIndex)) {
                $appointment = $offerService->confirmFromToken($offer, (int) $selectedOptionIndex);
            } else {
                return back()->with('appointment_error', 'Por favor selecciona un horario.');
            }
        } catch (\Throwable $e) {
            return back()->with('appointment_error', 'Ese horario acaba de ocuparse. Por favor elige otra opción o escríbenos por WhatsApp.');
        }

        $sent = $this->sendWhatsappConfirmation($offer->refresh(), $appointment);

        return redirect()
            ->route('social-appointments.show', ['token' => $token])
            ->with('appointment_success', $sent
                ? 'Tu cita quedó registrada. Te enviamos la confirmación por WhatsApp.'
                : 'Tu cita quedó registrada. No pudimos enviar la confirmación por WhatsApp en este momento.');
    }

    private function confirmFromDatetime(AppointmentSlotOffer $offer, \App\Models\SocialComment $comment, Carbon $start): Appointment
    {
        $settings = app(SocialCrmSettingsService::class);
        $duration = $settings->appointmentSlotDuration();
        $end = $start->copy()->addMinutes($duration);

        $doctor = $comment->suggestedDoctor
            ?? \App\Models\Professional::query()
                ->where('role', \App\Enums\ProfessionalRole::Doctor->value)
                ->where('is_active', true)
                ->first();

        if (! $doctor) {
            throw new \RuntimeException('No hay doctor disponible.');
        }

        $available = app(AppointmentAvailabilityService::class)->isSlotAvailableForDoctor($doctor, $start, $end);

        if (! $available) {
            throw new \RuntimeException('Ese horario acaba de ocuparse.');
        }

        $patient = app(\App\Services\SocialPatientConversionService::class)->ensurePatientForLead($comment);

        $appointment = app(\App\Services\AppointmentCreationService::class)->createFromSocialLead($comment, [
            'patient_id' => $patient?->id,
            'scheduled_at' => $start,
            'duration_minutes' => $duration,
            'doctor_id' => $doctor->id,
            'procedure_id' => $comment->suggested_procedure_id,
            'status' => $settings->appointmentAutoConfirm() ? \App\Enums\AppointmentStatus::Confirmed : \App\Enums\AppointmentStatus::PendingConfirmation,
            'source' => \App\Enums\AppointmentSource::SmartLink,
            'notes' => 'Cita agendada desde calendario web.',
            'created_by' => null,
            'audit_notes' => 'Cita creada desde seleccion libre en calendario.',
            'metadata' => [
                'slot_offer_id' => $offer->id,
                'selected_datetime' => $start->toIso8601String(),
            ],
        ]);

        app(\App\Services\AppointmentWorkflowService::class)->syncToCalendar($appointment);

        \App\Models\AppointmentSlotHold::create([
            'appointment_slot_offer_id' => $offer->id,
            'social_comment_id' => $comment->id,
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'procedure_id' => $comment->suggested_procedure_id,
            'starts_at' => $start,
            'ends_at' => $end,
            'expires_at' => now()->addMinutes($settings->appointmentSlotHoldMinutes()),
            'status' => 'confirmed',
            'metadata' => ['selected_datetime' => $start->toIso8601String()],
        ]);

        $offer->update([
            'status' => 'selected',
            'appointment_id' => $appointment->id,
            'selected_option_index' => null,
        ]);

        $comment->actions()->create([
            'action' => \App\Enums\SocialCommentActionType::AppointmentSlotHeld,
            'performed_by' => null,
            'notes' => 'Horario seleccionado desde calendario web.',
            'external_response' => [
                'offer_id' => $offer->id,
                'appointment_id' => $appointment->id,
                'selected_datetime' => $start->toIso8601String(),
            ],
        ]);

        app(\App\Services\SocialLeadScoringService::class)->scoreWhatsappSlotSelected($comment->refresh());

        return $appointment;
    }

    public function calendar(string $token): Response
    {
        $offer = AppointmentSlotOffer::query()
            ->with(['appointment.doctor', 'appointment.procedure'])
            ->where('token', $token)
            ->firstOrFail();

        $appointment = $offer->appointment;

        abort_unless($appointment && $appointment->scheduled_at, 404);

        $startsAt = $appointment->scheduled_at->copy();
        $endsAt = $startsAt->copy()->addMinutes($appointment->duration_minutes ?: app(SocialCrmSettingsService::class)->appointmentSlotDuration());
        $procedure = $appointment->procedure?->name ?? 'Cita dental';
        $doctor = $appointment->doctor?->name;
        $timezone = config('app.timezone', 'UTC');
        $uid = 'appointment-'.$appointment->id.'@'.parse_url(config('app.url', 'https://dental.local'), PHP_URL_HOST);
        $description = 'Cita dental registrada por WhatsApp.' . ($doctor ? '\nProfesional: '.$doctor : '');

        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Dental CRM//Appointments//ES',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:'.$this->icsText($uid),
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART;TZID='.$timezone.':'.$startsAt->format('Ymd\THis'),
            'DTEND;TZID='.$timezone.':'.$endsAt->format('Ymd\THis'),
            'SUMMARY:'.$this->icsText('Cita dental - '.$procedure),
            'DESCRIPTION:'.$this->icsText($description),
            'STATUS:CONFIRMED',
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="cita-dental.ics"',
        ]);
    }

    private function sendWhatsappConfirmation(AppointmentSlotOffer $offer, Appointment $appointment): bool
    {
        $offer->loadMissing(['socialComment.socialIdentity', 'whatsappMessage']);

        $phone = $offer->whatsappMessage?->from_phone
            ?: $offer->socialComment?->socialIdentity?->phone;

        if (! $phone) {
            return false;
        }

        return app(WhatsappService::class)->sendMessage(
            $phone,
            app(WhatsappService::class)->buildAppointmentCreatedReply($appointment),
            $offer->social_comment_id,
        );
    }

    private function icsText(string $value): string
    {
        return str_replace(
            ["\\", ";", ",", "\r\n", "\n", "\r"],
            ["\\\\", "\\;", "\\,", "\\n", "\\n", "\\n"],
            $value,
        );
    }
}
