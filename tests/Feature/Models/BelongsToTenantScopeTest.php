<?php

namespace Tests\Feature\Models;

use App\Models\Clinic;
use App\Models\Patient;
use App\Support\TenantContext;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BelongsToTenantScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_for_current_tenant_filters_by_active_tenant_context(): void
    {
        $clinicA = Clinic::create([
            'name' => 'Clinica A',
            'slug' => 'clinica-a',
            'subdomain' => 'clinica-a',
            'primary_domain' => 'clinica-a.localhost',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
        $clinicB = Clinic::create([
            'name' => 'Clinica B',
            'slug' => 'clinica-b',
            'subdomain' => 'clinica-b',
            'primary_domain' => 'clinica-b.localhost',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);

        Patient::create([
            'clinic_id' => $clinicA->id,
            'full_name' => 'Paciente A',
            'normalized_name' => 'paciente a',
        ]);
        Patient::create([
            'clinic_id' => $clinicB->id,
            'full_name' => 'Paciente B',
            'normalized_name' => 'paciente b',
        ]);

        app(TenantContext::class)->set($clinicA);

        $this->assertSame(1, Patient::query()->forCurrentTenant()->count());
        $this->assertSame('Paciente A', Patient::query()->forCurrentTenant()->first()?->full_name);
    }

    public function test_for_current_tenant_fails_closed_inside_clinic_panel_without_tenant(): void
    {
        $clinic = Clinic::create([
            'name' => 'Clinica Demo',
            'slug' => 'clinica-demo',
            'subdomain' => 'clinica-demo',
            'primary_domain' => 'clinica-demo.localhost',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);

        Patient::create([
            'clinic_id' => $clinic->id,
            'full_name' => 'Paciente Demo',
            'normalized_name' => 'paciente demo',
        ]);

        Filament::setCurrentPanel('clinic');
        app(TenantContext::class)->clear();

        $this->assertSame(0, Patient::query()->forCurrentTenant()->count());

        Filament::setCurrentPanel(null);
    }
}
