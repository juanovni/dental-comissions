<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plan_public_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_plan_revision_id')->constrained('treatment_plan_revisions');
            $table->string('token_hash')->unique();
            $table->string('token_prefix');
            $table->string('purpose')->default('review');
            $table->string('status')->default('active');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('first_viewed_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->string('recipient_phone_hash')->nullable();
            $table->string('recipient_email_hash')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['clinic_id', 'treatment_plan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_public_links');
    }
};
