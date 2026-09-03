<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_encounters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('clinical_case_id')->nullable();
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->foreignId('professional_id')->constrained('professionals');
            $table->string('encounter_type');
            $table->string('care_path');
            $table->string('status')->default('draft');
            $table->timestamp('occurred_at');
            $table->text('subjective_notes')->nullable();
            $table->text('objective_findings')->nullable();
            $table->text('assessment')->nullable();
            $table->text('plan_notes')->nullable();
            $table->foreignId('signed_by')->nullable()->constrained('professionals')->nullOnDelete();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id', 'occurred_at']);
            $table->index(['clinic_id', 'professional_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_encounters');
    }
};
