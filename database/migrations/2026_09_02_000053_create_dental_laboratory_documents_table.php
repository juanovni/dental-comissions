<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dental_laboratory_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_id')->constrained('dental_laboratory_cases')->cascadeOnDelete();
            $table->foreignId('case_item_id')->nullable()->constrained('dental_laboratory_case_items')->nullOnDelete();
            $table->string('document_type'); // prescription, scan, photo, archive, guide, result
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['clinic_id', 'case_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_laboratory_documents');
    }
};
