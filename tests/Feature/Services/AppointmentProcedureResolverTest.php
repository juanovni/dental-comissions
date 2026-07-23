<?php

namespace Tests\Feature\Services;

use App\Models\Procedure;
use App\Services\AppointmentProcedureResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentProcedureResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_procedure_when_transcription_contains_multiple_options(): void
    {
        $cleaning = Procedure::factory()->create([
            'name' => 'Limpieza dental',
            'code' => 'LIMP001',
            'is_active' => true,
        ]);

        Procedure::factory()->create([
            'name' => 'Blanqueamiento',
            'code' => 'BLA001',
            'is_active' => true,
        ]);

        $resolved = app(AppointmentProcedureResolver::class)
            ->findByName('y procedimientos blanqueamiento dental o limpieza dental');

        $this->assertTrue($cleaning->is($resolved));
    }

    public function test_resolves_common_stt_error_for_blanqueamiento(): void
    {
        $whitening = Procedure::factory()->create([
            'name' => 'Blanqueamiento',
            'code' => 'BLA001',
            'is_active' => true,
        ]);

        $resolved = app(AppointmentProcedureResolver::class)
            ->findByName('lancamiento dental');

        $this->assertTrue($whitening->is($resolved));
    }
}
