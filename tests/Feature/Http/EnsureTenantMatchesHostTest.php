<?php

namespace Tests\Feature\Http;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureTenantMatchesHostTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_route_can_match_host_based_clinic(): void
    {
        Clinic::create([
            'name' => 'Clinic One',
            'slug' => 'clinic-1',
            'subdomain' => 'clinic-1',
            'primary_domain' => 'clinic-1.localhost',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);

        $this->get('http://clinic-1.localhost:8080/check-in/clinic-1')
            ->assertOk();
    }

    public function test_tenant_route_is_rejected_when_host_clinic_does_not_match_slug(): void
    {
        Clinic::create([
            'name' => 'Clinic One',
            'slug' => 'clinic-1',
            'subdomain' => 'clinic-1',
            'primary_domain' => 'clinic-1.localhost',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);

        Clinic::create([
            'name' => 'Clinic Two',
            'slug' => 'clinic-2',
            'subdomain' => 'clinic-2',
            'primary_domain' => 'clinic-2.localhost',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);

        $this->get('http://clinic-1.localhost:8080/check-in/clinic-2')
            ->assertNotFound();
    }

    public function test_clinic_panel_rejects_unrecognized_host_even_with_valid_tenant_in_url(): void
    {
        $clinic = $this->createClinic('clinic-1');
        $user = User::factory()->create();
        $clinic->users()->attach($user, ['role' => 'admin', 'is_default' => true, 'is_active' => true]);

        $this->actingAs($user)
            ->get('http://unknown.localhost:8080/admin/patients')
            ->assertNotFound();
    }

    public function test_clinic_panel_rejects_unrecognized_host_without_default_tenant_fallback(): void
    {
        $clinic = $this->createClinic('clinic-1');
        $user = User::factory()->create();
        $clinic->users()->attach($user, ['role' => 'admin', 'is_default' => true, 'is_active' => true]);

        $this->actingAs($user)
            ->get('http://unknown.localhost:8080/admin')
            ->assertNotFound();
    }

    public function test_clinic_panel_serves_host_matching_slug(): void
    {
        $clinic = $this->createClinic('clinic-1', 'clinic-one', 'clinic-one.localhost');
        $user = User::factory()->create();
        $clinic->users()->attach($user, ['role' => 'admin', 'is_default' => true, 'is_active' => true]);

        $response = $this->actingAs($user)
            ->get('http://clinic-1.localhost:8080/admin');

        $this->assertContains($response->getStatusCode(), [200, 302, 303]);
    }

    public function test_clinic_panel_serves_recognized_host(): void
    {
        $clinic = $this->createClinic('clinic-1');
        $user = User::factory()->create();
        $clinic->users()->attach($user, ['role' => 'admin', 'is_default' => true, 'is_active' => true]);

        $response = $this->actingAs($user)
            ->get('http://clinic-1.localhost:8080/admin');

        $this->assertContains($response->getStatusCode(), [200, 302, 303]);
    }

    private function createClinic(string $slug, ?string $subdomain = null, ?string $primaryDomain = null): Clinic
    {
        $subdomain ??= $slug;
        $primaryDomain ??= $slug.'.localhost';

        return Clinic::create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'subdomain' => $subdomain,
            'primary_domain' => $primaryDomain,
            'currency' => 'USD',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }
}
