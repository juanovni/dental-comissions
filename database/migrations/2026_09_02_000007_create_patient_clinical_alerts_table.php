<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_clinical_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('alert_name');
            $table->text('description')->nullable();
            $table->string('level'); // ClinicalAlertLevel: advisory, conditional_hold, hard_stop
            $table->string('source'); // ClinicalAlertSource
            $table->string('scope')->nullable(); // general, procedure_specific, medication_specific
            $table->json('context')->nullable(); // procedure_id, medication, etc.
            $table->string('status')->default('active'); // active, resolved, expired
            $table->foreignId('created_by_professional_id')->nullable()->constrained('professionals')->nullOnDelete();
            $table->foreignId('resolved_by_professional_id')->nullable()->constrained('professionals')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id', 'status', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_clinical_alerts');
    }
};
