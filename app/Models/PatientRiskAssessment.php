<?php

namespace App\Models;

use App\Enums\PatientRiskLevel;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientRiskAssessment extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'domain',
        'risk_level',
        'factors',
        'evidence',
        'assessed_by',
        'assessed_at',
        'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'risk_level' => PatientRiskLevel::class,
            'factors' => 'array',
            'assessed_at' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'assessed_by');
    }

    public function recallPlans(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PatientRecallPlan::class, 'risk_assessment_id');
    }

    public function isValid(): bool
    {
        if ($this->valid_until && $this->valid_until->isBefore(now())) {
            return false;
        }

        return true;
    }
}
