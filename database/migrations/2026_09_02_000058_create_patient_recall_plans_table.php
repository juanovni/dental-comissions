<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_recall_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('recall_type_id')->constrained('recall_types')->cascadeOnDelete();
            $table->foreignId('risk_assessment_id')->nullable()->constrained('patient_risk_assessments')->nullOnDelete();
            $table->foreignId('policy_version_id')->nullable()->constrained('recall_policy_versions')->nullOnDelete();
            $table->foreignId('last_encounter_id')->nullable()->constrained('clinical_encounters')->nullOnDelete();
            $table->foreignId('last_procedure_id')->nullable()->constrained('performed_procedures')->nullOnDelete();
            $table->date('calculated_date');
            $table->date('adjusted_date')->nullable();
            $table->text('adjustment_reason')->nullable();
            $table->foreignId('adjusted_by')->nullable()->constrained('professionals')->nullOnDelete();
            $table->foreignId('related_appointment_id')->nullable()->unsignedBigInteger();
            $table->string('status')->default('active'); // active, completed, cancelled, overdue
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id', 'recall_type_id', 'status']);
            $table->index(['clinic_id', 'calculated_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_recall_plans');
    }
};
