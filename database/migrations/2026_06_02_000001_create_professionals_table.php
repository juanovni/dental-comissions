<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professionals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role');
            $table->string('whatsapp_phone')->nullable()->unique();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('can_register_via_whatsapp')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['role', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professionals');
    }
};
