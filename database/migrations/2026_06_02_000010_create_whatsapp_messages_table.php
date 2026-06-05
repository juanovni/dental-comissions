<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction');
            $table->string('status')->default('received');
            $table->string('from_phone');
            $table->string('to_phone');
            $table->text('message_body');
            $table->string('message_sid')->nullable()->unique();
            $table->foreignId('related_message_id')->nullable()->constrained('whatsapp_messages')->nullOnDelete();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['professional_id', 'status']);
            $table->index(['from_phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
