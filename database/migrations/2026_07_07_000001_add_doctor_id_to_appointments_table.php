<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->foreignId('doctor_id')
                ->nullable()
                ->after('procedure_id')
                ->constrained('professionals')
                ->nullOnDelete();

            $table->index(['doctor_id', 'scheduled_at']);
            $table->index(['doctor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex(['doctor_id', 'scheduled_at']);
            $table->dropIndex(['doctor_id', 'status']);
            $table->dropConstrainedForeignId('doctor_id');
        });
    }
};
