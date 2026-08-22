<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_accounts', function (Blueprint $table): void {
            $table->dropUnique('social_accounts_platform_external_account_id_unique');
            $table->unique(['clinic_id', 'platform', 'external_account_id'], 'social_accounts_clinic_platform_external_unique');
        });
    }

    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table): void {
            $table->dropUnique('social_accounts_clinic_platform_external_unique');
            $table->unique(['platform', 'external_account_id']);
        });
    }
};
