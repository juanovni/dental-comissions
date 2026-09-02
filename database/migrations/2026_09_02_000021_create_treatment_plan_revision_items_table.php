<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plan_revision_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_plan_revision_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_plan_item_id')->constrained()->cascadeOnDelete();
            $table->string('procedure_name_snapshot');
            $table->string('procedure_code_snapshot')->nullable();
            $table->text('description_snapshot')->nullable();
            $table->string('tooth_snapshot')->nullable();
            $table->string('surface_snapshot')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->unsignedInteger('duration_minutes_snapshot')->nullable();
            $table->unsignedInteger('sessions_snapshot')->nullable();
            $table->string('clinical_priority_snapshot')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['treatment_plan_revision_id', 'treatment_plan_item_id']);
            $table->index(['clinic_id', 'treatment_plan_revision_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_revision_items');
    }
};
