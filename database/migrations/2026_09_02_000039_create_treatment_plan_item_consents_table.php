<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plan_item_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requirement_id')->constrained('treatment_plan_item_consent_requirements')->cascadeOnDelete();
            $table->foreignId('template_version_id')->constrained('informed_consent_template_versions')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('signer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('signer_type')->default('patient'); // patient, representative, guardian
            $table->string('signer_name')->nullable();
            $table->string('signer_relationship')->nullable();
            $table->foreignId('professional_id')->constrained('professionals')->nullOnDelete();
            $table->string('professional_who_explained')->nullable();
            $table->string('status')->default('pending'); // pending, signed, rejected, revoked, expired, waived, superseded
            $table->timestamp('signed_at')->nullable();
            $table->text('explanation_notes')->nullable();
            $table->text('patient_questions')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('signature_data')->nullable(); // base64 signature or file path
            $table->string('signature_ip_hash', 64)->nullable();
            $table->string('signature_user_agent')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'requirement_id', 'status']);
            $table->index(['clinic_id', 'patient_id', 'status']);
            $table->index(['clinic_id', 'template_version_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_item_consents');
    }
};
