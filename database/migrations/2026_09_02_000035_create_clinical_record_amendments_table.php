<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_record_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinical_encounter_id')->constrained()->cascadeOnDelete();
            $table->string('amendment_type'); // correction, addition, clarification
            $table->text('reason')->nullable();
            $table->json('changes_snapshot')->nullable();
            $table->foreignId('amended_by')->constrained('professionals');
            $table->timestamp('amended_at');
            $table->foreignId('approved_by')->nullable()->constrained('professionals')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'clinical_encounter_id', 'amended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_record_amendments');
    }
};
