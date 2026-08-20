<?php

namespace Tests\Feature\Models;

use App\Models\Clinic;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\Professional;
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

    public function test_appointment_related_options_do_not_mix_between_tenants(): void
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

        $patientA = Patient::factory()->create(['clinic_id' => $clinicA->id, 'full_name' => 'Paciente A']);
        $doctorA = Professional::factory()->doctor()->create(['clinic_id' => $clinicA->id, 'name' => 'Doctor A']);
        $procedureA = Procedure::factory()->create(['clinic_id' => $clinicA->id, 'name' => 'Procedimiento A']);
        Appointment::factory()->create([
            'clinic_id' => $clinicA->id,
            'patient_id' => $patientA->id,
            'doctor_id' => $doctorA->id,
            'procedure_id' => $procedureA->id,
        ]);

        $patientB = Patient::factory()->create(['clinic_id' => $clinicB->id, 'full_name' => 'Paciente B']);
        $doctorB = Professional::factory()->doctor()->create(['clinic_id' => $clinicB->id, 'name' => 'Doctor B']);
        $procedureB = Procedure::factory()->create(['clinic_id' => $clinicB->id, 'name' => 'Procedimiento B']);
        Appointment::factory()->create([
            'clinic_id' => $clinicB->id,
            'patient_id' => $patientB->id,
            'doctor_id' => $doctorB->id,
            'procedure_id' => $procedureB->id,
        ]);

        app(TenantContext::class)->set($clinicA);

        $this->assertSame(['Paciente A'], Patient::query()->forCurrentTenant()->pluck('full_name')->all());
        $this->assertSame(['Doctor A'], Professional::query()->forCurrentTenant()->where('role', 'doctor')->pluck('name')->all());
        $this->assertSame(['Procedimiento A'], Procedure::query()->forCurrentTenant()->pluck('name')->all());
        $this->assertSame(['Paciente A'], Appointment::query()->forCurrentTenant()->with('patient')->get()->pluck('patient.full_name')->all());

        app(TenantContext::class)->set($clinicB);

        $this->assertSame(['Paciente B'], Patient::query()->forCurrentTenant()->pluck('full_name')->all());
        $this->assertSame(['Doctor B'], Professional::query()->forCurrentTenant()->where('role', 'doctor')->pluck('name')->all());
        $this->assertSame(['Procedimiento B'], Procedure::query()->forCurrentTenant()->pluck('name')->all());
        $this->assertSame(['Paciente B'], Appointment::query()->forCurrentTenant()->with('patient')->get()->pluck('patient.full_name')->all());
    }
}
