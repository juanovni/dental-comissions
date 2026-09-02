<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odontograms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('dentition_type')->default('permanent');
            $table->string('status')->default('draft'); // draft, signed
            $table->timestamp('recorded_at');
            $table->foreignId('recorded_by')->constrained('professionals');
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odontograms');
    }
};
