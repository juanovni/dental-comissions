<?php

namespace App\Models;

use App\Enums\MedicalClearanceDecision;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalClearanceDecision extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'consultation_id',
        'reviewed_by_professional_id',
        'decision',
        'clinical_summary',
        'conditions_summary',
        'scope',
        'valid_from',
        'valid_until',
        'issued_by_professional_id',
        'issuer_name',
        'document_reference',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'decision' => MedicalClearanceDecision::class,
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(ExternalMedicalConsultation::class, 'consultation_id');
    }

    public function reviewedByProfessional(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'reviewed_by_professional_id');
    }

    public function issuedByProfessional(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'issued_by_professional_id');
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(MedicalClearanceCondition::class, 'decision_id');
    }

    public function clearanceRequirements(): HasMany
    {
        return $this->hasMany(TreatmentPlanItemClearanceRequirement::class, 'satisfied_by_decision_id');
    }

    public function isCurrentlyValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now();

        if ($this->valid_from && $this->valid_from->isAfter($now)) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->isBefore($now)) {
            return false;
        }

        return true;
    }

    public function isFullySatisfied(): bool
    {
        if ($this->decision !== MedicalClearanceDecision::cleared_with_conditions) {
            return $this->decision === MedicalClearanceDecision::cleared;
        }

        $mandatoryConditions = $this->conditions()->where('is_mandatory', true)->get();

        if ($mandatoryConditions->isEmpty()) {
            return true;
        }

        return $mandatoryConditions->every(fn (MedicalClearanceCondition $condition) => $condition->status === 'verified');
    }
}
