<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informed_consent_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('informed_consent_templates')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('language', 10)->default('es');
            $table->string('title');
            $table->text('content_body');
            $table->text('risks')->nullable();
            $table->text('alternatives')->nullable();
            $table->text('post_procedure_instructions')->nullable();
            $table->text('patient_rights')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->string('content_checksum', 64);
            $table->string('status')->default('draft'); // draft, active, archived
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['template_id', 'version_number']);
            $table->index(['clinic_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('informed_consent_template_versions');
    }
};
