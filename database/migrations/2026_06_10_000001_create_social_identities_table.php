<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform');
            $table->string('platform_user_id');
            $table->string('username')->nullable();
            $table->string('display_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('normalized_phone')->nullable();
            $table->string('status')->default('new_lead');
            $table->timestamp('linked_at')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'platform_user_id']);
            $table->index(['status', 'last_seen_at']);
            $table->index('patient_id');
            $table->index('normalized_phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_identities');
    }
};
