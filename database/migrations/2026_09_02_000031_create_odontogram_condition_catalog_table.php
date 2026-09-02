<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odontogram_condition_catalog', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinic_id')->nullable();
            $table->string('code');
            $table->string('name');
            $table->string('category'); // preexisting, finding, diagnosis, treatment
            $table->string('entry_type');
            $table->string('symbol')->nullable();
            $table->string('color')->nullable();
            $table->string('applies_to')->default('tooth'); // tooth, surface, arch
            $table->boolean('requires_surface')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['clinic_id', 'code']);
            $table->index(['clinic_id', 'category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odontogram_condition_catalog');
    }
};
