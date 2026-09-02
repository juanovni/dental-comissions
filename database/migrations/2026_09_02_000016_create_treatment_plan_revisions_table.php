<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plan_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('revision_number');
            $table->string('status')->default('draft');
            $table->string('currency');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('payment_terms')->nullable();
            $table->text('patient_notes')->nullable();
            $table->text('internal_change_reason')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('content_checksum')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            $table->unique(['treatment_plan_id', 'revision_number']);
            $table->index(['clinic_id', 'treatment_plan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_revisions');
    }
};
