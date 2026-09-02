<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procedures', function (Blueprint $table) {
            $table->decimal('commercial_price', 12, 2)->nullable()->after('id');
            $table->unsignedInteger('estimated_duration_minutes')->nullable()->after('commercial_price');
            $table->foreignId('specialty_id')->nullable()->after('estimated_duration_minutes')->constrained()->nullOnDelete();
            $table->boolean('requires_lab')->default(false);
            $table->boolean('requires_consent')->default(false);
            $table->boolean('is_provider_dependent')->default(false);
            $table->boolean('traceable')->default(false);
            $table->boolean('has_multiple_units')->default(false);
            $table->boolean('risk_sensitive')->default(false);
            $table->string('default_body_region')->nullable();
            $table->string('default_tooth_number')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('procedures', function (Blueprint $table) {
            $table->dropColumn([
                'commercial_price',
                'estimated_duration_minutes',
                'specialty_id',
                'requires_lab',
                'requires_consent',
                'is_provider_dependent',
                'traceable',
                'has_multiple_units',
                'risk_sensitive',
                'default_body_region',
                'default_tooth_number',
            ]);
        });
    }
};
