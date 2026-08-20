<?php

namespace Tests\Feature\Services;

use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Clinic;
use App\Models\User;
use App\Services\ClinicProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicProvisioningServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_provisions_a_clinic_with_an_existing_admin(): void
    {
        config(['tenancy.base_domain' => 'localhost']);

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $clinic = app(ClinicProvisioningService::class)->provision([
            'name' => 'Clinica Norte',
            'slug' => 'clinica-norte',
            'subdomain' => 'norte',
            'currency' => 'USD',
            'timezone' => 'America/Santo_Domingo',
        ], existingAdmin: $admin);

        $this->assertSame(TenantStatus::Active, $clinic->status);
        $this->assertSame('norte.localhost', $clinic->primary_domain);
        $this->assertSame('clinics/'.$clinic->id.'/', $clinic->settings['storage_prefix']);
        $this->assertSame('09:00', $clinic->settings['appointments']['start_time']);
        $this->assertFalse($clinic->settings['crm']['auto_responses_enabled']);
        $this->assertSame('Consulta inicial', $clinic->settings['procedure_templates'][0]['name']);
        $this->assertSame('not_configured', $clinic->settings['integrations']['meta']);

        $this->assertDatabaseHas('clinic_user', [
            'clinic_id' => $clinic->id,
            'user_id' => $admin->id,
            'role' => UserRole::Admin->value,
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function test_it_provisions_a_clinic_with_a_new_admin(): void
    {
        $clinic = app(ClinicProvisioningService::class)->provision([
            'name' => 'Clinica Sur',
            'slug' => 'clinica-sur',
            'subdomain' => 'sur',
        ], newAdminData: [
            'name' => 'Admin Sur',
            'email' => 'admin.sur@clinica.com',
            'password' => 'secret123',
        ]);

        $admin = User::where('email', 'admin.sur@clinica.com')->firstOrFail();

        $this->assertSame(TenantStatus::Active, $clinic->status);
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertTrue($clinic->users->contains($admin));
    }

    public function test_it_requires_an_initial_admin(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(ClinicProvisioningService::class)->provision([
            'name' => 'Clinica Centro',
            'slug' => 'clinica-centro',
            'subdomain' => 'centro',
        ]);
    }

    public function test_it_marks_the_clinic_as_provisioning_failed_when_provisioning_breaks(): void
    {
        try {
            app(ClinicProvisioningService::class)->provision([
                'name' => 'Clinica Oeste',
                'slug' => 'clinica-oeste',
                'subdomain' => 'oeste',
            ], newAdminData: [
                'name' => 'Admin Oeste',
                'email' => null,
            ]);

            $this->fail('Provisioning should have failed.');
        } catch (\Throwable) {
            $clinic = Clinic::where('slug', 'clinica-oeste')->firstOrFail();

            $this->assertSame(TenantStatus::ProvisioningFailed, $clinic->status);
            $this->assertIsArray($clinic->settings);
            $this->assertArrayHasKey('provisioning_error', $clinic->settings);
        }
    }
}
