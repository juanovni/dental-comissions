<?php

namespace App\Http\Controllers;

use App\Models\AppointmentSlotOffer;
use App\Models\Appointment;
use App\Services\AppointmentSlotOfferService;
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

        $options = app(AppointmentSlotOfferService::class)->validOptionsForOffer($offer);
        $groups = collect($options)
            ->groupBy(fn (array $option): string => Carbon::parse($option['datetime'])->toDateString())
            ->map(fn ($items, string $date): array => [
                'date' => $date,
                'label' => Carbon::parse($date)->isoFormat('dddd D [de] MMMM'),
                'short_label' => Carbon::parse($date)->isoFormat('ddd D MMM'),
                'options' => $items->values()->all(),
            ])
            ->values()
            ->all();

        $comment = $offer->socialComment;
        $showDoctor = app(SocialCrmSettingsService::class)->appointmentShowDoctor();
        $confirmedAppointment = $offer->appointment;
        $offerService = app(AppointmentSlotOfferService::class);
        $needsPatientName = ! $offerService->hasRealPatientName($comment);
        $patientName = $needsPatientName ? '' : ($offerService->getPatientName($comment) ?? '');
        $patientPhone = $offerService->getPhoneForConfirmation($comment);

        return view('social.appointments.show', [
            'offer' => $offer,
            'comment' => $comment,
            'confirmedAppointment' => $confirmedAppointment,
            'procedure' => $comment->suggestedProcedure,
            'doctor' => $showDoctor ? $comment->suggestedDoctor : null,
            'groups' => $groups,
            'options' => $options,
            'expired' => ! $offer->isPending(),
            'hasAvailableOptions' => $options !== [],
            'needsPatientName' => $needsPatientName,
            'patientName' => $patientName,
            'patientPhone' => $patientPhone,
        ]);
    }

    public function confirm(Request $request, string $token): RedirectResponse
    {
        $offer = AppointmentSlotOffer::query()
            ->with(['socialComment.socialIdentity', 'whatsappMessage'])
            ->where('token', $token)
            ->firstOrFail();

        if (! $offer->isPending()) {
            return back()->with('appointment_error', 'Este enlace ya expiró o fue utilizado.');
        }

        $comment = $offer->socialComment;
        $offerService = app(AppointmentSlotOfferService::class);

        $patientName = trim((string) $request->input('patient_name', ''));
        $phoneConfirmed = (bool) $request->input('phone_confirmed', false);

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

        $optionIndex = (int) $request->input('option');

        try {
            $appointment = $offerService->confirmFromToken($offer, $optionIndex);
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
