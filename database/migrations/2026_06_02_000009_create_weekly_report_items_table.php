<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_record_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['weekly_report_id', 'activity_record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_report_items');
    }
};
