<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_check_in_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('clinic_slug', 120);
            $table->string('channel', 40)->default('public');
            $table->string('status', 40);
            $table->string('failure_reason', 80)->nullable();
            $table->string('identifier_hash', 64)->nullable();
            $table->string('identifier_last_digits', 12)->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['clinic_slug', 'status', 'created_at']);
            $table->index(['appointment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_check_in_attempts');
    }
};
