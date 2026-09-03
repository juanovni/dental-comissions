<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('treatment_plan_item_id')->nullable();
            $table->unsignedBigInteger('source_appointment_id')->nullable();
            $table->foreignId('responsible_professional_id')->constrained('professionals');
            $table->unsignedBigInteger('specialty_id')->nullable();
            $table->string('case_type');
            $table->string('status')->default('draft');
            $table->string('title');
            $table->text('diagnosis_summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id', 'case_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_cases');
    }
};
