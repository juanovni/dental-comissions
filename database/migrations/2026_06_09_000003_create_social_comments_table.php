<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_post_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_comment_id')->nullable()->constrained('social_comments')->nullOnDelete();
            $table->string('platform');
            $table->string('external_comment_id');
            $table->string('external_parent_comment_id')->nullable();
            $table->string('author_name')->nullable();
            $table->string('author_username')->nullable();
            $table->string('author_external_id')->nullable();
            $table->text('comment_text');
            $table->string('classification')->nullable();
            $table->string('sentiment')->nullable();
            $table->string('priority')->nullable();
            $table->string('reputation_risk')->nullable();
            $table->string('status')->default('new');
            $table->string('suggested_action')->nullable();
            $table->string('response_channel')->nullable();
            $table->text('suggested_reply')->nullable();
            $table->boolean('requires_human_review')->default(false);
            $table->text('ai_reason')->nullable();
            $table->json('ai_response')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->json('raw_payload')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'external_comment_id']);
            $table->index(['social_account_id', 'status']);
            $table->index(['social_post_id', 'published_at']);
            $table->index(['classification', 'priority']);
            $table->index(['reputation_risk', 'requires_human_review']);
            $table->index('processed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_comments');
    }
};
