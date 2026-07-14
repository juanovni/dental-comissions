<?php

namespace App\Services;

use App\Enums\LocalLanguagePatternType;
use App\Models\LocalLanguagePattern;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LocalLanguagePatternService
{
    private const CACHE_KEY = 'local_language_patterns.active';

    public function normalize(string $text): string
    {
        return Str::of($text)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9\s]+/u', ' ')
            ->squish()
            ->toString();
    }

    public function match(string $text, LocalLanguagePatternType|string $type, string $locale = 'es_EC'): ?array
    {
        return $this->matches($text, [$type], $locale)[0] ?? null;
    }

    public function matches(string $text, array $types, string $locale = 'es_EC'): array
    {
        $normalizedText = $this->normalize($text);

        if ($normalizedText === '') {
            return [];
        }

        $typeValues = array_map(
            fn (LocalLanguagePatternType|string $type): string => $type instanceof LocalLanguagePatternType ? $type->value : $type,
            $types,
        );

        return collect($this->patterns())
            ->whereIn('type', $typeValues)
            ->where('locale', $locale)
            ->filter(fn (array $pattern): bool => $pattern['normalized_phrase'] !== ''
                && str_contains($normalizedText, $pattern['normalized_phrase']))
            ->sortByDesc(fn (array $pattern): int => strlen($pattern['normalized_phrase']))
            ->values()
            ->all();
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function patterns(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function (): array {
            return LocalLanguagePattern::query()
                ->active()
                ->get(['id', 'type', 'phrase', 'normalized_phrase', 'value', 'locale'])
                ->map(fn (LocalLanguagePattern $pattern): array => [
                    'id' => $pattern->id,
                    'type' => $pattern->type->value,
                    'phrase' => $pattern->phrase,
                    'normalized_phrase' => $pattern->normalized_phrase,
                    'value' => $pattern->value,
                    'locale' => $pattern->locale,
                ])
                ->all();
        });
    }
}
