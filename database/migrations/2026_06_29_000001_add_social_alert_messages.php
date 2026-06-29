<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $row = DB::table('social_crm_settings')
            ->where('key', 'social_alert_messages')
            ->first();

        if (! $row) {
            return;
        }

        $messages = json_decode($row->value, true) ?? [];

        $messages['closing_opportunity'] = [
            'title' => 'Oportunidad de cierre',
            'message' => 'El paciente requiere intervencion humana para concretar la venta.',
        ];
        $messages['new_lead_arrived'] = [
            'title' => 'Nuevo lead recibido',
            'message' => 'Un nuevo comentario o lead ha llegado a la bandeja social.',
        ];
        $messages['auto_reply_sent'] = [
            'title' => 'Respuesta automatica enviada',
            'message' => 'Se envio una respuesta automatica al comentario del lead.',
        ];

        DB::table('social_crm_settings')
            ->where('key', 'social_alert_messages')
            ->update([
                'value' => json_encode($messages),
                'notes' => 'Claves esperadas: hot_lead_created, lead_reheated, high_duration, pending_patient_creation, no_contact_overdue, follow_up_due, closing_opportunity, new_lead_arrived, auto_reply_sent.',
                'updated_at' => now(),
            ]);

        DB::table('social_lead_alerts')
            ->where('alert_type', 'closing_opportunity')
            ->where('title', 'Closing Opportunity')
            ->update([
                'title' => 'Oportunidad de cierre',
                'message' => 'El paciente requiere intervencion humana para concretar la venta.',
            ]);

        DB::table('social_lead_alerts')
            ->where('alert_type', 'new_lead_arrived')
            ->where('title', 'New Lead Arrived')
            ->update([
                'title' => 'Nuevo lead recibido',
                'message' => 'Un nuevo comentario o lead ha llegado a la bandeja social.',
            ]);

        DB::table('social_lead_alerts')
            ->where('alert_type', 'auto_reply_sent')
            ->where('title', 'Auto Reply Sent')
            ->update([
                'title' => 'Respuesta automatica enviada',
                'message' => 'Se envio una respuesta automatica al comentario del lead.',
            ]);
    }

    public function down(): void
    {
        $row = DB::table('social_crm_settings')
            ->where('key', 'social_alert_messages')
            ->first();

        if (! $row) {
            return;
        }

        $messages = json_decode($row->value, true) ?? [];

        unset($messages['closing_opportunity']);
        unset($messages['new_lead_arrived']);
        unset($messages['auto_reply_sent']);

        DB::table('social_crm_settings')
            ->where('key', 'social_alert_messages')
            ->update([
                'value' => json_encode($messages),
                'notes' => 'Claves esperadas: hot_lead_created, lead_reheated, high_duration, pending_patient_creation, no_contact_overdue, follow_up_due.',
                'updated_at' => now(),
            ]);
    }
};
