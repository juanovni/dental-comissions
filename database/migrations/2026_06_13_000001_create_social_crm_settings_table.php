<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_crm_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_group')->default('general')->index();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('value_type')->default('string');
            $table->json('value')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        $now = now();

        DB::table('social_crm_settings')->insert([
            [
                'setting_group' => 'inbox_zero',
                'key' => 'social_inbox_archived_conversion_statuses',
                'label' => 'Estados CRM que salen de la bandeja principal',
                'value_type' => 'array',
                'value' => json_encode(['identity_linked', 'pending_patient_creation', 'appointment_created', 'converted']),
                'notes' => 'Cuando un lead llega a cualquiera de estos estados se considera archivado para la bandeja social.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'inbox_zero',
                'key' => 'social_inbox_archive_on_review',
                'label' => 'Archivar al marcar como revisado',
                'value_type' => 'boolean',
                'value' => json_encode(true),
                'notes' => 'Si esta activo, Revisado oculta el comentario de la bandeja principal.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'whatsapp_bridge',
                'key' => 'social_whatsapp_reply_template',
                'label' => 'Texto sugerido para derivar a WhatsApp',
                'value_type' => 'string',
                'value' => json_encode('Hola! Te comparto informacion personalizada aqui: {smart_link}. Si quieres seguimiento directo, abre WhatsApp: {whatsapp_link}'),
                'notes' => 'Variables disponibles: {token}, {platform}, {whatsapp_link}, {smart_link}.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'whatsapp_bridge',
                'key' => 'social_whatsapp_autocopy_toast',
                'label' => 'Mensaje de confirmacion al copiar link',
                'value_type' => 'string',
                'value' => json_encode('Link copiado. Pegalo ahora en el chat de Instagram.'),
                'notes' => 'Toast visible para secretaria cuando el navegador copia el link.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'scoring',
                'key' => 'social_score_token_generated',
                'label' => 'Puntos por token generado',
                'value_type' => 'integer',
                'value' => json_encode(30),
                'notes' => 'Preparado para Fase 2.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'scoring',
                'key' => 'social_score_smart_link_click',
                'label' => 'Puntos por clic en Smart Link',
                'value_type' => 'integer',
                'value' => json_encode(15),
                'notes' => 'Preparado para Fase 2.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'scoring',
                'key' => 'social_score_smart_link_revisit',
                'label' => 'Puntos por reingreso a Smart Link',
                'value_type' => 'integer',
                'value' => json_encode(10),
                'notes' => 'Preparado para Fase 2.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'scoring',
                'key' => 'social_hot_lead_threshold',
                'label' => 'Puntaje minimo para lead caliente',
                'value_type' => 'integer',
                'value' => json_encode(75),
                'notes' => 'Preparado para Fase 2.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('social_crm_settings');
    }
};
