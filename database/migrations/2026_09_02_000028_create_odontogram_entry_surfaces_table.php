<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odontogram_entry_surfaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('odontogram_entry_id')->constrained()->cascadeOnDelete();
            $table->string('surface');
            $table->timestamps();

            $table->unique(['odontogram_entry_id', 'surface']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odontogram_entry_surfaces');
    }
};
