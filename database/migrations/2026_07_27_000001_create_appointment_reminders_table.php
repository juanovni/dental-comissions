<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 32);
            $table->string('reminder_type', 64);
            $table->string('status', 32);
            $table->string('to_phone', 32)->nullable();
            $table->text('message')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['appointment_id', 'channel', 'reminder_type'], 'appointment_reminder_unique');
            $table->index(['channel', 'status']);
            $table->index(['scheduled_for', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_reminders');
    }
};
