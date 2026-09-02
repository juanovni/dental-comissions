<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_readiness_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('treatment_plan_item_id')->nullable();
            $table->string('moment'); // scheduling, execution
            $table->string('result'); // not_ready, ready, blocked
            $table->json('evidence')->nullable(); // versiones de perfil, consentimientos, etc.
            $table->json('blocking_reasons')->nullable();
            $table->foreignId('evaluated_by')->nullable()->constrained('professionals')->nullOnDelete();
            $table->timestamp('evaluated_at');
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id', 'moment']);
            $table->index(['clinic_id', 'treatment_plan_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_readiness_evaluations');
    }
};
