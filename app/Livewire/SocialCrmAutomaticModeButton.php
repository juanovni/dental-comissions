<?php

namespace App\Livewire;

use App\Models\SocialCrmSetting;
use App\Services\SocialCrmSettingsService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SocialCrmAutomaticModeButton extends Component
{
    public function toggleAutomaticMode(): void
    {
        $isActivating = ! $this->isAutomaticModeActive();
        $settings = $isActivating ? $this->automaticSettings() : $this->manualSettings();

        foreach ($settings as $key => $value) {
            $valueType = $this->valueType($value);

            SocialCrmSetting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'setting_group' => $this->settingGroup($key),
                    'label' => $this->settingLabel($key),
                    'value_type' => $valueType,
                    'value' => $this->castForDb($value, $valueType),
                    'is_active' => true,
                ],
            );
        }

        app(SocialCrmSettingsService::class)->clearCache();

        $this->dispatch('social-crm-automatic-mode-updated');

        if ($isActivating) {
            Notification::make()
                ->title('Modo automático activado')
                ->body('El sistema responderá automáticamente a leads comerciales, propondrá citas y dará seguimiento según la configuración.')
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title('Modo automático desactivado')
            ->body('Las respuestas automáticas, propuestas de cita y seguimientos automáticos quedaron pausados.')
            ->warning()
            ->send();
    }

    public function isAutomaticModeActive(): bool
    {
        $settings = app(SocialCrmSettingsService::class);

        return $settings->get('social_appointment_propose_slots', false) === true
            && $settings->get('social_appointment_auto_create_patient', true) === true
            && $settings->get('social_appointment_require_whatsapp_phone_for_patient', true) === true
            && $settings->get('social_auto_reply_enabled', false) === true
            && $settings->get('social_auto_reply_dry_run', true) === false
            && $settings->get('social_auto_reply_use_ai', false) === true
            && $settings->get('social_auto_reply_use_smart_link', false) === true
            && $settings->get('social_whatsapp_follow_up_auto_reply_enabled', false) === true
            && $settings->get('social_alerts_enabled', false) === true;
    }

    public function render(): View
    {
        return view('livewire.social-crm-automatic-mode-button');
    }

    private function automaticSettings(): array
    {
        return [
            'social_appointment_propose_slots' => true,
            'social_appointment_auto_confirm' => false,
            'social_appointment_auto_create_patient' => true,
            'social_appointment_require_whatsapp_phone_for_patient' => true,
            'social_appointment_patient_fallback_name' => 'Paciente WhatsApp',
            'social_auto_reply_enabled' => true,
            'social_auto_reply_dry_run' => false,
            'social_auto_reply_use_ai' => true,
            'social_auto_reply_use_smart_link' => true,
            'social_auto_reply_allowed_classifications' => ['sales_lead', 'commercial_question'],
            'social_whatsapp_follow_up_auto_reply_enabled' => true,
            'social_alerts_enabled' => true,
        ];
    }

    private function manualSettings(): array
    {
        return [
            'social_appointment_propose_slots' => false,
            'social_appointment_auto_confirm' => false,
            'social_auto_reply_enabled' => false,
            'social_auto_reply_dry_run' => true,
            'social_whatsapp_follow_up_auto_reply_enabled' => false,
            'social_alerts_enabled' => false,
        ];
    }

    private function valueType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_array($value) => 'array',
            default => 'string',
        };
    }

    private function castForDb(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'array' => is_array($value) ? $value : [],
            default => (string) ($value ?? ''),
        };
    }

    private function settingGroup(string $key): string
    {
        return match (true) {
            str_contains($key, 'appointment') => 'appointments',
            str_contains($key, 'auto_reply') => 'auto_reply',
            str_contains($key, 'whatsapp') => 'auto_reply',
            str_contains($key, 'alert') => 'alerts',
            default => 'general',
        };
    }

    private function settingLabel(string $key): string
    {
        $labels = [
            'social_appointment_propose_slots' => 'Proponer slots reales',
            'social_appointment_auto_confirm' => 'Auto-confirmar cita',
            'social_appointment_auto_create_patient' => 'Crear ficha al confirmar cita',
            'social_appointment_require_whatsapp_phone_for_patient' => 'Requerir telefono WhatsApp para ficha automatica',
            'social_appointment_patient_fallback_name' => 'Nombre fallback para ficha automatica',
            'social_auto_reply_enabled' => 'Auto-respuestas activadas',
            'social_auto_reply_dry_run' => 'Modo dry-run',
            'social_auto_reply_use_ai' => 'Usar IA para generar respuesta',
            'social_auto_reply_use_smart_link' => 'Usar Smart Link en vez de WhatsApp directo',
            'social_auto_reply_allowed_classifications' => 'Clasificaciones que activan auto-respuesta',
            'social_whatsapp_follow_up_auto_reply_enabled' => 'Auto-respuesta de seguimiento',
            'social_alerts_enabled' => 'Alertas de leads activas',
        ];

        return $labels[$key] ?? str($key)
            ->replace('social_', '')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
