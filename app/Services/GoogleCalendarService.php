<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\CalendarIntegration;
use App\Models\Professional;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\Event as CalendarEvent;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\EventExtendedProperties;
use Google\Service\Oauth2 as GoogleOAuth2;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    private function config(): array
    {
        return config('services.google_oauth', []);
    }

    public function clinicIntegration(): CalendarIntegration
    {
        return CalendarIntegration::clinicGoogle();
    }

    public function hasClinicCalendar(): bool
    {
        return $this->clinicIntegration()->isConnected();
    }

    public function client(): GoogleClient
    {
        $cfg = $this->config();

        $client = new GoogleClient();
        $client->setClientId($cfg['client_id']);
        $client->setClientSecret($cfg['client_secret']);
        $client->setRedirectUri($cfg['redirect_uri']);
        $client->setScopes($cfg['scopes'] ?? []);
        $client->setAccessType($cfg['access_type'] ?? 'offline');
        $client->setPrompt($cfg['prompt'] ?? 'consent');
        $client->setConfig('token_format', 'full');

        return $client;
    }

    public function clientForProfessional(Professional $professional): ?GoogleClient
    {
        if (!$professional->hasGoogleCalendar()) {
            Log::warning('Google Calendar no configurado para professional', [
                'professional_id' => $professional->id,
            ]);
            return null;
        }

        $token = $professional->getGoogleCalendarTokenDecrypted();
        if (blank($token)) {
            return null;
        }

        $client = $this->client();
        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            $refreshed = $this->refreshToken($professional, $client);
            if (!$refreshed) {
                return null;
            }
        }

        return $client;
    }

    public function getAuthorizationUrl(Professional $professional): string
    {
        $client = $this->client();
        $client->setState((string) $professional->id);

        return $client->createAuthUrl();
    }

    public function getClinicAuthorizationUrl(): string
    {
        $client = $this->client();
        $client->setState('clinic');

        return $client->createAuthUrl();
    }

    public function clientForClinic(): ?GoogleClient
    {
        $integration = $this->clinicIntegration();

        if (! $integration->isConnected()) {
            Log::warning('Google Calendar de clinica no configurado');
            return null;
        }

        $token = $integration->getTokenDecrypted();
        if (blank($token)) {
            return null;
        }

        $client = $this->client();
        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            $refreshed = $this->refreshClinicToken($client);
            if (! $refreshed) {
                return null;
            }
        }

        return $client;
    }

    public function exchangeClinicCode(string $code): bool
    {
        try {
            $client = $this->client();
            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                Log::error('Error intercambiando codigo OAuth de clinica', [
                    'error' => $token['error_description'] ?? $token['error'],
                ]);
                return false;
            }

            $email = $this->getUserEmail($client);
            $integration = $this->clinicIntegration();

            $integration->update([
                'account_email' => $email,
                'calendar_id' => 'primary',
                'is_enabled' => true,
            ]);

            $integration->setToken($token);

            return true;
        } catch (\Throwable $e) {
            Log::error('Error en exchangeClinicCode OAuth', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function exchangeCode(Professional $professional, string $code): bool
    {
        try {
            $client = $this->client();
            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                Log::error('Error intercambiando codigo OAuth', [
                    'professional_id' => $professional->id,
                    'error' => $token['error_description'] ?? $token['error'],
                ]);
                return false;
            }

            $email = $this->getUserEmail($client);

            $professional->update([
                'google_calendar_email' => $email,
                'google_calendar_enabled' => true,
            ]);

            $professional->setGoogleCalendarToken($token);

            return true;
        } catch (\Throwable $e) {
            Log::error('Error en exchangeCode OAuth', [
                'professional_id' => $professional->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function getUserEmail(GoogleClient $client): ?string
    {
        try {
            $oauth2 = new GoogleOAuth2($client);
            return $oauth2->userinfo->get()->email;
        } catch (\Throwable $e) {
            Log::warning('No se pudo obtener email del usuario OAuth', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function refreshToken(Professional $professional, ?GoogleClient $client = null): bool
    {
        try {
            $client = $client ?? $this->client();
            $token = $professional->getGoogleCalendarTokenDecrypted();

            if (blank($token) || blank($token['refresh_token'] ?? null)) {
                Log::warning('No hay refresh_token para professional', [
                    'professional_id' => $professional->id,
                ]);
                return false;
            }

            $client->setAccessToken($token);
            $newToken = $client->refreshToken($token['refresh_token']);

            if (isset($newToken['error'])) {
                Log::error('Error refrescando token', [
                    'professional_id' => $professional->id,
                    'error' => $newToken['error_description'] ?? $newToken['error'],
                ]);
                return false;
            }

            if (!isset($newToken['refresh_token'])) {
                $newToken['refresh_token'] = $token['refresh_token'];
            }

            $professional->setGoogleCalendarToken($newToken);

            return true;
        } catch (\Throwable $e) {
            Log::error('Error en refreshToken', [
                'professional_id' => $professional->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function revokeToken(Professional $professional): bool
    {
        try {
            $client = $this->clientForProfessional($professional);
            if ($client) {
                $token = $professional->getGoogleCalendarTokenDecrypted();
                if ($token && ($token['access_token'] ?? null)) {
                    $client->revokeToken($token['access_token']);
                }
            }

            $professional->disconnectGoogleCalendar();

            return true;
        } catch (\Throwable $e) {
            Log::error('Error revocando token', [
                'professional_id' => $professional->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function refreshClinicToken(?GoogleClient $client = null): bool
    {
        try {
            $integration = $this->clinicIntegration();
            $client = $client ?? $this->client();
            $token = $integration->getTokenDecrypted();

            if (blank($token) || blank($token['refresh_token'] ?? null)) {
                Log::warning('No hay refresh_token para Google Calendar de clinica');
                return false;
            }

            $client->setAccessToken($token);
            $newToken = $client->refreshToken($token['refresh_token']);

            if (isset($newToken['error'])) {
                Log::error('Error refrescando token Google Calendar de clinica', [
                    'error' => $newToken['error_description'] ?? $newToken['error'],
                ]);
                return false;
            }

            if (! isset($newToken['refresh_token'])) {
                $newToken['refresh_token'] = $token['refresh_token'];
            }

            $integration->setToken($newToken);

            return true;
        } catch (\Throwable $e) {
            Log::error('Error en refreshClinicToken', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function revokeClinicToken(): bool
    {
        try {
            $integration = $this->clinicIntegration();
            $client = $this->clientForClinic();

            if ($client) {
                $token = $integration->getTokenDecrypted();
                if ($token && ($token['access_token'] ?? null)) {
                    $client->revokeToken($token['access_token']);
                }
            }

            $integration->disconnect();

            return true;
        } catch (\Throwable $e) {
            Log::error('Error revocando token Google Calendar de clinica', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function listClinicEvents(Carbon $start, Carbon $end): array
    {
        try {
            $client = $this->clientForClinic();
            if (! $client) {
                return [];
            }

            $integration = $this->clinicIntegration();
            $service = $this->makeCalendarService($client);
            $calendarId = $integration->calendar_id ?: 'primary';

            $events = $service->events->listEvents($calendarId, [
                'timeMin' => $start->toRfc3339String(),
                'timeMax' => $end->toRfc3339String(),
                'singleEvents' => true,
                'orderBy' => 'startTime',
            ]);

            return $events->getItems();
        } catch (\Throwable $e) {
            Log::error('Error listando eventos de Google Calendar de clinica', [
                'start' => $start->toDateTimeString(),
                'end' => $end->toDateTimeString(),
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    public function isClinicSlotAvailable(Carbon $start, Carbon $end, ?Professional $doctor = null): bool
    {
        if (! $this->hasClinicCalendar()) {
            return true;
        }

        foreach ($this->listClinicEvents($start, $end) as $event) {
            $eventStart = Carbon::parse($event->start->dateTime ?? $event->start->date);
            $eventEnd = Carbon::parse($event->end->dateTime ?? $event->end->date);

            if ($start->lessThan($eventEnd) && $end->greaterThan($eventStart)) {
                $private = $event->getExtendedProperties()?->getPrivate() ?? [];

                if (($private['source'] ?? null) === 'dental_commissions_mvp') {
                    if ($doctor && (string) ($private['doctor_id'] ?? '') !== (string) $doctor->id) {
                        continue;
                    }
                }

                return false;
            }
        }

        return true;
    }

    public function listEvents(Professional $professional, Carbon $start, Carbon $end): array
    {
        try {
            $client = $this->clientForProfessional($professional);
            if (!$client) {
                return [];
            }

            $service = $this->makeCalendarService($client);
            $calendarId = $professional->google_calendar_email ?? 'primary';

            $optParams = [
                'timeMin' => $start->toRfc3339String(),
                'timeMax' => $end->toRfc3339String(),
                'singleEvents' => true,
                'orderBy' => 'startTime',
            ];

            $events = $service->events->listEvents($calendarId, $optParams);

            return $events->getItems();
        } catch (\Throwable $e) {
            Log::error('Error listando eventos de Google Calendar', [
                'professional_id' => $professional->id,
                'start' => $start->toDateTimeString(),
                'end' => $end->toDateTimeString(),
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    public function isSlotAvailable(Professional $professional, Carbon $start, Carbon $end): bool
    {
        $events = $this->listEvents($professional, $start, $end);

        foreach ($events as $event) {
            $eventStart = Carbon::parse($event->start->dateTime ?? $event->start->date);
            $eventEnd = Carbon::parse($event->end->dateTime ?? $event->end->date);

            if ($start->lessThan($eventEnd) && $end->greaterThan($eventStart)) {
                return false;
            }
        }

        return true;
    }

    public function availableSlots(
        Professional $professional,
        Carbon $date,
        int $durationMinutes,
        Carbon $dayStart = null,
        Carbon $dayEnd = null,
    ): array {
        $dayStart = $dayStart ?? $date->copy()->setTime(8, 0);
        $dayEnd = $dayEnd ?? $date->copy()->setTime(18, 0);

        $events = $this->listEvents($professional, $dayStart, $dayEnd);

        $busy = [];
        foreach ($events as $event) {
            $busy[] = [
                'start' => Carbon::parse($event->start->dateTime ?? $event->start->date),
                'end' => Carbon::parse($event->end->dateTime ?? $event->end->date),
            ];
        }

        usort($busy, fn($a, $b) => $a['start']->timestamp <=> $b['start']->timestamp);

        $slots = [];
        $cursor = $dayStart->copy();

        foreach ($busy as $block) {
            while ($cursor->copy()->addMinutes($durationMinutes)->lessThanOrEqualTo($block['start']) &&
                   $cursor->lessThan($dayEnd)) {
                $slotEnd = $cursor->copy()->addMinutes($durationMinutes);
                if ($slotEnd->lessThanOrEqualTo($block['start']) && $slotEnd->lessThanOrEqualTo($dayEnd)) {
                    $slots[] = [
                        'start' => $cursor->copy(),
                        'end' => $slotEnd,
                    ];
                }
                $cursor->addMinutes(30);
            }
            $cursor = max($cursor->timestamp, $block['end']->timestamp);
            $cursor = Carbon::createFromTimestamp($cursor, $dayStart->timezone);
        }

        while ($cursor->copy()->addMinutes($durationMinutes)->lessThanOrEqualTo($dayEnd)) {
            $slotEnd = $cursor->copy()->addMinutes($durationMinutes);
            $slots[] = [
                'start' => $cursor->copy(),
                'end' => $slotEnd,
            ];
            $cursor->addMinutes(30);
        }

        return $slots;
    }

    public function buildCalendarEvent(Appointment $appointment): CalendarEvent
    {
        $event = new CalendarEvent();
        $doctorName = $appointment->doctor?->name;
        $patientName = $appointment->patient?->full_name ?? 'Pendiente';

        $event->setSummary('Cita: ' . ($doctorName ? $doctorName . ' - ' : '') . $patientName);
        $event->setDescription($this->buildEventDescription($appointment));

        $clinicTz = app(SocialCrmSettingsService::class)->clinicTimezone();

        $start = new EventDateTime();
        $localStart = $appointment->scheduled_at->copy()->setTimezone($clinicTz);
        $start->setDateTime($localStart->format('Y-m-d\TH:i:s'));
        $start->setTimeZone($clinicTz);
        $event->setStart($start);

        $end = new EventDateTime();
        $localEnd = $appointment->scheduled_at->copy()->addMinutes($appointment->duration_minutes ?? 60)->setTimezone($clinicTz);
        $end->setDateTime($localEnd->format('Y-m-d\TH:i:s'));
        $end->setTimeZone($clinicTz);
        $event->setEnd($end);

        $properties = new EventExtendedProperties();
        $properties->setPrivate([
            'source' => 'dental_commissions_mvp',
            'appointment_id' => (string) $appointment->id,
            'doctor_id' => (string) ($appointment->doctor_id ?? ''),
        ]);
        $event->setExtendedProperties($properties);

        return $event;
    }

    private function buildEventDescription(Appointment $appointment): string
    {
        $lines = [];
        $lines[] = 'Cita #' . $appointment->id;
        $lines[] = 'Paciente: ' . ($appointment->patient?->full_name ?? 'Por asignar');
        $lines[] = 'Doctor: ' . ($appointment->doctor?->name ?? 'Por asignar');
        $lines[] = 'Teléfono: ' . ($appointment->patient?->phone ?? 'N/A');

        if ($appointment->procedure) {
            $lines[] = 'Procedimiento: ' . $appointment->procedure->name;
        }

        if ($appointment->notes) {
            $lines[] = 'Notas: ' . $appointment->notes;
        }

        $lines[] = 'Origen: ' . ($appointment->source?->label() ?? 'N/A');
        $lines[] = 'Estado: ' . ($appointment->status?->label() ?? 'N/A');

        return implode("\n", $lines);
    }

    public function createOrUpdateEvent(Appointment $appointment): ?string
    {
        try {
            if (! $this->hasClinicCalendar()) {
                Log::info('Sin Google Calendar de clinica configurado', [
                    'appointment_id' => $appointment->id,
                ]);
                return null;
            }

            $client = $this->clientForClinic();
            if (!$client) {
                Log::warning('Sin cliente OAuth para Google Calendar de clinica', [
                    'appointment_id' => $appointment->id,
                ]);
                return null;
            }

            $integration = $this->clinicIntegration();
            $service = $this->makeCalendarService($client);
            $calendarId = $integration->calendar_id ?: 'primary';

            $event = $this->buildCalendarEvent($appointment);

            if ($appointment->external_appointment_id) {
                $updated = $service->events->update($calendarId, $appointment->external_appointment_id, $event);
                $eventId = $updated->getId();
            } else {
                $created = $service->events->insert($calendarId, $event);
                $eventId = $created->getId();
            }

            if (!$eventId) {
                Log::warning('Evento de Google Calendar no devolvió ID', [
                    'appointment_id' => $appointment->id,
                ]);
                return null;
            }

            $appointment->updateQuietly([
                'external_appointment_id' => $eventId,
                'external_calendar_id' => $calendarId,
                'external_provider' => 'google_calendar',
                'external_status' => 'active',
                'last_synced_at' => now(),
                'sync_error' => null,
            ]);

            return $eventId;
        } catch (\Throwable $e) {
            Log::error('Error sincronizando cita con Google Calendar', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);

            $appointment->updateQuietly([
                'external_status' => 'sync_error',
                'sync_error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function deleteEvent(Appointment $appointment): bool
    {
        try {
            $eventId = $appointment->external_appointment_id;
            if (!$eventId) {
                return true;
            }

            if (! $this->hasClinicCalendar()) {
                return true;
            }

            $client = $this->clientForClinic();
            if (!$client) {
                return false;
            }

            $integration = $this->clinicIntegration();
            $service = $this->makeCalendarService($client);
            $calendarId = $appointment->external_calendar_id ?: ($integration->calendar_id ?: 'primary');

            $service->events->delete($calendarId, $eventId);

            $appointment->updateQuietly([
                'external_appointment_id' => null,
                'external_status' => 'deleted',
                'last_synced_at' => now(),
                'sync_error' => null,
            ]);

            Log::info('Evento de Google Calendar eliminado', [
                'appointment_id' => $appointment->id,
                'event_id' => $eventId,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Error eliminando evento de Google Calendar', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);

            $appointment->updateQuietly([
                'external_status' => 'delete_error',
                'sync_error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function syncAppointment(Appointment $appointment): ?string
    {
        if ($appointment->status === AppointmentStatus::Cancelled) {
            $this->deleteEvent($appointment);
            return null;
        }

        if (in_array($appointment->status, [AppointmentStatus::NoShow, AppointmentStatus::Completed])) {
            return $appointment->external_appointment_id;
        }

        return $this->createOrUpdateEvent($appointment);
    }

    protected function makeCalendarService(GoogleClient $client): GoogleCalendar
    {
        return new GoogleCalendar($client);
    }
}
