<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procedures', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->string('category')->nullable();
            $table->decimal('internal_rate', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedures');
    }
};
