<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->foreignId('procedure_id')->nullable()->after('social_account_id')->constrained()->nullOnDelete();
            $table->string('campaign_name')->nullable()->after('permalink');
            $table->string('campaign_goal')->nullable()->after('campaign_name');
            $table->decimal('revenue_generated', 12, 2)->default(0)->after('campaign_goal');
            $table->unsignedInteger('conversion_count')->default(0)->after('revenue_generated');
            $table->json('metadata')->nullable()->after('conversion_count');

            $table->index(['procedure_id', 'published_at']);
            $table->index('campaign_name');
        });
    }

    public function down(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropIndex(['procedure_id', 'published_at']);
            $table->dropIndex(['campaign_name']);

            $table->dropConstrainedForeignId('procedure_id');
            $table->dropColumn([
                'campaign_name',
                'campaign_goal',
                'revenue_generated',
                'conversion_count',
                'metadata',
            ]);
        });
    }
};
