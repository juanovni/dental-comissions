<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_moderation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('platform')->nullable();
            $table->string('condition_type');
            $table->text('condition_value');
            $table->string('suggested_action')->nullable();
            $table->string('priority')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['platform', 'is_active']);
            $table->index('condition_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_moderation_rules');
    }
};
