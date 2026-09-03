<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatment_plan_item_consent_requirements', function (Blueprint $table) {
            $table->foreign('satisfied_by_consent_id')
                ->references('id')
                ->on('treatment_plan_item_consents')
                ->nullOnDelete();
        });

        Schema::table('informed_consent_templates', function (Blueprint $table) {
            $table->foreign('current_version_id')
                ->references('id')
                ->on('informed_consent_template_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('treatment_plan_item_consent_requirements', function (Blueprint $table) {
            $table->dropForeign(['satisfied_by_consent_id']);
        });

        Schema::table('informed_consent_templates', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });
    }
};
