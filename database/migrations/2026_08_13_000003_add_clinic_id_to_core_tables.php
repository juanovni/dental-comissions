<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table): void {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['clinic_id', 'normalized_name']);
            $table->index(['clinic_id', 'phone']);
        });

        Schema::table('professionals', function (Blueprint $table): void {
            $table->dropUnique('professionals_whatsapp_phone_unique');
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unique(['clinic_id', 'whatsapp_phone']);
            $table->index(['clinic_id', 'role', 'is_active']);
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['clinic_id', 'scheduled_at']);
            $table->index(['clinic_id', 'status']);
        });

        Schema::table('procedures', function (Blueprint $table): void {
            $table->dropUnique('procedures_name_unique');
            $table->dropUnique('procedures_code_unique');
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unique(['clinic_id', 'name']);
            $table->unique(['clinic_id', 'code']);
            $table->index(['clinic_id', 'category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('procedures', function (Blueprint $table): void {
            $table->dropUnique(['clinic_id', 'name']);
            $table->dropUnique(['clinic_id', 'code']);
            $table->dropIndex(['clinic_id', 'category', 'is_active']);
            $table->dropConstrainedForeignId('clinic_id');
            $table->unique('name');
            $table->unique('code');
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex(['clinic_id', 'scheduled_at']);
            $table->dropIndex(['clinic_id', 'status']);
            $table->dropConstrainedForeignId('clinic_id');
        });

        Schema::table('professionals', function (Blueprint $table): void {
            $table->dropUnique(['clinic_id', 'whatsapp_phone']);
            $table->dropIndex(['clinic_id', 'role', 'is_active']);
            $table->dropConstrainedForeignId('clinic_id');
            $table->unique('whatsapp_phone');
        });

        Schema::table('patients', function (Blueprint $table): void {
            $table->dropIndex(['clinic_id', 'normalized_name']);
            $table->dropIndex(['clinic_id', 'phone']);
            $table->dropConstrainedForeignId('clinic_id');
        });
    }
};
