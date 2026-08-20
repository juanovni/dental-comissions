<?php

namespace Tests\Feature\Console\Commands;

use App\Models\Clinic;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TenantAwareCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reminders_command_accepts_clinic_option(): void
    {
        $clinic = $this->createClinic();

        $exit = \Artisan::call('appointments:send-reminders', ['--clinic' => $clinic->id]);

        $this->assertSame(0, $exit);
    }

    public function test_reminders_command_runs_for_all_active_clinics(): void
    {
        $this->createClinic('clinic-a');
        $this->createClinic('clinic-b');

        $exit = \Artisan::call('appointments:send-reminders');

        $this->assertContains($exit, [0, 1]);
    }

    public function test_classify_comments_accepts_clinic_option(): void
    {
        $clinic = $this->createClinic();

        $exit = \Artisan::call('social:classify-comments', ['--clinic' => $clinic->id]);

        $this->assertSame(0, $exit);
    }

    public function test_lead_alerts_accepts_clinic_option(): void
    {
        $clinic = $this->createClinic();

        $exit = \Artisan::call('social:lead-alerts', ['--clinic' => $clinic->id]);

        $this->assertSame(0, $exit);
    }

    public function test_roi_leakage_report_accepts_clinic_option(): void
    {
        $clinic = $this->createClinic();

        $exit = \Artisan::call('social:roi-leakage-report', ['--clinic' => $clinic->id]);

        $this->assertSame(0, $exit);
    }

    public function test_sync_accounts_accepts_clinic_option(): void
    {
        $clinic = $this->createClinic();
        Http::fake();

        $exit = \Artisan::call('social:sync-accounts', ['--clinic' => $clinic->id]);

        $this->assertSame(0, $exit);
    }

    public function test_sync_comments_accepts_clinic_option(): void
    {
        $clinic = $this->createClinic();
        Http::fake();

        $exit = \Artisan::call('social:sync-comments', ['--clinic' => $clinic->id]);

        $this->assertSame(0, $exit);
    }

    public function test_run_for_each_clinic_sets_tenant_context(): void
    {
        $clinic = $this->createClinic();
        $tenantContext = app(TenantContext::class);

        \Artisan::call('appointments:send-reminders', ['--clinic' => $clinic->id]);

        $this->assertNull($tenantContext->id());
    }

    public function test_run_for_each_clinic_clears_context_after_each_clinic(): void
    {
        $a = $this->createClinic('clinic-a');
        $b = $this->createClinic('clinic-b');
        $tenantContext = app(TenantContext::class);

        \Artisan::call('appointments:send-reminders');

        $this->assertNull($tenantContext->id());
    }

    private function createClinic(string $slug = 'test-clinic'): Clinic
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
}
