<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_events', function (Blueprint $table): void {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['clinic_id', 'appointment_id']);
        });

        Schema::table('appointment_notes', function (Blueprint $table): void {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['clinic_id', 'appointment_id']);
            $table->index(['clinic_id', 'patient_id']);
        });

        Schema::table('appointment_reminders', function (Blueprint $table): void {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['clinic_id', 'appointment_id']);
            $table->index(['clinic_id', 'patient_id']);
        });

        Schema::table('appointment_check_in_attempts', function (Blueprint $table): void {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['clinic_id', 'appointment_id']);
            $table->index(['clinic_id', 'clinic_slug']);
        });

        Schema::table('appointment_slot_offers', function (Blueprint $table): void {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['clinic_id', 'social_comment_id']);
            $table->index(['clinic_id', 'appointment_id']);
        });

        Schema::table('appointment_slot_holds', function (Blueprint $table): void {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['clinic_id', 'appointment_slot_offer_id']);
            $table->index(['clinic_id', 'appointment_id']);
            $table->index(['clinic_id', 'doctor_id']);
        });

        Schema::table('doctor_assistant_assignments', function (Blueprint $table): void {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->dropUnique('doctor_assistant_assignments_doctor_id_assistant_id_unique');
            $table->unique(['clinic_id', 'doctor_id', 'assistant_id']);
            $table->index(['clinic_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('doctor_assistant_assignments', function (Blueprint $table): void {
            $table->dropUnique(['clinic_id', 'doctor_id', 'assistant_id']);
            $table->dropIndex(['clinic_id', 'is_active']);
            $table->dropConstrainedForeignId('clinic_id');
            $table->unique(['doctor_id', 'assistant_id']);
        });

        Schema::table('appointment_slot_holds', function (Blueprint $table): void {
            $table->dropIndex(['clinic_id', 'appointment_slot_offer_id']);
            $table->dropIndex(['clinic_id', 'appointment_id']);
            $table->dropIndex(['clinic_id', 'doctor_id']);
            $table->dropConstrainedForeignId('clinic_id');
        });

        Schema::table('appointment_slot_offers', function (Blueprint $table): void {
            $table->dropIndex(['clinic_id', 'social_comment_id']);
            $table->dropIndex(['clinic_id', 'appointment_id']);
            $table->dropConstrainedForeignId('clinic_id');
        });

        Schema::table('appointment_check_in_attempts', function (Blueprint $table): void {
            $table->dropIndex(['clinic_id', 'appointment_id']);
            $table->dropIndex(['clinic_id', 'clinic_slug']);
            $table->dropConstrainedForeignId('clinic_id');
        });

        Schema::table('appointment_reminders', function (Blueprint $table): void {
            $table->dropIndex(['clinic_id', 'appointment_id']);
            $table->dropIndex(['clinic_id', 'patient_id']);
            $table->dropConstrainedForeignId('clinic_id');
        });

        Schema::table('appointment_notes', function (Blueprint $table): void {
            $table->dropIndex(['clinic_id', 'appointment_id']);
            $table->dropIndex(['clinic_id', 'patient_id']);
            $table->dropConstrainedForeignId('clinic_id');
        });

        Schema::table('appointment_events', function (Blueprint $table): void {
            $table->dropIndex(['clinic_id', 'appointment_id']);
            $table->dropConstrainedForeignId('clinic_id');
        });
    }
};
