<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dental_laboratory_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('treatment_plan_item_id')->nullable()->constrained('treatment_plan_items')->nullOnDelete();
            $table->foreignId('professional_id')->constrained('professionals')->nullOnDelete();
            $table->foreignId('laboratory_id')->nullable()->unsignedBigInteger(); // laboratorio externo
            $table->string('laboratory_name')->nullable();
            $table->string('status')->default('draft');
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->date('promised_date')->nullable();
            $table->date('received_date')->nullable();
            $table->date('ready_date')->nullable();
            $table->text('clinical_notes')->nullable();
            $table->text('lab_instructions')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id', 'status']);
            $table->index(['clinic_id', 'status', 'promised_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_laboratory_cases');
    }
};
