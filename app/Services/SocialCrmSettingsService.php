<?php

namespace App\Services;

use App\Enums\SocialConversionStatus;
use App\Models\SocialCrmSetting;
use Illuminate\Support\Facades\Cache;

class SocialCrmSettingsService
{
    private const CACHE_KEY = 'social_crm_settings.active';

    public function get(string $key, mixed $default = null): mixed
    {
        $setting = $this->settings()[$key] ?? null;

        if (! $setting) {
            return $default;
        }

        return $this->castValue($setting['value'] ?? null, $setting['value_type'] ?? 'string', $default);
    }

    public function archivedConversionStatuses(): array
    {
        $statuses = $this->get('social_inbox_archived_conversion_statuses', [
            SocialConversionStatus::IdentityLinked->value,
            SocialConversionStatus::PendingPatientCreation->value,
            SocialConversionStatus::AppointmentCreated->value,
            SocialConversionStatus::Converted->value,
        ]);

        return collect($statuses)
            ->filter(fn (mixed $status): bool => is_string($status) && $status !== '')
            ->values()
            ->all();
    }

    public function archiveOnReview(): bool
    {
        return (bool) $this->get('social_inbox_archive_on_review', true);
    }

    public function whatsappReplyTemplate(): string
    {
        return (string) $this->get(
            'social_whatsapp_reply_template',
            'Hola! Para darte seguimiento personalizado por WhatsApp, abre este link: {whatsapp_link}',
        );
    }

    public function autoCopyToast(): string
    {
        return (string) $this->get(
            'social_whatsapp_autocopy_toast',
            'Link copiado. Pegalo ahora en el chat de Instagram.',
        );
    }

    public function scoreForTokenGenerated(): int
    {
        return (int) $this->get('social_score_token_generated', 30);
    }

    public function scoreForSmartLinkClick(): int
    {
        return (int) $this->get('social_score_smart_link_click', 15);
    }

    public function scoreForSmartLinkRevisit(): int
    {
        return (int) $this->get('social_score_smart_link_revisit', 10);
    }

    public function scoreForReheatedRevisitBonus(): int
    {
        return (int) $this->get('social_score_reheated_revisit_bonus', 10);
    }

    public function hotLeadThreshold(): int
    {
        return (int) $this->get('social_hot_lead_threshold', 75);
    }

    public function reheatedAfterHours(): int
    {
        return (int) $this->get('social_reheated_after_hours', 72);
    }

    public function smartLinkDurationThresholdSeconds(): int
    {
        return (int) $this->get('social_smart_link_duration_threshold_seconds', 60);
    }

    public function smartLinkPingSeconds(): int
    {
        return max(5, (int) $this->get('social_smart_link_ping_seconds', 15));
    }

    public function smartLinkDurationScore(): int
    {
        return (int) $this->get('social_smart_link_duration_score', 20);
    }

    public function smartLinkDurationAlert(): string
    {
        return (string) $this->get(
            'social_smart_link_duration_alert',
            'Paciente esta muy interesado en los resultados visuales.',
        );
    }

    public function smartLinkContentBlocks(): array
    {
        $blocks = $this->get('social_smart_link_content_blocks', []);

        return is_array($blocks) ? $blocks : [];
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function settings(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function (): array {
            return SocialCrmSetting::query()
                ->where('is_active', true)
                ->get(['key', 'value', 'value_type'])
                ->keyBy('key')
                ->map(fn (SocialCrmSetting $setting): array => [
                    'value' => $setting->value,
                    'value_type' => $setting->value_type,
                ])
                ->all();
        });
    }

    private function castValue(mixed $value, string $type, mixed $default): mixed
    {
        if ($value === null) {
            return $default;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'array' => is_array($value) ? $value : $default,
            default => is_scalar($value) ? (string) $value : $default,
        };
    }
}
