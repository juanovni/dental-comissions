<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informed_consent_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('consent_id')->constrained('treatment_plan_item_consents')->cascadeOnDelete();
            $table->string('event_type'); // created, presented, signed, rejected, revoked, expired, waived, superseded, re_signed, substituted
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('professional_id')->nullable()->constrained('professionals')->nullOnDelete();
            $table->string('source')->default('admin'); // admin, doctor, patient_link, whatsapp, system
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->string('request_id')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'consent_id', 'occurred_at']);
            $table->index(['clinic_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('informed_consent_events');
    }
};
