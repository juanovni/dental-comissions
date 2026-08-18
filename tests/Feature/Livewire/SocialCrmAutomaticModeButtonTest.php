<?php

namespace Tests\Feature\Livewire;

use App\Livewire\SocialCrmAutomaticModeButton;
use App\Models\Clinic;
use App\Models\User;
use App\Support\TenantContext;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SocialCrmAutomaticModeButtonTest extends TestCase
{
    use RefreshDatabase;

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

    private function automaticModeActive(Clinic $clinic): bool
    {
        return app(TenantContext::class)->run($clinic, fn (): bool => (new SocialCrmAutomaticModeButton)->isAutomaticModeActive());
    }

    private function attachUser(User $user, Clinic $clinic, bool $isDefault = false): void
    {
        $clinic->users()->attach($user, ['role' => 'admin', 'is_default' => $isDefault, 'is_active' => true]);
    }

    public function test_activating_one_clinic_does_not_affect_another(): void
    {
        $clinicA = $this->clinic('clinic-a');
        $clinicB = $this->clinic('clinic-b');

        $user = User::factory()->create();
        $this->attachUser($user, $clinicA, isDefault: true);
        $this->attachUser($user, $clinicB);

        Filament::setCurrentPanel('clinic');
        Filament::setTenant($clinicA, isQuiet: true);

        // Activar modo automatico en la clinica A
        Livewire::actingAs($user)
            ->test(SocialCrmAutomaticModeButton::class)
            ->call('toggleAutomaticMode');

        $this->assertTrue($this->automaticModeActive($clinicA), 'La clinica A debe estar en modo automatico');
        $this->assertFalse($this->automaticModeActive($clinicB), 'La clinica B no debe verse afectada');

        // Activar modo automatico en la clinica B
        Filament::setTenant($clinicB, isQuiet: true);

        Livewire::actingAs($user)
            ->test(SocialCrmAutomaticModeButton::class)
            ->call('toggleAutomaticMode');

        $this->assertTrue($this->automaticModeActive($clinicA), 'La clinica A debe seguir en modo automatico');
        $this->assertTrue($this->automaticModeActive($clinicB), 'La clinica B debe estar en modo automatico');
    }

    public function test_manual_mode_in_one_clinic_does_not_affect_automatic_in_another(): void
    {
        $clinicA = $this->clinic('clinic-a');
        $clinicB = $this->clinic('clinic-b');

        $user = User::factory()->create();
        $this->attachUser($user, $clinicA, isDefault: true);
        $this->attachUser($user, $clinicB);

        Filament::setCurrentPanel('clinic');

        // Activar automatico en A
        Filament::setTenant($clinicA, isQuiet: true);
        Livewire::actingAs($user)
            ->test(SocialCrmAutomaticModeButton::class)
            ->call('toggleAutomaticMode');

        // Poner B en manual (desactivar lo que estuviera activo)
        Filament::setTenant($clinicB, isQuiet: true);
        Livewire::actingAs($user)
            ->test(SocialCrmAutomaticModeButton::class)
            ->call('toggleAutomaticMode');

        // Desactivar el modo en B
        Filament::setTenant($clinicB, isQuiet: true);
        Livewire::actingAs($user)
            ->test(SocialCrmAutomaticModeButton::class)
            ->call('toggleAutomaticMode');

        $this->assertTrue($this->automaticModeActive($clinicA), 'La clinica A debe seguir en modo automatico');
        $this->assertFalse($this->automaticModeActive($clinicB), 'La clinica B debe estar en modo manual');
    }
}
