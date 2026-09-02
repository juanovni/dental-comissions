<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_safety_rule_set_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinical_safety_rule_set_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('rules_snapshot')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();

            $table->unique(['clinical_safety_rule_set_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_safety_rule_set_versions');
    }
};
