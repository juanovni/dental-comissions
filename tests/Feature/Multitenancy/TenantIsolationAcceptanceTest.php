<?php

namespace Tests\Feature\Multitenancy;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\Professional;
use App\Models\SocialCrmSetting;
use App\Models\User;
use App\Services\ClinicProvisioningService;
use App\Services\SocialCrmSettingsService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinicA;
    private Clinic $clinicB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinicA = $this->createClinic('Clinica A', 'clinica-a', 'America/Guayaquil');
        $this->clinicB = $this->createClinic('Clinica B', 'clinica-b', 'America/Bogota');
    }

    private function createClinic(string $name, string $slug, string $timezone = 'UTC'): Clinic
    {
        return Clinic::create([
            'name' => $name,
            'slug' => $slug,
            'subdomain' => $slug,
            'primary_domain' => $slug.'.localhost',
            'currency' => 'USD',
            'timezone' => $timezone,
            'status' => 'active',
            'settings' => [
                'locale' => 'es',
                'storage_prefix' => 'clinics/{id}/',
                'appointments' => ['workdays' => ['monday'], 'start_time' => '09:00', 'end_time' => '17:00'],
                'crm' => [],
                'integrations' => [],
            ],
        ]);
    }

    private function asUser(Clinic $clinic): User
    {
        $user = User::factory()->create();
        $clinic->users()->attach($user, ['role' => 'admin', 'is_default' => true, 'is_active' => true]);

        return $user;
    }

    private function setSetting(Clinic $clinic, string $key, mixed $value, string $type = 'string'): void
    {
        SocialCrmSetting::create([
            'clinic_id' => $clinic->id,
            'setting_group' => 'citas',
            'key' => $key,
            'label' => $key,
            'value_type' => $type,
            'value' => $value,
            'is_active' => true,
        ]);
    }

    // ── TEST 1: Pacientes aislados ──

    public function test_paciente_clinica_a_no_visible_en_clinica_b(): void
    {
        Patient::factory()->create(['clinic_id' => $this->clinicA->id, 'full_name' => 'Juan Perez']);
        Patient::factory()->create(['clinic_id' => $this->clinicB->id, 'full_name' => 'Maria Garcia']);

        app(TenantContext::class)->set($this->clinicA);
        $pacientesA = Patient::query()->forCurrentTenant()->pluck('full_name')->all();

        app(TenantContext::class)->set($this->clinicB);
        $pacientesB = Patient::query()->forCurrentTenant()->pluck('full_name')->all();

        $this->assertContains('Juan Perez', $pacientesA);
        $this->assertNotContains('Maria Garcia', $pacientesA);
        $this->assertContains('Maria Garcia', $pacientesB);
        $this->assertNotContains('Juan Perez', $pacientesB);
    }

    // ── TEST 2: Citas aisladas ──

    public function test_citas_clinica_a_no_visibles_en_clinica_b(): void
    {
        $patientA = Patient::factory()->create(['clinic_id' => $this->clinicA->id]);
        $doctorA = Professional::factory()->doctor()->create(['clinic_id' => $this->clinicA->id]);
        $procA = Procedure::factory()->create(['clinic_id' => $this->clinicA->id]);
        Appointment::factory()->create([
            'clinic_id' => $this->clinicA->id, 'patient_id' => $patientA->id,
            'doctor_id' => $doctorA->id, 'procedure_id' => $procA->id,
            'scheduled_at' => now()->addDay()->setHour(10),
        ]);

        $patientB = Patient::factory()->create(['clinic_id' => $this->clinicB->id]);
        $doctorB = Professional::factory()->doctor()->create(['clinic_id' => $this->clinicB->id]);
        $procB = Procedure::factory()->create(['clinic_id' => $this->clinicB->id]);
        Appointment::factory()->create([
            'clinic_id' => $this->clinicB->id, 'patient_id' => $patientB->id,
            'doctor_id' => $doctorB->id, 'procedure_id' => $procB->id,
            'scheduled_at' => now()->addDay()->setHour(14),
        ]);

        app(TenantContext::class)->set($this->clinicA);
        $this->assertSame(1, Appointment::query()->forCurrentTenant()->count());

        app(TenantContext::class)->set($this->clinicB);
        $this->assertSame(1, Appointment::query()->forCurrentTenant()->count());
    }

    // ── TEST 3: Doctores/procedimientos aislados ──

    public function test_doctores_y_procedimientos_aislados_por_tenant(): void
    {
        Professional::factory()->doctor()->create(['clinic_id' => $this->clinicA->id, 'name' => 'Dr. AAA']);
        Professional::factory()->doctor()->create(['clinic_id' => $this->clinicB->id, 'name' => 'Dr. BBB']);
        Procedure::factory()->create(['clinic_id' => $this->clinicA->id, 'name' => 'Limpieza']);
        Procedure::factory()->create(['clinic_id' => $this->clinicB->id, 'name' => 'Extraccion']);

        app(TenantContext::class)->set($this->clinicA);
        $this->assertSame(['Dr. AAA'], Professional::query()->forCurrentTenant()->where('role', 'doctor')->pluck('name')->all());
        $this->assertSame(['Limpieza'], Procedure::query()->forCurrentTenant()->pluck('name')->all());

        app(TenantContext::class)->set($this->clinicB);
        $this->assertSame(['Dr. BBB'], Professional::query()->forCurrentTenant()->where('role', 'doctor')->pluck('name')->all());
        $this->assertSame(['Extraccion'], Procedure::query()->forCurrentTenant()->pluck('name')->all());
    }

    // ── TEST 4: Slug/subdomain/domain unicos ──

    public function test_slug_debe_ser_unico(): void
    {
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        Clinic::create([
            'name' => 'Dup', 'slug' => 'clinica-a', 'subdomain' => 'x',
            'primary_domain' => 'x.localhost', 'currency' => 'USD', 'timezone' => 'UTC', 'status' => 'active',
        ]);
    }

    public function test_subdomain_debe_ser_unico(): void
    {
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        Clinic::create([
            'name' => 'Dup', 'slug' => 'x', 'subdomain' => 'clinica-a',
            'primary_domain' => 'x.localhost', 'currency' => 'USD', 'timezone' => 'UTC', 'status' => 'active',
        ]);
    }

    public function test_primary_domain_debe_ser_unico(): void
    {
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        Clinic::create([
            'name' => 'Dup', 'slug' => 'x', 'subdomain' => 'x',
            'primary_domain' => 'clinica-a.localhost', 'currency' => 'USD', 'timezone' => 'UTC', 'status' => 'active',
        ]);
    }

    // ── TEST 5: Host resuelve clinica correcta ──

    public function test_host_resuelve_clinica_correcta(): void
    {
        $this->get('http://clinica-a.localhost:8080/check-in/clinica-a')->assertOk();
        $this->get('http://clinica-b.localhost:8080/check-in/clinica-b')->assertOk();
    }

    public function test_host_clinica_a_no_accede_ruta_clinica_b(): void
    {
        $this->get('http://clinica-a.localhost:8080/check-in/clinica-b')->assertNotFound();
    }

    // ── TEST 6: Usuario no asignado no accede ──

    public function test_usuario_no_asignado_no_accede_panel_clinica(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('http://clinica-a.localhost:8080/admin')
            ->assertStatus(404);
    }

    public function test_usuario_clinica_a_no_accede_panel_clinica_b(): void
    {
        $user = $this->asUser($this->clinicA);

        $this->actingAs($user)
            ->get('http://clinica-b.localhost:8080/admin')
            ->assertStatus(404);
    }

    // ── TEST 7: Check-in publico no cruzado ──

    public function test_check_in_publico_no_encuentra_cita_otra_clinica(): void
    {
        $patientA = Patient::factory()->create(['clinic_id' => $this->clinicA->id, 'phone' => '+593999111222']);
        Appointment::factory()->create([
            'clinic_id' => $this->clinicA->id, 'patient_id' => $patientA->id,
            'scheduled_at' => now()->setHour(10), 'status' => 'confirmed',
        ]);

        $patientB = Patient::factory()->create(['clinic_id' => $this->clinicB->id, 'phone' => '+593999333444']);
        Appointment::factory()->create([
            'clinic_id' => $this->clinicB->id, 'patient_id' => $patientB->id,
            'scheduled_at' => now()->setHour(10), 'status' => 'confirmed',
        ]);

        $this->withoutMiddleware();

        $this->post('/check-in/clinica-a', ['identifier' => '+593999111222'])->assertRedirect('/check-in/clinica-a');
        $this->post('/check-in/clinica-b', ['identifier' => '+593999333444'])->assertRedirect('/check-in/clinica-b');
        $this->post('/check-in/clinica-a', ['identifier' => '+593999333444'])->assertRedirect('/check-in/clinica-a');
    }

    // ── TEST 8: CRM Settings timezone aislado ──

    public function test_crm_settings_timezone_aislado_por_clinica(): void
    {
        $this->setSetting($this->clinicA, 'social_appointment_clinic_timezone', 'America/Guayaquil');
        $this->setSetting($this->clinicB, 'social_appointment_clinic_timezone', 'America/Bogota');

        app(SocialCrmSettingsService::class)->clearCache();

        app(TenantContext::class)->set($this->clinicA);
        $tzA = app(SocialCrmSettingsService::class)->clinicTimezone();

        app(TenantContext::class)->set($this->clinicB);
        app(SocialCrmSettingsService::class)->clearCache();
        $tzB = app(SocialCrmSettingsService::class)->clinicTimezone();

        $this->assertSame('America/Guayaquil', $tzA);
        $this->assertSame('America/Bogota', $tzB);
    }

    // ── TEST 9: Provisioning defaults ──

    public function test_provisioning_crea_clinica_con_defaults(): void
    {
        $admin = User::factory()->create();
        $clinic = app(ClinicProvisioningService::class)->provision([
            'name' => 'Clinic Test', 'slug' => 'clinic-test', 'subdomain' => 'clinic-test',
            'currency' => 'EUR', 'timezone' => 'America/Mexico_City', 'country' => 'México',
        ], existingAdmin: $admin);

        $this->assertSame('EUR', $clinic->currency);
        $this->assertSame('America/Mexico_City', $clinic->timezone);
        $this->assertSame('México', $clinic->country);
        $this->assertNotNull($clinic->settings);

        $crmTz = SocialCrmSetting::where('clinic_id', $clinic->id)
            ->where('key', 'social_appointment_clinic_timezone')
            ->value('value');
        $this->assertSame('America/Mexico_City', $crmTz);
    }

    // ── TEST 10: Storage prefix ──

    public function test_storage_prefix_es_clinics_id(): void
    {
        $this->assertStringContainsString('clinics/', $this->clinicA->settings['storage_prefix'] ?? '');
        $this->assertStringContainsString('clinics/', $this->clinicB->settings['storage_prefix'] ?? '');
    }

    // ── TEST 11: Doctores no cruzan ──

    public function test_doctores_no_cruzan_entre_clinicas(): void
    {
        Professional::factory()->doctor()->create(['clinic_id' => $this->clinicA->id, 'name' => 'Dr. A']);
        Professional::factory()->doctor()->create(['clinic_id' => $this->clinicB->id, 'name' => 'Dr. B']);

        app(TenantContext::class)->set($this->clinicA);
        $doctoresA = Professional::query()->forCurrentTenant()->where('role', 'doctor')->pluck('name')->all();

        app(TenantContext::class)->set($this->clinicB);
        $doctoresB = Professional::query()->forCurrentTenant()->where('role', 'doctor')->pluck('name')->all();

        $this->assertContains('Dr. A', $doctoresA);
        $this->assertNotContains('Dr. B', $doctoresA);
        $this->assertContains('Dr. B', $doctoresB);
        $this->assertNotContains('Dr. A', $doctoresB);
    }

    // ── TEST 12: Pacientes no cruzan ──

    public function test_pacientes_no_cruzan_entre_clinicas(): void
    {
        Patient::factory()->create(['clinic_id' => $this->clinicA->id, 'full_name' => 'Paciente A1']);
        Patient::factory()->create(['clinic_id' => $this->clinicA->id, 'full_name' => 'Paciente A2']);
        Patient::factory()->create(['clinic_id' => $this->clinicB->id, 'full_name' => 'Paciente B1']);

        app(TenantContext::class)->set($this->clinicA);
        $this->assertSame(2, Patient::query()->forCurrentTenant()->count());

        app(TenantContext::class)->set($this->clinicB);
        $this->assertSame(1, Patient::query()->forCurrentTenant()->count());
    }

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        parent::tearDown();
    }
}