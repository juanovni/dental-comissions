<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('professional_id')->nullable()->constrained('professionals')->nullOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained('procedures')->nullOnDelete();
            $table->string('role');
            $table->string('commission_type');
            $table->decimal('fixed_amount', 10, 2)->nullable();
            $table->decimal('percentage_value', 5, 2)->nullable();
            $table->decimal('internal_rate', 10, 2)->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['role', 'commission_type', 'is_active']);
            $table->index(['professional_id', 'procedure_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_rules');
    }
};
