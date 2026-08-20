<?php

namespace Tests\Feature\Http;

use App\Models\Clinic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaAuthTenantResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.meta.app_id' => 'test-app-id',
            'services.meta.app_secret' => 'test-app-secret',
            'services.meta.redirect_uri' => 'http://app.localhost:8080/auth/meta/callback',
            'services.meta.api_url' => 'https://graph.facebook.com/v25.0',
        ]);
    }

    private function clinic(string $slug): Clinic
    {
        return Clinic::create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'subdomain' => $slug,
            'primary_domain' => $slug.'.localhost',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }

    public function test_oauth_redirect_resolves_clinic_from_host(): void
    {
        $clinic = $this->clinic('clinic-1');

        $this->get('http://clinic-1.localhost:8080/auth/meta/redirect')
            ->assertRedirect();

        $this->assertSame($clinic->id, session('meta_oauth_clinic_id'));
    }

    public function test_oauth_redirect_on_admin_domain_has_no_clinic(): void
    {
        $this->get('http://app.localhost:8080/auth/meta/redirect')
            ->assertRedirect();

        $this->assertNull(session('meta_oauth_clinic_id'));
    }
}
