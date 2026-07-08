<?php

namespace App\Http\Controllers;

use App\Models\Professional;
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

            return redirect('/admin/integrations/google-calendar')
                ->with('error', 'Autorizacion cancelada o rechazada.');
        }

        abort_unless($request->filled('code'), 400, 'Google no devolvio codigo de autorizacion.');

        $professionalId = $request->string('state')->toString();
        $professional = Professional::find($professionalId);

        if (!$professional) {
            Log::error('Professional no encontrado en callback OAuth', [
                'professional_id' => $professionalId,
            ]);

            return redirect('/admin/integrations/google-calendar')
                ->with('error', 'Profesional no encontrado.');
        }

        $success = app(GoogleCalendarService::class)->exchangeCode(
            $professional,
            $request->string('code')->toString(),
        );

        if (!$success) {
            return redirect('/admin/integrations/google-calendar')
                ->with('error', 'No se pudo completar la autenticacion con Google.');
        }

        return redirect('/admin/integrations/google-calendar')
            ->with('status', 'Google Calendar conectado exitosamente.');
    }
}
