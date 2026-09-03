<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performed_procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinical_encounter_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('treatment_plan_item_id')->nullable();
            $table->unsignedBigInteger('procedure_id')->nullable();
            $table->string('procedure_name_snapshot');
            $table->string('procedure_code_snapshot')->nullable();
            $table->string('tooth')->nullable();
            $table->string('surface')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('status')->default('pending');
            $table->foreignId('performed_by')->constrained('professionals');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('clinical_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id', 'clinical_encounter_id', 'status']);
            $table->index(['clinic_id', 'treatment_plan_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performed_procedures');
    }
};
