<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_safety_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinical_safety_rule_set_id')->constrained()->cascadeOnDelete();
            $table->string('rule_name');
            $table->text('description')->nullable();
            // Condicion que activa la regla
            $table->string('trigger_type'); // allergy, medication, condition, procedure, combination
            $table->json('trigger_conditions'); // sustancia, medicamento, condicion, procedimiento
            // Accion resultante
            $table->string('action_level'); // ClinicalAlertLevel: advisory, conditional_hold, hard_stop
            $table->text('action_message')->nullable();
            $table->text('action_recommendation')->nullable();
            // Metadatos
            $table->string('source')->nullable(); // manual, regional_pack, evidence_based
            $table->text('evidence_source')->nullable(); // referencia bibliografica
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['clinic_id', 'clinical_safety_rule_set_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_safety_rules');
    }
};
