<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_safety_rule_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinical_safety_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('treatment_plan_item_id')->nullable();
            $table->foreignId('evaluation_id')->nullable()->constrained('clinical_readiness_evaluations')->nullOnDelete();
            // Resultado de la evaluacion
            $table->string('result'); // triggered, not_triggered, overridden
            $table->string('resulting_level')->nullable(); // ClinicalAlertLevel
            $table->text('evidence')->nullable();
            $table->text('context')->nullable();
            // Override si aplica
            $table->boolean('was_overridden')->default(false);
            $table->text('override_reason')->nullable();
            $table->foreignId('overridden_by_professional_id')->nullable()->constrained('professionals')->nullOnDelete();
            $table->timestamp('overridden_at')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id', 'clinical_safety_rule_id']);
            $table->index(['clinic_id', 'treatment_plan_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_safety_rule_applications');
    }
};
