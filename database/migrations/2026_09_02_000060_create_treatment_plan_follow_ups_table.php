<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plan_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_plan_id')->constrained('treatment_plans')->cascadeOnDelete();
            $table->foreignId('treatment_plan_item_id')->nullable()->constrained('treatment_plan_items')->nullOnDelete();
            $table->string('channel'); // whatsapp, phone, email, sms, in_person
            $table->string('reason');
            $table->string('status')->default('pending'); // pending, performed, failed, cancelled
            $table->timestamp('scheduled_at');
            $table->timestamp('performed_at')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('outcome')->nullable(); // contacted, no_answer, rescheduled, completed, declined
            $table->text('notes')->nullable();
            $table->timestamp('next_action_at')->nullable();
            $table->string('external_reference')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'treatment_plan_id', 'status']);
            $table->index(['clinic_id', 'scheduled_at', 'status']);
            $table->index(['clinic_id', 'next_action_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_follow_ups');
    }
};
