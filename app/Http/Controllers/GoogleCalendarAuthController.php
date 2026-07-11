<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleCalendarAuthController extends Controller
{
    public function callback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            Log::warning('Error en OAuth Google Calendar', [
                'error' => $request->string('error')->toString(),
                'error_description' => $request->string('error_description')->toString(),
            ]);

            return redirect('/admin/integrations#google-calendar')
                ->with('error', 'Autorizacion cancelada o rechazada.');
        }

        abort_unless($request->filled('code'), 400, 'Google no devolvio codigo de autorizacion.');

        if ($request->string('state')->toString() !== 'clinic') {
            Log::error('State OAuth Google Calendar invalido', [
                'state' => $request->string('state')->toString(),
            ]);

            return redirect('/admin/integrations#google-calendar')
                ->with('error', 'Solicitud de autorizacion invalida.');
        }

        $success = app(GoogleCalendarService::class)->exchangeClinicCode(
            $request->string('code')->toString(),
        );

        if (!$success) {
            return redirect('/admin/integrations#google-calendar')
                ->with('error', 'No se pudo completar la autenticacion con Google.');
        }

        return redirect('/admin/integrations#google-calendar')
            ->with('status', 'Google Calendar conectado exitosamente.');
    }
}
