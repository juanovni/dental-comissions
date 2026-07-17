<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $settings = [
            [
                'setting_group' => 'whatsapp_bridge',
                'key' => 'social_whatsapp_first_leads_enabled',
                'label' => 'Crear leads desde WhatsApp',
                'value_type' => 'boolean',
                'value' => json_encode(true),
                'notes' => 'Crea un lead nuevo cuando escribe un numero desconocido por WhatsApp.',
            ],
            [
                'setting_group' => 'scoring',
                'key' => 'social_score_whatsapp_first_message',
                'label' => 'Puntos por primer WhatsApp',
                'value_type' => 'integer',
                'value' => json_encode(20),
                'notes' => 'Puntaje inicial para leads creados por mensaje directo de WhatsApp.',
            ],
            [
                'setting_group' => 'scoring',
                'key' => 'social_score_whatsapp_treatment_interest',
                'label' => 'Puntos por interes en tratamiento',
                'value_type' => 'integer',
                'value' => json_encode(15),
                'notes' => 'Puntaje cuando el agente detecta interes comercial o tratamiento en WhatsApp.',
            ],
            [
                'setting_group' => 'scoring',
                'key' => 'social_score_whatsapp_appointment_intent',
                'label' => 'Puntos por intencion de cita',
                'value_type' => 'integer',
                'value' => json_encode(30),
                'notes' => 'Puntaje cuando el agente detecta intencion de agendar cita en WhatsApp.',
            ],
            [
                'setting_group' => 'scoring',
                'key' => 'social_score_whatsapp_slot_selected',
                'label' => 'Puntos por horario seleccionado',
                'value_type' => 'integer',
                'value' => json_encode(20),
                'notes' => 'Puntaje cuando el lead selecciona un horario ofrecido por WhatsApp.',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('social_crm_settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting + [
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('social_crm_settings')
            ->whereIn('key', [
                'social_whatsapp_first_leads_enabled',
                'social_score_whatsapp_first_message',
                'social_score_whatsapp_treatment_interest',
                'social_score_whatsapp_appointment_intent',
                'social_score_whatsapp_slot_selected',
            ])
            ->delete();
    }
};
