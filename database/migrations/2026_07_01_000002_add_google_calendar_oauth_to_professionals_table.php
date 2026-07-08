<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professionals', function (Blueprint $table): void {
            $table->string('google_calendar_email', 255)->nullable()->after('email');
            $table->text('google_calendar_token')->nullable()->after('google_calendar_email');
            $table->timestamp('google_calendar_token_expires_at')->nullable()->after('google_calendar_token');
            $table->boolean('google_calendar_enabled')->default(false)->after('google_calendar_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('professionals', function (Blueprint $table): void {
            $table->dropColumn([
                'google_calendar_email',
                'google_calendar_token',
                'google_calendar_token_expires_at',
                'google_calendar_enabled',
            ]);
        });
    }
};
