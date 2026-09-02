<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('procedure_id')->nullable();
            $table->unsignedBigInteger('phase_id')->nullable();
            $table->unsignedBigInteger('alternative_group_id')->nullable();
            $table->string('tooth')->nullable();
            $table->string('surface')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('duration_minutes')->default(30);
            $table->unsignedInteger('sessions')->default(1);
            $table->unsignedBigInteger('specialty_id')->nullable();
            $table->unsignedBigInteger('suggested_professional_id')->nullable();
            $table->string('clinical_priority')->default('normal');
            $table->unsignedInteger('sequence_order')->default(0);
            $table->string('readiness_status')->default('not_ready');
            $table->text('clinical_hold_reason')->nullable();
            $table->date('recommended_from')->nullable();
            $table->date('recommended_until')->nullable();
            $table->string('commercial_status')->default('proposed');
            $table->string('clinical_status')->default('unscheduled');
            $table->text('rejection_reason')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['clinic_id', 'treatment_plan_id', 'commercial_status', 'clinical_status']);
            $table->index(['clinic_id', 'treatment_plan_id', 'phase_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_items');
    }
};
