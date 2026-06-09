<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->string('external_post_id');
            $table->text('caption')->nullable();
            $table->text('media_url')->nullable();
            $table->text('permalink')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'external_post_id']);
            $table->index(['social_account_id', 'published_at']);
            $table->index('last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
