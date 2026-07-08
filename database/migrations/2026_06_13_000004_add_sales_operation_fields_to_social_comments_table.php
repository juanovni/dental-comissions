<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_comments', function (Blueprint $table) {
            $table->timestamp('contacted_at')->nullable()->after('reheated_at');
            $table->timestamp('follow_up_at')->nullable()->after('contacted_at');
            $table->text('follow_up_notes')->nullable()->after('follow_up_at');
            $table->timestamp('lost_at')->nullable()->after('follow_up_notes');
            $table->string('lost_reason')->nullable()->after('lost_at');

            $table->index(['contacted_at', 'follow_up_at']);
            $table->index(['lost_at', 'lost_reason']);
        });

        $now = now();

        DB::table('social_crm_settings')->insertOrIgnore([
            [
                'setting_group' => 'sales_operations',
                'key' => 'social_sales_urgent_score_threshold',
                'label' => 'Puntaje para seguimiento urgente',
                'value_type' => 'integer',
                'value' => json_encode(75),
                'notes' => 'Leads con este puntaje o superior aparecen como urgentes en Leads Calientes.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'sales_operations',
                'key' => 'social_sales_max_hours_without_contact',
                'label' => 'Horas maximas sin contactar',
                'value_type' => 'integer',
                'value' => json_encode(4),
                'notes' => 'Si un lead caliente supera estas horas sin contacto, se marca como vencido.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'sales_operations',
                'key' => 'social_sales_default_follow_up_hours',
                'label' => 'Horas por defecto para seguimiento',
                'value_type' => 'integer',
                'value' => json_encode(24),
                'notes' => 'Accion rapida para programar siguiente contacto.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'sales_operations',
                'key' => 'social_sales_lost_reasons',
                'label' => 'Razones de perdida de lead',
                'value_type' => 'array',
                'value' => json_encode(['sin_respuesta', 'precio', 'fuera_de_zona', 'ya_atendido', 'no_califica']),
                'notes' => 'La primera razon se usa como valor por defecto en acciones rapidas.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::table('social_comments', function (Blueprint $table) {
            $table->dropIndex(['contacted_at', 'follow_up_at']);
            $table->dropIndex(['lost_at', 'lost_reason']);
            $table->dropColumn([
                'contacted_at',
                'follow_up_at',
                'follow_up_notes',
                'lost_at',
                'lost_reason',
            ]);
        });

        DB::table('social_crm_settings')
            ->whereIn('key', [
                'social_sales_urgent_score_threshold',
                'social_sales_max_hours_without_contact',
                'social_sales_default_follow_up_hours',
                'social_sales_lost_reasons',
            ])
            ->delete();
    }
};
