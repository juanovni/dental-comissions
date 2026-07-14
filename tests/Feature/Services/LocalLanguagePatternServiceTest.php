<?php

namespace Tests\Feature\Services;

use App\Enums\LocalLanguagePatternType;
use App\Models\LocalLanguagePattern;
use App\Services\LocalLanguagePatternService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalLanguagePatternServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalizes_accents_and_punctuation(): void
    {
        $service = app(LocalLanguagePatternService::class);

        $this->assertSame('en la tardecita', $service->normalize('¡En la tardecita!'));
        $this->assertSame('bacan', $service->normalize('Bacán'));
    }

    public function test_matches_active_pattern_by_type(): void
    {
        LocalLanguagePattern::create([
            'type' => LocalLanguagePatternType::Period,
            'phrase' => 'tipo tarde',
            'value' => 'afternoon',
            'locale' => 'es_EC',
            'is_active' => true,
            'source' => 'manual',
        ]);

        $match = app(LocalLanguagePatternService::class)->match(
            'Quiero una cita tipo tarde',
            LocalLanguagePatternType::Period,
        );

        $this->assertNotNull($match);
        $this->assertSame('tipo tarde', $match['phrase']);
        $this->assertSame('afternoon', $match['value']);
    }

    public function test_ignores_inactive_patterns(): void
    {
        LocalLanguagePattern::create([
            'type' => LocalLanguagePatternType::AppointmentIntent,
            'phrase' => 'me agenda un chance',
            'value' => 'appointment_interest',
            'locale' => 'es_EC',
            'is_active' => false,
            'source' => 'manual',
        ]);

        $match = app(LocalLanguagePatternService::class)->match(
            'me agenda un chance para mañana',
            LocalLanguagePatternType::AppointmentIntent,
        );

        $this->assertNull($match);
    }
}
