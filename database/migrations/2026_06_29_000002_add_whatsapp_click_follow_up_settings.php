<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('social_crm_settings')->insertOrIgnore([
            [
                'setting_group' => 'alerts',
                'key' => 'social_whatsapp_click_follow_up_minutes',
                'label' => 'Minutos sin mensaje tras clic WhatsApp',
                'value_type' => 'integer',
                'value' => json_encode(30),
                'notes' => 'Minutos de espera tras un clic en WhatsApp sin recibir mensaje para generar alerta.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'auto_reply',
                'key' => 'social_whatsapp_follow_up_auto_reply_enabled',
                'label' => 'Auto-respuesta de seguimiento WhatsApp',
                'value_type' => 'boolean',
                'value' => json_encode(false),
                'notes' => 'Envia un mensaje de seguimiento en el comentario original si el lead hizo clic en WhatsApp pero no envio mensaje.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'auto_reply',
                'key' => 'social_whatsapp_follow_up_auto_reply_template',
                'label' => 'Plantilla de seguimiento WhatsApp',
                'value_type' => 'string',
                'value' => json_encode('Hola {author_name}, vi que abriste el enlace de WhatsApp pero no me enviaste mensaje. ¿Te quedo alguna duda? Puedes responder aqui mismo o escribirme al WhatsApp cuando gustes.'),
                'notes' => 'Variables: {author_name}, {platform}.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $row = DB::table('social_crm_settings')
            ->where('key', 'social_alert_messages')
            ->first();

        if ($row) {
            $messages = json_decode($row->value, true) ?? [];

            $messages['whatsapp_click_no_message'] = [
                'title' => 'Clic en WhatsApp sin mensaje',
                'message' => 'El lead hizo clic en WhatsApp pero no ha enviado el mensaje de confirmacion.',
            ];

            DB::table('social_crm_settings')
                ->where('key', 'social_alert_messages')
                ->update([
                    'value' => json_encode($messages),
                    'notes' => 'Claves esperadas: hot_lead_created, lead_reheated, high_duration, pending_patient_creation, no_contact_overdue, follow_up_due, closing_opportunity, new_lead_arrived, auto_reply_sent, whatsapp_click_no_message.',
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        DB::table('social_crm_settings')
            ->whereIn('key', [
                'social_whatsapp_click_follow_up_minutes',
                'social_whatsapp_follow_up_auto_reply_enabled',
                'social_whatsapp_follow_up_auto_reply_template',
            ])
            ->delete();

        $row = DB::table('social_crm_settings')
            ->where('key', 'social_alert_messages')
            ->first();

        if ($row) {
            $messages = json_decode($row->value, true) ?? [];
            unset($messages['whatsapp_click_no_message']);

            DB::table('social_crm_settings')
                ->where('key', 'social_alert_messages')
                ->update([
                    'value' => json_encode($messages),
                    'notes' => 'Claves esperadas: hot_lead_created, lead_reheated, high_duration, pending_patient_creation, no_contact_overdue, follow_up_due, closing_opportunity, new_lead_arrived, auto_reply_sent.',
                    'updated_at' => now(),
                ]);
        }
    }
};
