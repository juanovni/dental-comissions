<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plan_item_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_plan_revision_id')->constrained('treatment_plan_revisions');
            $table->foreignId('treatment_plan_revision_item_id')->constrained('treatment_plan_revision_items');
            $table->unsignedBigInteger('treatment_plan_public_link_id')->nullable();
            $table->string('response'); // accepted, rejected, contact_requested
            $table->timestamp('responded_at');
            $table->string('source'); // patient_link, whatsapp, admin, phone
            $table->text('notes')->nullable();
            $table->string('ip_hash')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'treatment_plan_id', 'responded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_item_responses');
    }
};
