<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odontogram_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('odontogram_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('clinical_encounter_id')->nullable();
            $table->unsignedBigInteger('treatment_plan_item_id')->nullable();
            $table->unsignedBigInteger('performed_procedure_id')->nullable();
            $table->string('tooth_code')->nullable();
            $table->string('entry_type');
            $table->string('condition_code');
            $table->string('status')->default('active');
            $table->string('severity')->nullable();
            $table->text('diagnosis_text')->nullable();
            $table->text('observations')->nullable();
            $table->foreignId('recorded_by')->constrained('professionals');
            $table->timestamp('recorded_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'odontogram_id', 'entry_type', 'status']);
            $table->index(['clinic_id', 'patient_id', 'tooth_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odontogram_entries');
    }
};
