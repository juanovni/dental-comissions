<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dental_laboratory_case_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_id')->constrained('dental_laboratory_cases')->cascadeOnDelete();
            $table->foreignId('treatment_plan_item_id')->nullable()->constrained('treatment_plan_items')->nullOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained('procedures')->nullOnDelete();
            $table->string('tooth')->nullable();
            $table->string('work_type'); // crown, bridge, denture, implant_abutment, orthodontic, other
            $table->string('material')->nullable();
            $table->string('shade')->nullable();
            $table->text('specifications')->nullable();
            $table->integer('quantity')->default(1);
            $table->string('status')->default('pending');
            $table->text('quality_notes')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'case_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_laboratory_case_items');
    }
};
