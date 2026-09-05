<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plan_item_clearance_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_plan_item_id')->constrained('treatment_plan_items')->cascadeOnDelete();
            $table->string('requirement_type'); // medical_clearance, specialist_report, lab_result, imaging, pre_anesthetic, cardiac_clearance, custom
            $table->text('description')->nullable();
            $table->string('scope')->default('both'); // scheduling, execution, both
            $table->string('status')->default('active'); // active, satisfied, waived, expired, superseded
            $table->foreignId('satisfied_by_decision_id')->nullable()->unsignedBigInteger();
            $table->text('waiver_reason')->nullable();
            $table->foreignId('waived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('waived_at')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'treatment_plan_item_id', 'status']);
            $table->index(['clinic_id', 'requirement_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_item_clearance_requirements');
    }
};
