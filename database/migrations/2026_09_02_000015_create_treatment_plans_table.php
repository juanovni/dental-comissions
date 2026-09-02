<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('responsible_professional_id')->constrained('professionals');
            $table->unsignedBigInteger('source_appointment_id')->nullable();
            $table->unsignedBigInteger('current_revision_id')->nullable();
            $table->string('code');
            $table->string('title');
            $table->text('diagnosis_summary')->nullable();
            $table->string('clinical_priority')->default('normal');
            $table->string('lifecycle_status')->default('draft');
            $table->string('commercial_status')->default('not_sent');
            $table->string('clinical_status')->default('not_started');
            $table->string('currency')->default('USD');
            $table->date('valid_until')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['clinic_id', 'code']);
            $table->index(['clinic_id', 'lifecycle_status', 'valid_until']);
            $table->index(['clinic_id', 'patient_id', 'commercial_status', 'clinical_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plans');
    }
};
