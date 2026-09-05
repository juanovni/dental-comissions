<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('urgent_care_intakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('clinical_encounter_id')->constrained('clinical_encounters')->cascadeOnDelete();
            $table->foreignId('professional_id')->constrained('professionals')->nullOnDelete();
            $table->string('arrival_mode')->default('walk_in'); // walk_in, ambulance, transfer, self_referral
            $table->string('triage_category')->default('urgent'); // immediate, urgent, semi_urgent, non_urgent
            $table->text('chief_complaint');
            $table->text('red_flags')->nullable();
            $table->string('acute_medical_screen_status')->default('pending'); // pending, cleared, requires_followup
            $table->string('disposition')->nullable(); // discharged, admitted, transferred, deferred
            $table->text('disposition_notes')->nullable();
            $table->timestamp('deferred_completion_due_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id', 'triage_category']);
            $table->index(['clinic_id', 'disposition']);
            $table->index(['clinic_id', 'deferred_completion_due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('urgent_care_intakes');
    }
};
