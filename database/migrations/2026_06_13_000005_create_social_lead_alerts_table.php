<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_lead_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_comment_id')->constrained()->cascadeOnDelete();
            $table->string('alert_type')->index();
            $table->string('severity')->default('info')->index();
            $table->string('title');
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->unique(['social_comment_id', 'alert_type', 'resolved_at'], 'social_lead_alert_unique_open');
            $table->index(['alert_type', 'resolved_at']);
        });

        $now = now();

        DB::table('social_crm_settings')->insertOrIgnore([
            [
                'setting_group' => 'alerts',
                'key' => 'social_alerts_enabled',
                'label' => 'Alertas de leads activas',
                'value_type' => 'boolean',
                'value' => json_encode(true),
                'notes' => 'Permite activar o pausar la generacion de alertas operativas.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'alerts',
                'key' => 'social_alert_check_frequency_minutes',
                'label' => 'Frecuencia de revision de alertas',
                'value_type' => 'integer',
                'value' => json_encode(10),
                'notes' => 'Referencia operativa para el comando programado.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'alerts',
                'key' => 'social_alert_messages',
                'label' => 'Textos de alertas de leads',
                'value_type' => 'array',
                'value' => json_encode([
                    'hot_lead_created' => ['title' => 'Lead caliente', 'message' => 'El lead alcanzo el puntaje de alta prioridad.'],
                    'lead_reheated' => ['title' => 'Lead recalentado', 'message' => 'El paciente volvio a interactuar despues de un tiempo muerto.'],
                    'high_duration' => ['title' => 'Alta permanencia', 'message' => 'Paciente esta muy interesado en los resultados visuales.'],
                    'pending_patient_creation' => ['title' => 'Ficha pendiente', 'message' => 'El lead llego por WhatsApp y requiere crear o vincular ficha.'],
                    'no_contact_overdue' => ['title' => 'Lead sin contacto', 'message' => 'El lead caliente supero el tiempo maximo sin contacto.'],
                    'follow_up_due' => ['title' => 'Seguimiento vencido', 'message' => 'Hay un seguimiento programado pendiente de accion.'],
                ]),
                'notes' => 'Claves esperadas: hot_lead_created, lead_reheated, high_duration, pending_patient_creation, no_contact_overdue, follow_up_due.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('social_lead_alerts');

        DB::table('social_crm_settings')
            ->whereIn('key', [
                'social_alerts_enabled',
                'social_alert_check_frequency_minutes',
                'social_alert_messages',
            ])
            ->delete();
    }
};
