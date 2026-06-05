<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_id')->constrained()->cascadeOnDelete();
            $table->date('week_start');
            $table->date('week_end');
            $table->string('status')->default('draft');
            $table->integer('total_activities')->default(0);
            $table->integer('total_patients')->default(0);
            $table->integer('total_procedures')->default(0);
            $table->decimal('total_doctor_commission', 10, 2)->default(0);
            $table->decimal('total_assistant_commission', 10, 2)->default(0);
            $table->decimal('total_commission', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['professional_id', 'week_start']);
            $table->index(['status', 'week_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_reports');
    }
};
