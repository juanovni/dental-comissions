<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('domain'); // periodontal, caries, implant, orthodontic, general
            $table->string('risk_level'); // low, moderate, high, very_high
            $table->json('factors')->nullable();
            $table->text('evidence')->nullable();
            $table->foreignId('assessed_by')->constrained('professionals')->nullOnDelete();
            $table->date('assessed_at');
            $table->date('valid_until')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id', 'domain', 'valid_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_risk_assessments');
    }
};
