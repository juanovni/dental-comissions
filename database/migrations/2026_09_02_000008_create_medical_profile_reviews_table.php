<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_profile_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('version_id')->constrained('patient_medical_profile_versions')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->constrained('professionals');
            $table->string('outcome'); // approved, needs_update, flags_acknowledged
            $table->json('acknowledged_alerts')->nullable();
            $table->json('changes_noted')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('next_review_at')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_profile_reviews');
    }
};
