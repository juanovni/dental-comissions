<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odontogram_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('odontogram_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('odontogram_entry_id')->nullable();
            $table->string('event_type');
            $table->timestamp('occurred_at');
            $table->foreignId('created_by')->constrained('professionals');
            $table->string('source')->default('admin');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'odontogram_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odontogram_events');
    }
};
