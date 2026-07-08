<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_comment_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_comment_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->text('response_text')->nullable();
            $table->json('external_response')->nullable();
            $table->timestamps();

            $table->index(['social_comment_id', 'action']);
            $table->index('performed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_comment_actions');
    }
};
