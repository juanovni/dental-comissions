<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 80);
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40)->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 40)->default('system');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['appointment_id', 'occurred_at']);
            $table->index(['event_type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_events');
    }
};
