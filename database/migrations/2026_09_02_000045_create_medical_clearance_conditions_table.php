<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_clearance_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('decision_id')->constrained('medical_clearance_decisions')->cascadeOnDelete();
            $table->string('condition_description');
            $table->boolean('is_mandatory')->default(true);
            $table->string('status')->default('pending'); // pending, verified, not_met, waived, expired
            $table->text('evidence')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'decision_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_clearance_conditions');
    }
};
