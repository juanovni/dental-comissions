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
            $table->unsignedInteger('interest_score')->default(0)->after('conversion_status');
            $table->timestamp('hot_lead_at')->nullable()->after('interest_score');
            $table->timestamp('last_smart_link_visited_at')->nullable()->after('hot_lead_at');
            $table->timestamp('reheated_at')->nullable()->after('last_smart_link_visited_at');

            $table->index(['interest_score', 'hot_lead_at']);
            $table->index('reheated_at');
        });

        $now = now();

        DB::table('social_crm_settings')->insertOrIgnore([
            [
                'setting_group' => 'scoring',
                'key' => 'social_score_reheated_revisit_bonus',
                'label' => 'Bonus por lead recalentado',
                'value_type' => 'integer',
                'value' => json_encode(10),
                'notes' => 'Puntos adicionales cuando el lead vuelve al Smart Link despues de un tiempo muerto.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'scoring',
                'key' => 'social_reheated_after_hours',
                'label' => 'Horas para considerar lead recalentado',
                'value_type' => 'integer',
                'value' => json_encode(72),
                'notes' => 'Si vuelve al Smart Link despues de estas horas, se marca como Lead Recalentado.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::table('social_comments', function (Blueprint $table) {
            $table->dropIndex(['interest_score', 'hot_lead_at']);
            $table->dropIndex(['reheated_at']);
            $table->dropColumn([
                'interest_score',
                'hot_lead_at',
                'last_smart_link_visited_at',
                'reheated_at',
            ]);
        });

        DB::table('social_crm_settings')
            ->whereIn('key', [
                'social_score_reheated_revisit_bonus',
                'social_reheated_after_hours',
            ])
            ->delete();
    }
};
