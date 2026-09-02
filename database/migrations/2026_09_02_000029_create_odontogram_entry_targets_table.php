<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odontogram_entry_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('odontogram_entry_id')->constrained()->cascadeOnDelete();
            $table->string('target_type');
            $table->string('target_code');
            $table->string('tooth_code')->nullable();
            $table->string('surface')->nullable();
            $table->unsignedInteger('sequence_order')->default(0);
            $table->timestamps();

            $table->index(['odontogram_entry_id', 'target_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odontogram_entry_targets');
    }
};
