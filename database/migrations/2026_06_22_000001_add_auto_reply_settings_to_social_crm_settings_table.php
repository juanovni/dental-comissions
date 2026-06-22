<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('social_crm_settings')->insert([
            [
                'setting_group' => 'auto_reply',
                'key' => 'social_auto_reply_enabled',
                'label' => 'Auto respuestas activadas',
                'value_type' => 'boolean',
                'value' => json_encode(false),
                'notes' => 'Activa o desactiva las respuestas automáticas en comentarios de Facebook/Instagram.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'auto_reply',
                'key' => 'social_auto_reply_dry_run',
                'label' => 'Modo dry-run',
                'value_type' => 'boolean',
                'value' => json_encode(true),
                'notes' => 'Cuando está activo, genera el mensaje pero no lo publica en Meta. Solo guarda auditoría.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'auto_reply',
                'key' => 'social_auto_reply_use_ai',
                'label' => 'Usar IA para generar respuesta',
                'value_type' => 'boolean',
                'value' => json_encode(true),
                'notes' => 'Si está desactivado, usa la plantilla estática sin IA.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'auto_reply',
                'key' => 'social_auto_reply_company_name',
                'label' => 'Nombre de la empresa/clínica',
                'value_type' => 'string',
                'value' => json_encode('Clínica Dental'),
                'notes' => 'Nombre que aparece en la cabecera del mensaje automático.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'auto_reply',
                'key' => 'social_auto_reply_header_template',
                'label' => 'Plantilla de cabecera',
                'value_type' => 'string',
                'value' => json_encode('👋 Te saluda {empresa}'),
                'notes' => 'Primera línea del mensaje. Variable disponible: {empresa}.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'auto_reply',
                'key' => 'social_auto_reply_template',
                'label' => 'Plantilla de respuesta',
                'value_type' => 'string',
                'value' => json_encode('Hola, con gusto te ayudamos. Te dejamos la información inicial y el acceso para continuar por WhatsApp aquí: {smart_link}'),
                'notes' => 'Cuerpo del mensaje. Variables: {empresa}, {smart_link}, {whatsapp_link}, {tracking_token}, {procedure_name}, {lead_first_name}.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'auto_reply',
                'key' => 'social_auto_reply_max_attempts',
                'label' => 'Máximo de reintentos',
                'value_type' => 'integer',
                'value' => json_encode(2),
                'notes' => 'Número máximo de intentos de publicación en Meta antes de marcar como fallido.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'auto_reply',
                'key' => 'social_auto_reply_use_smart_link',
                'label' => 'Usar Smart Link en vez de WhatsApp directo',
                'value_type' => 'boolean',
                'value' => json_encode(true),
                'notes' => 'Si está activo, el comentario lleva al Smart Link. Si está desactivado, lleva directamente a WhatsApp.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'auto_reply',
                'key' => 'social_auto_reply_allowed_classifications',
                'label' => 'Clasificaciones que activan auto-respuesta',
                'value_type' => 'array',
                'value' => json_encode(['sales_lead', 'commercial_question']),
                'notes' => 'Lista de clasificaciones de comentario que disparan respuesta automática.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('social_crm_settings')
            ->where('setting_group', 'auto_reply')
            ->delete();
    }
};
