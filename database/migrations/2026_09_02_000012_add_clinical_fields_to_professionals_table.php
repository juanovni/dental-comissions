<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professionals', function (Blueprint $table) {
            $table->foreignId('specialty_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('license_number')->nullable()->after('specialty_id');
            $table->text('digital_signature')->nullable();
            $table->boolean('can_approve_plans')->default(false);
            $table->boolean('can_sign_consents')->default(false);
            $table->boolean('can_order_emergency')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('professionals', function (Blueprint $table) {
            $table->dropColumn([
                'specialty_id',
                'license_number',
                'digital_signature',
                'can_approve_plans',
                'can_sign_consents',
                'can_order_emergency',
            ]);
        });
    }
};
