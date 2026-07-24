<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role');
            $table->string('permission');
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();

            $table->unique(['role', 'permission']);
            $table->index(['role', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
