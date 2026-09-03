<?php

namespace App\Models;

use App\Enums\CarePath;
use App\Enums\EncounterStatus;
use App\Enums\EncounterType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicalEncounter extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'clinical_case_id',
        'appointment_id',
        'professional_id',
        'encounter_type',
        'care_path',
        'status',
        'occurred_at',
        'subjective_notes',
        'objective_findings',
        'assessment',
        'plan_notes',
        'signed_by',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'encounter_type' => EncounterType::class,
            'care_path' => CarePath::class,
            'status' => EncounterStatus::class,
            'occurred_at' => 'datetime',
            'signed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function clinicalCase(): BelongsTo
    {
        return $this->belongsTo(ClinicalCase::class, 'clinical_case_id');
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'professional_id');
    }

    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'signed_by');
    }

    public function performedProcedures(): HasMany
    {
        return $this->hasMany(PerformedProcedure::class, 'clinical_encounter_id');
    }

    public function amendments(): HasMany
    {
        return $this->hasMany(ClinicalRecordAmendment::class, 'clinical_encounter_id');
    }

    public function isSigned(): bool
    {
        return $this->status === EncounterStatus::Signed;
    }

    public function canBeEdited(): bool
    {
        return $this->status === EncounterStatus::Draft;
    }
}
