<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_recall_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('recall_plan_id')->nullable()->constrained('patient_recall_plans')->nullOnDelete();
            $table->string('event_type'); // calculated, adjusted, scheduled, completed, cancelled, overdue
            $table->date('previous_date')->nullable();
            $table->date('new_date')->nullable();
            $table->foreignId('professional_id')->nullable()->constrained('professionals')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->string('source')->default('system'); // system, professional, admin
            $table->foreignId('policy_version_id')->nullable()->constrained('recall_policy_versions')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id', 'recall_plan_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_recall_events');
    }
};
