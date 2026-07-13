<?php

namespace App\Http\Controllers;

use App\Models\AppointmentSlotOffer;
use App\Services\AppointmentSlotOfferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SocialAppointmentLinkController extends Controller
{
    public function show(string $token): View
    {
        $offer = AppointmentSlotOffer::query()
            ->with(['socialComment.suggestedProcedure'])
            ->where('token', $token)
            ->firstOrFail();

        return view('social.appointments.show', [
            'offer' => $offer,
            'options' => $offer->metadata['options'] ?? [],
            'expired' => ! $offer->isPending(),
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
