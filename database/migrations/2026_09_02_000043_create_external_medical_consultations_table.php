<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_medical_consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('requesting_professional_id')->constrained('professionals')->cascadeOnDelete();
            $table->foreignId('recipient_professional_id')->nullable()->constrained('professionals')->nullOnDelete();
            $table->foreignId('specialty_id')->nullable()->constrained('specialties')->nullOnDelete();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_institution')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('specialty_name')->nullable();
            $table->string('reason');
            $table->text('clinical_context')->nullable();
            $table->json('questions')->nullable();
            $table->text('supporting_documents')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id', 'status']);
            $table->index(['clinic_id', 'requesting_professional_id', 'status']);
            $table->index(['clinic_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_medical_consultations');
    }
};
