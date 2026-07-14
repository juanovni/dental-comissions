<?php

namespace App\Http\Controllers;

use App\Models\AppointmentSlotOffer;
use App\Services\AppointmentSlotOfferService;
use App\Services\SocialCrmSettingsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SocialAppointmentLinkController extends Controller
{
    public function show(string $token): View
    {
        $offer = AppointmentSlotOffer::query()
            ->with(['socialComment.suggestedProcedure', 'socialComment.suggestedDoctor'])
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

        return view('social.appointments.show', [
            'offer' => $offer,
            'comment' => $comment,
            'procedure' => $comment->suggestedProcedure,
            'doctor' => $showDoctor ? $comment->suggestedDoctor : null,
            'groups' => $groups,
            'options' => $options,
            'expired' => ! $offer->isPending(),
            'hasAvailableOptions' => $options !== [],
        ]);
    }

    public function confirm(string $token): RedirectResponse
    {
        $offer = AppointmentSlotOffer::query()
            ->where('token', $token)
            ->firstOrFail();

        if (! $offer->isPending()) {
            return back()->with('appointment_error', 'Este enlace ya expiró o fue utilizado.');
        }

        $optionIndex = (int) request()->input('option');

        try {
            app(AppointmentSlotOfferService::class)->confirmFromToken($offer, $optionIndex);
        } catch (\Throwable $e) {
            return back()->with('appointment_error', 'Ese horario acaba de ocuparse. Por favor elige otra opción o escríbenos por WhatsApp.');
        }

        return redirect()
            ->route('social-appointments.show', ['token' => $token])
            ->with('appointment_success', 'Tu cita quedó registrada. Te enviaremos la confirmación por WhatsApp.');
    }
}
