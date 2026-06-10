<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_records', function (Blueprint $table) {
            $table->foreignId('social_comment_id')->nullable()->after('payment_method_commission_snapshot')->constrained()->nullOnDelete();
            $table->foreignId('social_identity_id')->nullable()->after('social_comment_id')->constrained()->nullOnDelete();
            $table->foreignId('social_post_id')->nullable()->after('social_identity_id')->constrained()->nullOnDelete();
            $table->timestamp('social_attributed_at')->nullable()->after('social_post_id');

            $table->index(['social_post_id', 'activity_date']);
            $table->index(['social_identity_id', 'activity_date']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_records', function (Blueprint $table) {
            $table->dropIndex(['social_post_id', 'activity_date']);
            $table->dropIndex(['social_identity_id', 'activity_date']);

            $table->dropConstrainedForeignId('social_comment_id');
            $table->dropConstrainedForeignId('social_identity_id');
            $table->dropConstrainedForeignId('social_post_id');
            $table->dropColumn('social_attributed_at');
        });
    }
};
