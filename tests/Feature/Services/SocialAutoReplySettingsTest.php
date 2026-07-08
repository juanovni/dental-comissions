<?php

namespace Tests\Feature\Services;

use App\Models\SocialCrmSetting;
use App\Services\SocialCrmSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialAutoReplySettingsTest extends TestCase
{
    use RefreshDatabase;

    private SocialCrmSettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SocialCrmSettingsService::class);
    }

    public function test_auto_reply_disabled_by_default(): void
    {
        $this->assertFalse($this->service->autoReplyEnabled());
    }

    public function test_auto_reply_dry_run_enabled_by_default(): void
    {
        $this->assertTrue($this->service->autoReplyDryRun());
    }

    public function test_auto_reply_use_ai_enabled_by_default(): void
    {
        $this->assertTrue($this->service->autoReplyUseAi());
    }

    public function test_auto_reply_company_name_default(): void
    {
        $this->assertSame('Clínica Dental', $this->service->autoReplyCompanyName());
    }

    public function test_auto_reply_header_template_default(): void
    {
        $this->assertSame('👋 Te saluda {empresa}', $this->service->autoReplyHeaderTemplate());
    }

    public function test_auto_reply_template_default(): void
    {
        $template = $this->service->autoReplyTemplate();

        $this->assertStringContainsString('{smart_link}', $template);
        $this->assertStringContainsString('WhatsApp', $template);
    }

    public function test_auto_reply_max_attempts_default(): void
    {
        $this->assertSame(2, $this->service->autoReplyMaxAttempts());
    }

    public function test_auto_reply_use_smart_link_enabled_by_default(): void
    {
        $this->assertTrue($this->service->autoReplyUseSmartLink());
    }

    public function test_auto_reply_allowed_classifications_default(): void
    {
        $classifications = $this->service->autoReplyAllowedClassifications();

        $this->assertContains('sales_lead', $classifications);
        $this->assertContains('commercial_question', $classifications);
    }

    public function test_auto_reply_allowed_social_account_ids_default(): void
    {
        $this->assertSame([], $this->service->autoReplyAllowedSocialAccountIds());
    }

    public function test_auto_reply_can_be_enabled(): void
    {
        SocialCrmSetting::updateOrCreate(
            ['key' => 'social_auto_reply_enabled'],
            [
                'setting_group' => 'auto_reply',
                'label' => 'Auto respuestas activadas',
                'value_type' => 'boolean',
                'value' => true,
                'is_active' => true,
            ],
        );

        $this->service->clearCache();

        $this->assertTrue($this->service->autoReplyEnabled());
    }

    public function test_auto_reply_company_name_is_configurable(): void
    {
        SocialCrmSetting::updateOrCreate(
            ['key' => 'social_auto_reply_company_name'],
            [
                'setting_group' => 'auto_reply',
                'label' => 'Nombre de la empresa',
                'value_type' => 'string',
                'value' => 'Sonríe Dental',
                'is_active' => true,
            ],
        );

        $this->service->clearCache();

        $this->assertSame('Sonríe Dental', $this->service->autoReplyCompanyName());
    }

    public function test_auto_reply_header_template_is_configurable(): void
    {
        SocialCrmSetting::updateOrCreate(
            ['key' => 'social_auto_reply_header_template'],
            [
                'setting_group' => 'auto_reply',
                'label' => 'Plantilla de cabecera',
                'value_type' => 'string',
                'value' => '🦷 Hola desde {empresa}',
                'is_active' => true,
            ],
        );

        $this->service->clearCache();

        $this->assertSame('🦷 Hola desde {empresa}', $this->service->autoReplyHeaderTemplate());
    }

    public function test_auto_reply_template_is_configurable(): void
    {
        SocialCrmSetting::updateOrCreate(
            ['key' => 'social_auto_reply_template'],
            [
                'setting_group' => 'auto_reply',
                'label' => 'Plantilla de respuesta',
                'value_type' => 'string',
                'value' => 'Custom reply: {smart_link}',
                'is_active' => true,
            ],
        );

        $this->service->clearCache();

        $this->assertSame('Custom reply: {smart_link}', $this->service->autoReplyTemplate());
    }

    public function test_auto_reply_allowed_classifications_is_configurable(): void
    {
        SocialCrmSetting::updateOrCreate(
            ['key' => 'social_auto_reply_allowed_classifications'],
            [
                'setting_group' => 'auto_reply',
                'label' => 'Clasificaciones permitidas',
                'value_type' => 'array',
                'value' => ['sales_lead'],
                'is_active' => true,
            ],
        );

        $this->service->clearCache();

        $classifications = $this->service->autoReplyAllowedClassifications();

        $this->assertContains('sales_lead', $classifications);
        $this->assertNotContains('commercial_question', $classifications);
    }

    public function test_auto_reply_allowed_social_account_ids_is_configurable(): void
    {
        SocialCrmSetting::updateOrCreate(
            ['key' => 'social_auto_reply_allowed_social_account_ids'],
            [
                'setting_group' => 'auto_reply',
                'label' => 'Cuentas habilitadas',
                'value_type' => 'array',
                'value' => ['2', 4, null, 0, 'abc', 4],
                'is_active' => true,
            ],
        );

        $this->service->clearCache();

        $this->assertSame([2, 4], $this->service->autoReplyAllowedSocialAccountIds());
    }

    public function test_auto_reply_max_attempts_is_configurable(): void
    {
        SocialCrmSetting::updateOrCreate(
            ['key' => 'social_auto_reply_max_attempts'],
            [
                'setting_group' => 'auto_reply',
                'label' => 'Máximo de reintentos',
                'value_type' => 'integer',
                'value' => 5,
                'is_active' => true,
            ],
        );

        $this->service->clearCache();

        $this->assertSame(5, $this->service->autoReplyMaxAttempts());
    }

    public function test_auto_reply_max_attempts_minimum_is_one(): void
    {
        SocialCrmSetting::updateOrCreate(
            ['key' => 'social_auto_reply_max_attempts'],
            [
                'setting_group' => 'auto_reply',
                'label' => 'Máximo de reintentos',
                'value_type' => 'integer',
                'value' => 0,
                'is_active' => true,
            ],
        );

        $this->service->clearCache();

        $this->assertSame(1, $this->service->autoReplyMaxAttempts());
    }

    public function test_auto_reply_dry_run_can_be_disabled(): void
    {
        SocialCrmSetting::updateOrCreate(
            ['key' => 'social_auto_reply_dry_run'],
            [
                'setting_group' => 'auto_reply',
                'label' => 'Modo dry-run',
                'value_type' => 'boolean',
                'value' => false,
                'is_active' => true,
            ],
        );

        $this->service->clearCache();

        $this->assertFalse($this->service->autoReplyDryRun());
    }

    public function test_auto_reply_use_smart_link_can_be_disabled(): void
    {
        SocialCrmSetting::updateOrCreate(
            ['key' => 'social_auto_reply_use_smart_link'],
            [
                'setting_group' => 'auto_reply',
                'label' => 'Usar Smart Link',
                'value_type' => 'boolean',
                'value' => false,
                'is_active' => true,
            ],
        );

        $this->service->clearCache();

        $this->assertFalse($this->service->autoReplyUseSmartLink());
    }
}
