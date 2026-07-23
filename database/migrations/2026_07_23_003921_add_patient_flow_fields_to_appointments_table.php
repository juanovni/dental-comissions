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
            $table->timestamp('on_the_way_at')->nullable()->after('checked_in_at');
            $table->timestamp('consultation_started_at')->nullable()->after('on_the_way_at');
            $table->timestamp('consultation_finished_at')->nullable()->after('consultation_started_at');
            $table->string('room', 80)->nullable()->after('consultation_finished_at');
            $table->unsignedSmallInteger('waiting_time_minutes')->nullable()->after('room');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn([
                'checked_in_at',
                'on_the_way_at',
                'consultation_started_at',
                'consultation_finished_at',
                'room',
                'waiting_time_minutes',
            ]);
        });
    }
};
