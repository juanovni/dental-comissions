<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->timestamp('checked_in_at')->nullable()->after('confirmed_at');
            $table->timestamp('preparation_started_at')->nullable()->after('checked_in_at');
            $table->timestamp('ready_for_doctor_at')->nullable()->after('preparation_started_at');
            $table->timestamp('consultation_started_at')->nullable()->after('ready_for_doctor_at');
            $table->timestamp('consultation_finished_at')->nullable()->after('consultation_started_at');
            $table->string('check_in_source', 40)->nullable()->after('no_show_at');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn([
                'checked_in_at',
                'preparation_started_at',
                'ready_for_doctor_at',
                'consultation_started_at',
                'consultation_finished_at',
                'check_in_source',
            ]);
        });
    }
};
