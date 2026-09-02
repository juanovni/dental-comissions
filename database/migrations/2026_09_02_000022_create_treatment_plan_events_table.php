<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plan_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('treatment_plan_revision_id')->nullable();
            $table->unsignedBigInteger('treatment_plan_item_id')->nullable();
            $table->string('event_type');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('professional_id')->nullable()->constrained('professionals')->nullOnDelete();
            $table->string('source')->default('admin');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->string('request_id')->nullable();
            $table->string('ip_hash')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'treatment_plan_id', 'occurred_at']);
            $table->index(['clinic_id', 'treatment_plan_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_events');
    }
};
