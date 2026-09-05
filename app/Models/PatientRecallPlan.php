<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientRecallPlan extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'recall_type_id',
        'risk_assessment_id',
        'policy_version_id',
        'last_encounter_id',
        'last_procedure_id',
        'calculated_date',
        'adjusted_date',
        'adjustment_reason',
        'adjusted_by',
        'related_appointment_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'calculated_date' => 'date',
            'adjusted_date' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function recallType(): BelongsTo
    {
        return $this->belongsTo(RecallType::class, 'recall_type_id');
    }

    public function riskAssessment(): BelongsTo
    {
        return $this->belongsTo(PatientRiskAssessment::class, 'risk_assessment_id');
    }

    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(RecallPolicyVersion::class, 'policy_version_id');
    }

    public function lastEncounter(): BelongsTo
    {
        return $this->belongsTo(ClinicalEncounter::class, 'last_encounter_id');
    }

    public function lastProcedure(): BelongsTo
    {
        return $this->belongsTo(PerformedProcedure::class, 'last_procedure_id');
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'adjusted_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(PatientRecallEvent::class, 'recall_plan_id');
    }

    public function effectiveDate(): \Carbon\Carbon
    {
        return $this->adjusted_date ?? $this->calculated_date;
    }

    public function isOverdue(): bool
    {
        return $this->effectiveDate()->isBefore(now()) && $this->status === 'active';
    }
}
