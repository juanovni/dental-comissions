<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plan_item_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_plan_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('depends_on_item_id')->constrained('treatment_plan_items');
            $table->string('dependency_type');
            $table->unsignedInteger('minimum_interval_days')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'treatment_plan_item_id']);
            $table->unique(['treatment_plan_item_id', 'depends_on_item_id', 'dependency_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_item_dependencies');
    }
};
