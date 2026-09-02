<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('medication_name');
            $table->string('active_ingredient')->nullable();
            $table->string('dose')->nullable();
            $table->string('frequency')->nullable();
            $table->string('indication')->nullable();
            $table->string('status')->default('active'); // active, discontinued, paused
            $table->string('source'); // patient_reported, professional_verified
            $table->foreignId('verified_by')->nullable()->constrained('professionals')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_medications');
    }
};
