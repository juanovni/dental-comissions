<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_comments', function (Blueprint $table): void {
            $table->timestamp('platform_hidden_at')->nullable()->after('is_hidden')->index();
            $table->json('platform_hide_response')->nullable()->after('platform_hidden_at');
            $table->text('platform_hide_error')->nullable()->after('platform_hide_response');
        });
    }

    public function down(): void
    {
        Schema::table('social_comments', function (Blueprint $table): void {
            $table->dropIndex(['platform_hidden_at']);
            $table->dropColumn([
                'platform_hidden_at',
                'platform_hide_response',
                'platform_hide_error',
            ]);
        });
    }
};
