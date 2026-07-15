<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_link_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_comment_id')->constrained()->cascadeOnDelete();
            $table->string('event_type')->index();
            $table->string('session_id', 80)->nullable()->index();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['social_comment_id', 'event_type']);
            $table->index(['social_comment_id', 'session_id']);
        });

        $now = now();

        DB::table('social_crm_settings')->insertOrIgnore([
            [
                'setting_group' => 'smart_links',
                'key' => 'social_smart_link_duration_threshold_seconds',
                'label' => 'Segundos para alerta de permanencia',
                'value_type' => 'integer',
                'value' => json_encode(60),
                'notes' => 'Al superar este tiempo en la landing, se registra alerta de alto interes.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'smart_links',
                'key' => 'social_smart_link_ping_seconds',
                'label' => 'Intervalo de tracking de permanencia',
                'value_type' => 'integer',
                'value' => json_encode(15),
                'notes' => 'Frecuencia en segundos para enviar pings de permanencia desde la landing.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'smart_links',
                'key' => 'social_smart_link_duration_score',
                'label' => 'Puntos por permanencia alta',
                'value_type' => 'integer',
                'value' => json_encode(20),
                'notes' => 'Puntos sumados cuando el paciente supera el umbral de permanencia.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'smart_links',
                'key' => 'social_smart_link_duration_alert',
                'label' => 'Mensaje de alerta por permanencia',
                'value_type' => 'string',
                'value' => json_encode('Paciente esta muy interesado en los resultados visuales.'),
                'notes' => 'Texto auditado cuando se supera el umbral de permanencia.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'smart_links',
                'key' => 'social_smart_link_content_blocks',
                'label' => 'Contenido dinamico de landing por categoria',
                'value_type' => 'array',
                'value' => json_encode([
                    'unknown' => [
                        'eyebrow' => 'Valoracion dental personalizada',
                        'title' => 'Tu sonrisa merece un plan claro, humano y sin presion.',
                        'subtitle' => 'Mira como trabajamos y continua por WhatsApp para recibir orientacion de la clinica.',
                        'visual_label' => 'Diagnostico integral',
                        'visual_image_url' => '',
                        'video_url' => '',
                        'before_image_url' => '',
                        'before_video_url' => '',
                        'after_image_url' => '',
                        'after_video_url' => '',
                    ],
                    'ortodoncia' => [
                        'eyebrow' => 'Ortodoncia invisible',
                        'title' => 'Tu nueva sonrisa, planificada a medida.',
                        'subtitle' => 'Conoce el flujo visual de un tratamiento moderno antes de escribirnos.',
                        'visual_label' => 'Simulacion Ortodoncia',
                        'visual_image_url' => '/images/smart-links/ortodoncia/hero.jpg',
                        'video_url' => '',
                        'before_image_url' => '',
                        'before_video_url' => '/videos/smart-links/ortodoncia/before.mp4',
                        'after_image_url' => '/images/smart-links/ortodoncia/after.jpg',
                        'after_video_url' => '',
                    ],
                    'limpieza' => [
                        'eyebrow' => 'Limpieza dental profesional',
                        'title' => 'Recupera una sonrisa fresca, limpia y saludable.',
                        'subtitle' => 'Conoce como una limpieza profesional ayuda a prevenir molestias, manchas y acumulacion de sarro antes de escribirnos.',
                        'visual_label' => 'Profilaxis dental',
                        'visual_image_url' => '',
                        'video_url' => '',
                        'before_image_url' => '',
                        'before_video_url' => '',
                        'after_image_url' => '',
                        'after_video_url' => '',
                    ],
                    'implantes' => [
                        'eyebrow' => 'Implantes dentales',
                        'title' => 'Recupera seguridad al morder, sonreir y hablar.',
                        'subtitle' => 'Explora resultados visuales y resuelve tus dudas por WhatsApp.',
                        'visual_label' => 'Rehabilitacion oral',
                        'visual_image_url' => '',
                        'video_url' => '',
                        'before_image_url' => '',
                        'before_video_url' => '',
                        'after_image_url' => '',
                        'after_video_url' => '',
                    ],
                ]),
                'notes' => 'Usa claves por categoria de procedimiento. Siempre debe existir unknown como respaldo.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('social_link_events');

        DB::table('social_crm_settings')
            ->whereIn('key', [
                'social_smart_link_duration_threshold_seconds',
                'social_smart_link_ping_seconds',
                'social_smart_link_duration_score',
                'social_smart_link_duration_alert',
                'social_smart_link_content_blocks',
            ])
            ->delete();
    }
};
