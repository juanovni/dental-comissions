<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('voice_call_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('direction')->nullable();
            $table->string('provider_event_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['voice_call_id', 'provider_event_id'], 'voice_events_call_provider_unique');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_events');
    }
};
