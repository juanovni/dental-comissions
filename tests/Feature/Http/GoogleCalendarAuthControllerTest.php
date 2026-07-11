<?php

namespace Tests\Feature\Http;

use App\Services\GoogleCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleCalendarAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_callback_without_code_aborts(): void
    {
        $response = $this->get('/auth/google/callback');

        $response->assertStatus(400);
    }

    public function test_callback_with_error_redirects_with_message(): void
    {
        $response = $this->get('/auth/google/callback?error=access_denied&error_description=User+denied');

        $response->assertRedirect('/admin/integrations#google-calendar');
        $response->assertSessionHas('error');
    }

    public function test_callback_with_invalid_state_redirects_with_message(): void
    {
        $response = $this->get('/auth/google/callback?code=test-code&state=99999');

        $response->assertRedirect('/admin/integrations#google-calendar');
        $response->assertSessionHas('error', 'Solicitud de autorizacion invalida.');
    }

    public function test_callback_exchanges_code_and_redirects(): void
    {
        $service = $this->createMock(GoogleCalendarService::class);
        $service->expects($this->once())
            ->method('exchangeClinicCode')
            ->with('valid-code')
            ->willReturn(true);

        $this->app->instance(GoogleCalendarService::class, $service);

        $response = $this->get('/auth/google/callback?code=valid-code&state=clinic');

        $response->assertRedirect('/admin/integrations#google-calendar');
        $response->assertSessionHas('status');
    }

    public function test_callback_handles_exchange_failure(): void
    {
        $service = $this->createMock(GoogleCalendarService::class);
        $service->expects($this->once())
            ->method('exchangeClinicCode')
            ->willReturn(false);

        $this->app->instance(GoogleCalendarService::class, $service);

        $response = $this->get('/auth/google/callback?code=bad-code&state=clinic');

        $response->assertRedirect('/admin/integrations#google-calendar');
        $response->assertSessionHas('error');
    }
}
