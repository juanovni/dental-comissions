<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('identity_status')->nullable()->after('id'); // IdentityStatus enum
            $table->foreignId('current_identity_version_id')->nullable()->after('identity_status'); // FK a medical_profile_versions
            $table->boolean('medical_profile_complete')->default(false);
            $table->date('profile_last_verified_at')->nullable();
            $table->date('next_recall_due_at')->nullable();
            $table->string('recall_interval_months')->nullable();
            $table->text('recall_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'identity_status',
                'current_identity_version_id',
                'medical_profile_complete',
                'profile_last_verified_at',
                'next_recall_due_at',
                'recall_interval_months',
                'recall_notes',
            ]);
        });
    }
};
