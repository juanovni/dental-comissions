<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plan_item_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_plan_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('planned_quantity')->default(1);
            $table->unsignedInteger('completed_quantity')->default(0);
            $table->unsignedInteger('session_number')->nullable();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['treatment_plan_item_id', 'appointment_id']);
            $table->index(['clinic_id', 'treatment_plan_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_item_appointments');
    }
};
