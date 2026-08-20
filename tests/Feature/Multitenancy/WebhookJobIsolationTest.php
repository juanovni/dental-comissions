<?php

namespace Tests\Feature\Multitenancy;

use App\Models\CalendarIntegration;
use App\Models\Clinic;
use App\Models\Professional;
use App\Models\SocialCrmSetting;
use App\Models\VoiceCall;
use App\Services\SocialCrmSettingsService;
use App\Services\WhatsappService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookJobIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinicA;
    private Clinic $clinicB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinicA = Clinic::create([
            'name' => 'Clinic A', 'slug' => 'ca', 'subdomain' => 'ca',
            'primary_domain' => 'ca.localhost', 'currency' => 'USD',
            'timezone' => 'UTC', 'status' => 'active', 'settings' => [],
        ]);
        $this->clinicB = Clinic::create([
            'name' => 'Clinic B', 'slug' => 'cb', 'subdomain' => 'cb',
            'primary_domain' => 'cb.localhost', 'currency' => 'USD',
            'timezone' => 'UTC', 'status' => 'active', 'settings' => [],
        ]);
    }

    public function test_whatsapp_webhook_resolves_clinic_by_phone_number_id(): void
    {
        $this->clinicA->update([
            'settings' => ['integrations' => ['whatsapp' => ['phone_number_id' => 'PA-111']]],
        ]);

        Http::fake();
        $service = app(WhatsappService::class);

        $service->processIncomingMessage([
            'metadata' => ['phone_number_id' => 'PA-111'],
            'contacts' => [['phone' => '+593999111']],
            'messages' => [['from' => '+593999111', 'id' => 'wamid.A', 'timestamp' => (string) now()->timestamp, 'type' => 'text', 'text' => ['body' => 'Hola']]],
        ]);

        $msg = \App\Models\WhatsappMessage::where('from_phone', '+593999111')->first();
        $this->assertNotNull($msg);
        $this->assertSame($this->clinicA->id, $msg->clinic_id);
    }

    public function test_whatsapp_webhook_clinics_separate_messages(): void
    {
        $this->clinicA->update([
            'settings' => ['integrations' => ['whatsapp' => ['phone_number_id' => 'PA-111']]],
        ]);
        $this->clinicB->update([
            'settings' => ['integrations' => ['whatsapp' => ['phone_number_id' => 'PB-222']]],
        ]);

        Http::fake();
        $service = app(WhatsappService::class);

        $service->processIncomingMessage([
            'metadata' => ['phone_number_id' => 'PA-111'],
            'contacts' => [['phone' => '+593111']],
            'messages' => [['from' => '+593111', 'id' => 'wamid.1', 'timestamp' => (string) now()->timestamp, 'type' => 'text', 'text' => ['body' => 'A']]],
        ]);
        $service->processIncomingMessage([
            'metadata' => ['phone_number_id' => 'PB-222'],
            'contacts' => [['phone' => '+593222']],
            'messages' => [['from' => '+593222', 'id' => 'wamid.2', 'timestamp' => (string) now()->timestamp, 'type' => 'text', 'text' => ['body' => 'B']]],
        ]);

        $msgA = \App\Models\WhatsappMessage::where('from_phone', '+593111')->first();
        $msgB = \App\Models\WhatsappMessage::where('from_phone', '+593222')->first();

        $this->assertSame($this->clinicA->id, $msgA->clinic_id);
        $this->assertSame($this->clinicB->id, $msgB->clinic_id);
    }

    public function test_google_calendar_tokens_aislados_por_clinica(): void
    {
        CalendarIntegration::create([
            'clinic_id' => $this->clinicA->id,
            'provider' => 'google_calendar',
            'calendar_id' => 'primary',
            'is_enabled' => true,
            'token' => 'token-A',
        ]);
        CalendarIntegration::create([
            'clinic_id' => $this->clinicB->id,
            'provider' => 'google_calendar',
            'calendar_id' => 'primary',
            'is_enabled' => true,
            'token' => 'token-B',
        ]);

        $intA = CalendarIntegration::where('clinic_id', $this->clinicA->id)
            ->where('provider', 'google_calendar')->first();
        $intB = CalendarIntegration::where('clinic_id', $this->clinicB->id)
            ->where('provider', 'google_calendar')->first();

        $this->assertNotNull($intA);
        $this->assertNotNull($intB);
        $this->assertSame('token-A', $intA->token);
        $this->assertSame('token-B', $intB->token);
    }

    public function test_voice_call_pertenece_a_clinica_correcta(): void
    {
        VoiceCall::create([
            'clinic_id' => $this->clinicA->id,
            'channel' => 'whatsapp_calling',
            'provider' => 'telnyx',
            'from_phone' => '+593999111',
            'status' => 'started',
        ]);
        VoiceCall::create([
            'clinic_id' => $this->clinicB->id,
            'channel' => 'whatsapp_calling',
            'provider' => 'telnyx',
            'from_phone' => '+593999222',
            'status' => 'started',
        ]);

        app(TenantContext::class)->set($this->clinicA);
        $countA = VoiceCall::query()->forCurrentTenant()->count();

        app(TenantContext::class)->set($this->clinicB);
        $countB = VoiceCall::query()->forCurrentTenant()->count();

        $this->assertSame(1, $countA);
        $this->assertSame(1, $countB);
    }

    public function test_profesionales_clinica_a_no_aparecen_en_clinica_b(): void
    {
        Professional::factory()->doctor()->create(['clinic_id' => $this->clinicA->id, 'name' => 'Dr. X']);
        Professional::factory()->assistant()->create(['clinic_id' => $this->clinicA->id, 'name' => 'Aux. Y']);
        Professional::factory()->doctor()->create(['clinic_id' => $this->clinicB->id, 'name' => 'Dr. Z']);

        app(TenantContext::class)->set($this->clinicA);
        $namesA = Professional::query()->forCurrentTenant()->pluck('name')->sort()->values()->all();

        app(TenantContext::class)->set($this->clinicB);
        $namesB = Professional::query()->forCurrentTenant()->pluck('name')->sort()->values()->all();

        $this->assertCount(2, $namesA);
        $this->assertCount(1, $namesB);
        $this->assertContains('Dr. X', $namesA);
        $this->assertNotContains('Dr. Z', $namesA);
    }

    public function test_social_crm_settings_aislados_por_clinica(): void
    {
        SocialCrmSetting::create([
            'clinic_id' => $this->clinicA->id, 'setting_group' => 'general',
            'key' => 'test_iso_key', 'label' => 'Test', 'value_type' => 'string',
            'value' => 'value-A', 'is_active' => true,
        ]);
        SocialCrmSetting::create([
            'clinic_id' => $this->clinicB->id, 'setting_group' => 'general',
            'key' => 'test_iso_key', 'label' => 'Test', 'value_type' => 'string',
            'value' => 'value-B', 'is_active' => true,
        ]);

        app(SocialCrmSettingsService::class)->clearCache();

        app(TenantContext::class)->set($this->clinicA);
        app(SocialCrmSettingsService::class)->clearCache();
        $valA = app(SocialCrmSettingsService::class)->get('test_iso_key');

        app(TenantContext::class)->set($this->clinicB);
        app(SocialCrmSettingsService::class)->clearCache();
        $valB = app(SocialCrmSettingsService::class)->get('test_iso_key');

        $this->assertSame('value-A', $valA);
        $this->assertSame('value-B', $valB);
    }

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        parent::tearDown();
    }
}