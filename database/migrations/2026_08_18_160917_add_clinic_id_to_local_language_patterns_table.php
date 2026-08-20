<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('local_language_patterns', function (Blueprint $table): void {
            $table->foreignId('clinic_id')->nullable()->constrained('clinics')->nullOnDelete()->after('id');
        });

        Schema::table('local_language_patterns', function (Blueprint $table): void {
            $table->dropUnique('local_language_patterns_unique_phrase');
        });

        Schema::table('local_language_patterns', function (Blueprint $table): void {
            $table->unique(['clinic_id', 'type', 'normalized_phrase', 'locale'], 'local_language_patterns_unique_phrase');
        });
    }

    public function down(): void
    {
        Schema::table('local_language_patterns', function (Blueprint $table): void {
            $table->dropUnique('local_language_patterns_unique_phrase');
        });

        Schema::table('local_language_patterns', function (Blueprint $table): void {
            $table->unique(['type', 'normalized_phrase', 'locale'], 'local_language_patterns_unique_phrase');
        });

        Schema::table('local_language_patterns', function (Blueprint $table): void {
            $table->dropForeign(['clinic_id']);
            $table->dropColumn('clinic_id');
        });
    }
};