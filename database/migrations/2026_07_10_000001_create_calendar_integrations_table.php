<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_integrations', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 80)->unique();
            $table->string('account_email')->nullable();
            $table->string('calendar_id')->default('primary');
            $table->text('token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_integrations');
    }
};
