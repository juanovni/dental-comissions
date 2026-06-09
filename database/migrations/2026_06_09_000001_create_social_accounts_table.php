<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('platform');
            $table->string('account_name');
            $table->string('external_account_id');
            $table->string('page_id')->nullable();
            $table->string('instagram_business_account_id')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->json('sync_settings')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'external_account_id']);
            $table->index(['platform', 'is_active']);
            $table->index('last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
