<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('social_crm_settings')->insert([
            [
                'setting_group' => 'auto_reply',
                'key' => 'social_auto_reply_allowed_social_account_ids',
                'label' => 'Cuentas sociales habilitadas para auto-respuesta',
                'value_type' => 'array',
                'value' => json_encode([]),
                'notes' => 'Lista opcional de IDs internos de social_accounts. Si esta vacia, aplica a todas las cuentas activas. Usar durante rollout para limitar publicacion real a cuentas piloto.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('social_crm_settings')
            ->where('key', 'social_auto_reply_allowed_social_account_ids')
            ->delete();
    }
};
