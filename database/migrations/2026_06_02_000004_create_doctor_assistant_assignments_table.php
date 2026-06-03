<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_assistant_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('professionals')->cascadeOnDelete();
            $table->foreignId('assistant_id')->constrained('professionals')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamps();

            $table->unique(['doctor_id', 'assistant_id']);
            $table->index(['doctor_id', 'is_active']);
            $table->index(['assistant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_assistant_assignments');
    }
};
