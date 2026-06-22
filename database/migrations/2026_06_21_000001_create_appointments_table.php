<?php

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('social_comment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('social_identity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('social_post_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('status', 40)->default(AppointmentStatus::PendingConfirmation->value);
            $table->string('source', 40)->default(AppointmentSource::AdminManual->value);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('no_show_at')->nullable();
            $table->json('metadata')->nullable();
            $table->string('external_provider', 80)->nullable();
            $table->string('external_appointment_id', 120)->nullable();
            $table->string('external_calendar_id', 120)->nullable();
            $table->string('external_status', 80)->nullable();
            $table->json('external_payload')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('sync_error')->nullable();
            $table->timestamps();

            $table->index(['scheduled_at', 'status']);
            $table->index(['patient_id', 'scheduled_at']);
            $table->index(['social_comment_id', 'scheduled_at']);
            $table->index(['social_post_id', 'scheduled_at']);
            $table->index(['external_provider', 'external_appointment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
