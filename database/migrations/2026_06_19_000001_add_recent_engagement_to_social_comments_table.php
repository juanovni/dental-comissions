<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_comments', function (Blueprint $table): void {
            $table->unsignedInteger('recent_engagement_score')->default(0)->after('interest_score');
            $table->timestamp('last_engagement_at')->nullable()->after('recent_engagement_score');
            $table->unsignedInteger('engagement_event_count_1h')->default(0)->after('last_engagement_at');
            $table->unsignedInteger('engagement_event_count_24h')->default(0)->after('engagement_event_count_1h');
            $table->string('last_engagement_event_type', 60)->nullable()->after('engagement_event_count_24h');
            $table->string('engagement_priority_reason')->nullable()->after('last_engagement_event_type');

            $table->index(['recent_engagement_score', 'last_engagement_at']);
        });
    }

    public function down(): void
    {
        Schema::table('social_comments', function (Blueprint $table): void {
            $table->dropIndex(['recent_engagement_score', 'last_engagement_at']);
            $table->dropColumn([
                'recent_engagement_score',
                'last_engagement_at',
                'engagement_event_count_1h',
                'engagement_event_count_24h',
                'last_engagement_event_type',
                'engagement_priority_reason',
            ]);
        });
    }
};
