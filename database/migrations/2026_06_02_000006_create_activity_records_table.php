<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('professionals')->cascadeOnDelete();
            $table->foreignId('procedure_id')->constrained()->cascadeOnDelete();
            $table->date('activity_date');
            $table->time('activity_time')->nullable();
            $table->string('status')->default('pending_confirmation');
            $table->decimal('doctor_commission_amount', 10, 2)->nullable();
            $table->decimal('assistant_commission_total', 10, 2)->nullable();
            $table->decimal('internal_rate_snapshot', 10, 2)->nullable();
            $table->text('correction_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'activity_date']);
            $table->index(['doctor_id', 'status']);
            $table->index(['patient_id', 'activity_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_records');
    }
};
