<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_clearance_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('consultation_id')->constrained('external_medical_consultations')->cascadeOnDelete();
            $table->foreignId('reviewed_by_professional_id')->constrained('professionals')->nullOnDelete();
            $table->string('decision'); // cleared, cleared_with_conditions, not_cleared, insufficient_information, expired, superseded
            $table->text('clinical_summary')->nullable();
            $table->text('conditions_summary')->nullable();
            $table->string('scope')->default('both'); // scheduling, execution, both
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->foreignId('issued_by_professional_id')->nullable()->constrained('professionals')->nullOnDelete();
            $table->string('issuer_name')->nullable();
            $table->string('document_reference')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('active'); // active, superseded, expired
            $table->timestamps();

            $table->index(['clinic_id', 'consultation_id', 'status']);
            $table->index(['clinic_id', 'decision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_clearance_decisions');
    }
};
