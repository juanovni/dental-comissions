<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_slot_holds', function (Blueprint $table): void {
            $table->foreignId('social_comment_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('appointment_slot_holds', function (Blueprint $table): void {
            $table->foreignId('social_comment_id')->nullable(false)->change();
        });
    }
};
