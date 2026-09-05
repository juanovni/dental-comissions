<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dental_laboratory_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_id')->constrained('dental_laboratory_cases')->cascadeOnDelete();
            $table->foreignId('case_item_id')->nullable()->constrained('dental_laboratory_case_items')->nullOnDelete();
            $table->string('event_type'); // ordered, sent, received, quality_review, adjustment, remake, cancelled, communicated
            $table->text('notes')->nullable();
            $table->foreignId('professional_id')->nullable()->constrained('professionals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'case_id', 'event_type']);
            $table->index(['clinic_id', 'case_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_laboratory_events');
    }
};
