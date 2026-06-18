<?php

namespace App\Services;

class SocialLinkEventMapper
{
    private const MAP = [
        'view' => [
            'label' => 'Abrio el Smart Link',
            'icon' => 'eye',
            'color' => 'blue',
            'group' => 'navigation',
        ],
        'revisit' => [
            'label' => 'Volvio a abrir el Smart Link',
            'icon' => 'arrow-path',
            'color' => 'indigo',
            'group' => 'navigation',
        ],
        'engagement_ping' => [
            'label' => 'Permanecio en la landing',
            'icon' => 'clock',
            'color' => 'gray',
            'group' => 'engagement',
        ],
        'duration_threshold' => [
            'label' => 'Supero el umbral de interes',
            'icon' => 'fire',
            'color' => 'orange',
            'group' => 'engagement',
        ],
        'video_start' => [
            'label' => 'Inicio el video',
            'icon' => 'play',
            'color' => 'cyan',
            'group' => 'video',
        ],
        'video_25' => [
            'label' => 'Vio 25% del video',
            'icon' => 'play',
            'color' => 'cyan',
            'group' => 'video',
            'progress' => 25,
        ],
        'video_50' => [
            'label' => 'Vio 50% del video',
            'icon' => 'play',
            'color' => 'teal',
            'group' => 'video',
            'progress' => 50,
        ],
        'video_75' => [
            'label' => 'Vio 75% del video',
            'icon' => 'play',
            'color' => 'emerald',
            'group' => 'video',
            'progress' => 75,
        ],
        'video_complete' => [
            'label' => 'Completo el video',
            'icon' => 'check-circle',
            'color' => 'green',
            'group' => 'video',
            'progress' => 100,
        ],
        'whatsapp_click' => [
            'label' => 'Hizo clic para continuar por WhatsApp',
            'icon' => 'chat-bubble-left',
            'color' => 'green',
            'group' => 'conversion',
        ],
    ];

    public static function get(string $eventType): array
    {
        return self::MAP[$eventType] ?? [
            'label' => ucfirst(str_replace('_', ' ', $eventType)),
            'icon' => 'information-circle',
            'color' => 'gray',
            'group' => 'other',
        ];
    }

    public static function label(string $eventType): string
    {
        return self::get($eventType)['label'];
    }

    public static function icon(string $eventType): string
    {
        return self::get($eventType)['icon'];
    }

    public static function color(string $eventType): string
    {
        return self::get($eventType)['color'];
    }

    public static function group(string $eventType): string
    {
        return self::get($eventType)['group'];
    }

    public static function progress(?string $eventType): ?int
    {
        return self::get($eventType)['progress'] ?? null;
    }

    public static function all(): array
    {
        return self::MAP;
    }

    public static function videoProgress(): array
    {
        return array_filter(self::MAP, fn (array $entry) => ($entry['progress'] ?? null) !== null);
    }
}
