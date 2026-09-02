<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_medical_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('condition_name');
            $table->string('icd_code')->nullable();
            $table->string('status')->default('active'); // active, controlled, resolved, chronic
            $table->string('control_level')->nullable(); // well_controlled, partially_controlled, uncontrolled
            $table->string('treating_professional')->nullable();
            $table->string('source'); // patient_reported, professional_verified
            $table->foreignId('verified_by')->nullable()->constrained('professionals')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->date('diagnosed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_medical_conditions');
    }
};
