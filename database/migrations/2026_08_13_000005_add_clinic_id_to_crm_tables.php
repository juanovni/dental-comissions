<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_accounts', function (Blueprint $table): void {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['clinic_id', 'platform', 'is_active']);
        });

        Schema::table('social_posts', function (Blueprint $table): void {
            $table->dropUnique('social_posts_platform_external_post_id_unique');
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unique(['clinic_id', 'platform', 'external_post_id']);
            $table->index(['clinic_id', 'social_account_id', 'published_at']);
        });

        Schema::table('social_comments', function (Blueprint $table): void {
            $table->dropUnique('social_comments_platform_external_comment_id_unique');
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unique(['clinic_id', 'platform', 'external_comment_id']);
            $table->index(['clinic_id', 'social_account_id', 'status']);
            $table->index(['clinic_id', 'social_post_id', 'published_at']);
        });

        Schema::table('social_identities', function (Blueprint $table): void {
            $table->dropUnique('social_identities_platform_platform_user_id_unique');
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unique(['clinic_id', 'platform', 'platform_user_id']);
            $table->index(['clinic_id', 'status', 'last_seen_at']);
            $table->index(['clinic_id', 'normalized_phone']);
        });

        Schema::table('social_comment_actions', function (Blueprint $table): void {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['clinic_id', 'social_comment_id', 'action']);
        });

        Schema::table('social_lead_alerts', function (Blueprint $table): void {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['clinic_id', 'alert_type', 'resolved_at']);
        });

        Schema::table('social_link_events', function (Blueprint $table): void {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['clinic_id', 'social_comment_id', 'event_type']);
        });

        Schema::table('social_reply_templates', function (Blueprint $table): void {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['clinic_id', 'category', 'is_active']);
        });

        Schema::table('social_moderation_rules', function (Blueprint $table): void {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['clinic_id', 'platform', 'is_active']);
        });

        Schema::table('social_crm_settings', function (Blueprint $table): void {
            $table->dropUnique('social_crm_settings_key_unique');
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unique(['clinic_id', 'key']);
            $table->index(['clinic_id', 'setting_group', 'is_active']);
        });

        Schema::table('whatsapp_messages', function (Blueprint $table): void {
            $table->dropUnique('whatsapp_messages_message_sid_unique');
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unique(['clinic_id', 'message_sid']);
            $table->index(['clinic_id', 'professional_id', 'status']);
            $table->index(['clinic_id', 'from_phone']);
        });

        Schema::table('calendar_integrations', function (Blueprint $table): void {
            $table->dropUnique('calendar_integrations_provider_unique');
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unique(['clinic_id', 'provider']);
            $table->index(['clinic_id', 'is_enabled']);
        });

        Schema::table('voice_calls', function (Blueprint $table): void {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['clinic_id', 'status']);
            $table->index(['clinic_id', 'provider_call_id']);
        });

        Schema::table('voice_events', function (Blueprint $table): void {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['clinic_id', 'voice_call_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('voice_events', function (Blueprint $table): void {
            $table->dropIndex(['clinic_id', 'voice_call_id', 'type']);
            $table->dropConstrainedForeignId('clinic_id');
        });

        Schema::table('voice_calls', function (Blueprint $table): void {
            $table->dropIndex(['clinic_id', 'status']);
            $table->dropIndex(['clinic_id', 'provider_call_id']);
            $table->dropConstrainedForeignId('clinic_id');
        });

        Schema::table('calendar_integrations', function (Blueprint $table): void {
            $table->dropUnique(['clinic_id', 'provider']);
            $table->dropIndex(['clinic_id', 'is_enabled']);
            $table->dropConstrainedForeignId('clinic_id');
            $table->unique('provider');
        });

        Schema::table('whatsapp_messages', function (Blueprint $table): void {
            $table->dropUnique(['clinic_id', 'message_sid']);
            $table->dropIndex(['clinic_id', 'professional_id', 'status']);
            $table->dropIndex(['clinic_id', 'from_phone']);
            $table->dropConstrainedForeignId('clinic_id');
            $table->unique('message_sid');
        });

        Schema::table('social_crm_settings', function (Blueprint $table): void {
            $table->dropUnique(['clinic_id', 'key']);
            $table->dropIndex(['clinic_id', 'setting_group', 'is_active']);
            $table->dropConstrainedForeignId('clinic_id');
            $table->unique('key');
        });

        Schema::table('social_moderation_rules', function (Blueprint $table): void {
            $table->dropIndex(['clinic_id', 'platform', 'is_active']);
            $table->dropConstrainedForeignId('clinic_id');
        });

        Schema::table('social_reply_templates', function (Blueprint $table): void {
            $table->dropIndex(['clinic_id', 'category', 'is_active']);
            $table->dropConstrainedForeignId('clinic_id');
        });

        Schema::table('social_link_events', function (Blueprint $table): void {
            $table->dropIndex(['clinic_id', 'social_comment_id', 'event_type']);
            $table->dropConstrainedForeignId('clinic_id');
        });

        Schema::table('social_lead_alerts', function (Blueprint $table): void {
            $table->dropIndex(['clinic_id', 'alert_type', 'resolved_at']);
            $table->dropConstrainedForeignId('clinic_id');
        });

        Schema::table('social_comment_actions', function (Blueprint $table): void {
            $table->dropIndex(['clinic_id', 'social_comment_id', 'action']);
            $table->dropConstrainedForeignId('clinic_id');
        });

        Schema::table('social_identities', function (Blueprint $table): void {
            $table->dropUnique(['clinic_id', 'platform', 'platform_user_id']);
            $table->dropIndex(['clinic_id', 'status', 'last_seen_at']);
            $table->dropIndex(['clinic_id', 'normalized_phone']);
            $table->dropConstrainedForeignId('clinic_id');
            $table->unique(['platform', 'platform_user_id']);
        });

        Schema::table('social_comments', function (Blueprint $table): void {
            $table->dropUnique(['clinic_id', 'platform', 'external_comment_id']);
            $table->dropIndex(['clinic_id', 'social_account_id', 'status']);
            $table->dropIndex(['clinic_id', 'social_post_id', 'published_at']);
            $table->dropConstrainedForeignId('clinic_id');
            $table->unique(['platform', 'external_comment_id']);
        });

        Schema::table('social_posts', function (Blueprint $table): void {
            $table->dropUnique(['clinic_id', 'platform', 'external_post_id']);
            $table->dropIndex(['clinic_id', 'social_account_id', 'published_at']);
            $table->dropConstrainedForeignId('clinic_id');
            $table->unique(['platform', 'external_post_id']);
        });

        Schema::table('social_accounts', function (Blueprint $table): void {
            $table->dropIndex(['clinic_id', 'platform', 'is_active']);
            $table->dropConstrainedForeignId('clinic_id');
        });
    }
};
