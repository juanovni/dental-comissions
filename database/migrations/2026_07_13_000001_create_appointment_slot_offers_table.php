<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_slot_offers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('social_comment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_message_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token', 80)->unique();
            $table->string('status', 32)->default('pending')->index();
            $table->unsignedSmallInteger('selected_option_index')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['social_comment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_slot_offers');
    }
};
