<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatment_plan_item_clearance_requirements', function (Blueprint $table) {
            $table->foreign('satisfied_by_decision_id')
                ->references('id')
                ->on('medical_clearance_decisions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('treatment_plan_item_clearance_requirements', function (Blueprint $table) {
            $table->dropForeign(['satisfied_by_decision_id']);
        });
    }
};
