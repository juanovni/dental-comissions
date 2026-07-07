<?php

namespace App\Services;

use App\Models\Professional;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Oauth2 as GoogleOAuth2;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    private function config(): array
    {
        return config('services.google_oauth', []);
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

    public function listEvents(Professional $professional, Carbon $start, Carbon $end): array
    {
        try {
            $client = $this->clientForProfessional($professional);
            if (!$client) {
                return [];
            }

            $service = new GoogleCalendar($client);
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
}
