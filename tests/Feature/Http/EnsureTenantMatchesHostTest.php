<?php

namespace Tests\Feature\Http;

use App\Models\Clinic;
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
}
