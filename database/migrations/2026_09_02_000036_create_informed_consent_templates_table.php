<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informed_consent_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('general'); // general, procedure, surgery, anesthesia, etc.
            $table->boolean('is_active')->default(true);
            $table->foreignId('current_version_id')->nullable()->unsignedBigInteger();
            $table->timestamps();

            $table->index(['clinic_id', 'is_active']);
            $table->index(['clinic_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('informed_consent_templates');
    }
};
