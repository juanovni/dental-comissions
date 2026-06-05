<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_assistants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assistant_id')->constrained('professionals')->cascadeOnDelete();
            $table->decimal('commission_amount', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['activity_record_id', 'assistant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_assistants');
    }
};
