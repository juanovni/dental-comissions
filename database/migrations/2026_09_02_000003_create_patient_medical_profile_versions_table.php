<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_medical_profile_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_medical_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('status'); // MedicalProfileVersionStatus
            $table->string('reporter'); // patient_reported, professional_verified
            $table->json('allergies_snapshot')->nullable();
            $table->json('medications_snapshot')->nullable();
            $table->json('conditions_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['patient_medical_profile_id', 'version_number']);
            $table->index(['clinic_id', 'patient_medical_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_medical_profile_versions');
    }
};
