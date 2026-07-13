<?php

namespace App\Services;

use App\Models\SocialComment;
use App\Models\WhatsappMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AppointmentIntentService
{
    private array $dias = [
        'lunes' => 'monday', 'martes' => 'tuesday', 'miercoles' => 'wednesday',
        'miércoles' => 'wednesday', 'jueves' => 'thursday', 'viernes' => 'friday',
        'sabado' => 'saturday', 'sábado' => 'saturday', 'domingo' => 'sunday',
    ];

    private array $meses = [
        'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
        'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
        'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12,
    ];

    public function analyze(
        SocialComment $comment,
        WhatsappMessage $message,
        string $aiIntent,
        array $aiAppointmentCandidate,
    ): array {
        $body = $message->message_body ?? '';

        $result = [
            'has_intent' => false,
            'intent_type' => null,
            'preferred_date_text' => null,
            'preferred_time_text' => null,
            'preferred_date_parsed' => null,
            'preferred_time_parsed' => null,
            'preferred_period' => null,
            'confidence' => 0,
            'extraction_source' => 'none',
        ];

        $wantsAppointment = $aiAppointmentCandidate['wants_appointment'] ?? false;
        $aiDateText = $aiAppointmentCandidate['preferred_date_text'] ?? null;
        $aiTimeText = $aiAppointmentCandidate['preferred_time_text'] ?? null;

        if (in_array($aiIntent, ['appointment_interest', 'ready_to_book'], true) || $wantsAppointment) {
            $result['has_intent'] = true;
            $result['intent_type'] = $aiIntent === 'ready_to_book' ? 'ready_to_book' : 'appointment_interest';
            $result['preferred_date_text'] = $aiDateText;
            $result['preferred_time_text'] = $aiTimeText;

            if ($aiDateText || $aiTimeText) {
                $parsed = $this->parseDateTimeText($aiDateText, $aiTimeText);
                $result['preferred_date_parsed'] = $parsed['date'];
                $result['preferred_time_parsed'] = $parsed['time'];
                $result['preferred_period'] = $parsed['period'];
                $result['confidence'] = $parsed['date'] || $parsed['time'] ? 80 : 60;
                $result['extraction_source'] = 'ai';
            }

            if (!$result['preferred_date_parsed'] && !$result['preferred_time_parsed']) {
                $localParsed = $this->extractFromText($body);
                if ($localParsed['date'] || $localParsed['time']) {
                    $result['preferred_date_parsed'] = $localParsed['date'];
                    $result['preferred_time_parsed'] = $localParsed['time'];
                    $result['preferred_period'] = $localParsed['period'];
                    $result['preferred_date_text'] ??= $localParsed['date_text'];
                    $result['preferred_time_text'] ??= $localParsed['time_text'];
                    $result['confidence'] = 65;
                    $result['extraction_source'] = 'local_fallback';
                }
            }

            if (!$result['preferred_date_parsed']) {
                $result['confidence'] = 40;
            }
        }

        if ($result['has_intent']) {
            $comment->update([
                'appointment_scheduled_at' => $result['preferred_date_parsed']
                    ? ($result['preferred_time_parsed']
                        ? Carbon::parse($result['preferred_date_parsed'] . ' ' . $result['preferred_time_parsed'])
                        : Carbon::parse($result['preferred_date_parsed']))
                    : null,
                'ai_intent' => $result['intent_type'],
                'ai_confidence' => $result['confidence'],
            ]);

            $comment->actions()->create([
                'action' => \App\Enums\SocialCommentActionType::BookingIntentDetected,
                'performed_by' => null,
                'notes' => 'Intencion de agendamiento detectada: ' . ($result['intent_type'] ?? 'desconocida'),
                'external_response' => $result,
            ]);
        }

        return $result;
    }

    public function extractFromText(string $text): array
    {
        $normalized = mb_strtolower(trim($text));

        $result = [
            'date' => null,
            'time' => null,
            'date_text' => null,
            'time_text' => null,
            'period' => null,
        ];

        $now = Carbon::now()->startOfDay();

        $dateResult = $this->extractDate($normalized, $now);
        $timeResult = $this->extractTime($normalized);

        if ($dateResult['parsed']) {
            $result['date'] = $dateResult['parsed'];
            $result['date_text'] = $dateResult['text'];
        }

        if ($timeResult['parsed']) {
            $result['time'] = $timeResult['parsed'];
            $result['time_text'] = $timeResult['text'];
            $result['period'] = $timeResult['period'] ?? null;
        }

        return $result;
    }

    private function extractDate(string $text, Carbon $now): array
    {
        $result = ['parsed' => null, 'text' => null, 'period' => null];

        if (str_contains($text, 'pasado mañana') || str_contains($text, 'pasadomañana') || str_contains($text, 'pasado manana')) {
            $result['parsed'] = $now->copy()->addDays(2)->format('Y-m-d');
            $result['text'] = 'pasado mañana';
            return $result;
        }

        if (preg_match('/\b(?:el\s+)?(?:dia\s+)?(' . implode('|', array_keys($this->dias)) . ')\s+(\d{1,2})\b/u', $text, $matches)) {
            $day = (int) $matches[2];
            $month = $now->month;
            $year = $now->year;

            if (checkdate($month, $day, $year)) {
                $date = $now->copy()->setDate($year, $month, $day)->startOfDay();

                if ($date->lessThan($now)) {
                    $date->addMonthNoOverflow();
                }

                $result['parsed'] = $date->format('Y-m-d');
                $result['text'] = $matches[0];
            }

            return $result;
        }

        if (preg_match('/\b(' . implode('|', array_keys($this->dias)) . ')\b/u', $text, $matches)) {
            $targetDay = $this->dias[$matches[1]];
            $dayOfWeek = $now->dayOfWeekIso;
            $dayNames = ['monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6, 'sunday' => 7];
            $targetDayOfWeek = $dayNames[$targetDay] ?? null;

            $isNext = (bool) preg_match('/pr[oó]xim[oa]|siguiente|que\s+viene/', $text);
            $isThis = !$isNext;

            if ($targetDayOfWeek !== null) {
                $diff = ($targetDayOfWeek - $dayOfWeek + 7) % 7;
                if ($diff === 0) {
                    $diff = $isNext ? 7 : 0;
                } elseif ($isNext && $diff < 7) {
                    $diff += 7;
                }
                $result['parsed'] = $now->copy()->addDays($diff)->format('Y-m-d');
                $result['text'] = $matches[0];
            }
            return $result;
        }

        if (preg_match('/\b(mañana|manana)\b/u', $text)) {
            $result['parsed'] = $now->copy()->addDay()->format('Y-m-d');
            $result['text'] = 'mañana';
            return $result;
        }

        if (str_contains($text, 'hoy')) {
            $result['parsed'] = $now->format('Y-m-d');
            $result['text'] = 'hoy';
            return $result;
        }

        if (preg_match('/\b(\d{1,2})\s*(?:de\s*)?(' . implode('|', array_keys($this->meses)) . ')(?:\s*de\s*(\d{4}))?\b/u', $text, $matches)) {
            $day = (int) $matches[1];
            $month = $this->meses[$matches[2]];
            $year = !empty($matches[3]) ? (int) $matches[3] : $now->year;

            if (checkdate($month, $day, $year)) {
                $result['parsed'] = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $result['text'] = $matches[0];
            }
            return $result;
        }

        if (preg_match('/\b(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?\b/', $text, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = !empty($matches[3]) ? (int) $matches[3] : $now->year;

            if (strlen((string)$year) === 2) {
                $year += 2000;
            }

            if (checkdate($month, $day, $year)) {
                $result['parsed'] = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $result['text'] = $matches[0];
            }
            return $result;
        }

        if (preg_match('/\b(\d{4})-(\d{2})-(\d{2})\b/', $text, $matches)) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];
            $day = (int) $matches[3];

            if (checkdate($month, $day, $year)) {
                $result['parsed'] = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $result['text'] = $matches[0];
            }
            return $result;
        }

        if (preg_match('/esta\s+semana/i', $text)) {
            $endOfWeek = $now->copy()->endOfWeek(Carbon::FRIDAY);
            if ($endOfWeek->greaterThan($now)) {
                $result['parsed'] = $endOfWeek->format('Y-m-d');
                $result['text'] = 'esta semana';
                $result['is_approximate'] = true;
            }
            return $result;
        }

        if (preg_match('/pr[óo]xima?\s+semana|la\s+semana\s+(?:que\s+)?viene/i', $text)) {
            $nextWeek = $now->copy()->addWeek()->startOfWeek(Carbon::MONDAY);
            $result['parsed'] = $nextWeek->format('Y-m-d');
            $result['text'] = 'próxima semana';
            $result['is_approximate'] = true;
            return $result;
        }

        return $result;
    }

    private function extractTime(string $text): array
    {
        $result = ['parsed' => null, 'text' => null];

        if (preg_match('/a\s*las\s*(\d{1,2})(?:\s*[.:]\s*(\d{2}))?(?:\s*(am|pm|a\.m\.|p\.m\.))?/i', $text, $matches)) {
            $hour = (int) $matches[1];
            $minutes = !empty($matches[2]) ? (int) $matches[2] : 0;
            $meridiem = !empty($matches[3]) ? mb_strtolower($matches[3]) : null;

            if ($hour >= 0 && $hour <= 23 && $minutes >= 0 && $minutes <= 59) {
                if ($meridiem) {
                    if (in_array($meridiem, ['pm', 'p.m.', 'p. m.'])) {
                        $hour = $hour < 12 ? $hour + 12 : $hour;
                    } elseif (in_array($meridiem, ['am', 'a.m.', 'a. m.']) && $hour === 12) {
                        $hour = 0;
                    }
                } elseif ($hour < 7) {
                    $hour += 12;
                }

                $result['parsed'] = sprintf('%02d:%02d', $hour, $minutes);
                $result['text'] = $matches[0];
            }
            return $result;
        }

        if (preg_match('/\b(\d{1,2})[.:](\d{2})\b/', $text, $matches)) {
            $hour = (int) $matches[1];
            $minutes = (int) $matches[2];

            if ($hour >= 0 && $hour <= 23 && $minutes >= 0 && $minutes <= 59) {
                $result['parsed'] = sprintf('%02d:%02d', $hour, $minutes);
                $result['text'] = $matches[0];
            }
            return $result;
        }

        $periods = [
            'en la mañana' => ['09:00', 'morning'], 'en la manana' => ['09:00', 'morning'],
            'por la mañana' => ['09:00', 'morning'], 'por la manana' => ['09:00', 'morning'],
            'en la tarde' => ['15:00', 'afternoon'], 'por la tarde' => ['15:00', 'afternoon'],
            'en la noche' => ['17:00', 'night'], 'por la noche' => ['17:00', 'night'],
        ];

        foreach ($periods as $phrase => [$time, $period]) {
            if (str_contains($text, $phrase)) {
                $result['parsed'] = $time;
                $result['text'] = $phrase;
                $result['period'] = $period;
                return $result;
            }
        }

        return $result;
    }

    public function parseDateTimeText(?string $dateText, ?string $timeText): array
    {
        $result = ['date' => null, 'time' => null, 'period' => null];

        if ($dateText) {
            $parsed = $this->extractDate(mb_strtolower($dateText), Carbon::now()->startOfDay());
            $result['date'] = $parsed['parsed'];

            $timeParsedFromDateText = $this->extractTime(mb_strtolower($dateText));
            $result['period'] = $timeParsedFromDateText['period'] ?? null;

            if (! $result['time']) {
                $result['time'] = $timeParsedFromDateText['parsed'];
            }
        }

        if ($timeText) {
            $parsed = $this->extractTime(mb_strtolower($timeText));
            $result['time'] = $parsed['parsed'];
                $result['period'] = $parsed['period'] ?? null;
        }

        return $result;
    }

    public function analyzeConversationHistory(array $messages): array
    {
        $fullText = '';
        foreach ($messages as $msg) {
            if (($msg['role'] ?? '') === 'user') {
                $fullText .= ' ' . ($msg['content'] ?? '');
            }
        }

        return $this->extractFromText(trim($fullText));
    }
}
