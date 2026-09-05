<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recall_policy_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recall_type_id')->constrained('recall_types')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('risk_level'); // low, moderate, high, very_high
            $table->unsignedInteger('interval_months');
            $table->text('notes')->nullable();
            $table->string('status')->default('draft'); // draft, active, archived
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['recall_type_id', 'version_number']);
            $table->index(['clinic_id', 'risk_level', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recall_policy_versions');
    }
};
