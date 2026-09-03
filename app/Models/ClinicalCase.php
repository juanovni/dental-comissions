<?php

namespace App\Models;

use App\Enums\ClinicalCaseStatus;
use App\Enums\ClinicalCaseType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicalCase extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'treatment_plan_item_id',
        'source_appointment_id',
        'responsible_professional_id',
        'specialty_id',
        'case_type',
        'status',
        'title',
        'diagnosis_summary',
        'started_at',
        'completed_at',
        'closed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'case_type' => ClinicalCaseType::class,
            'status' => ClinicalCaseStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function responsibleProfessional(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'responsible_professional_id');
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function encounters(): HasMany
    {
        return $this->hasMany(ClinicalEncounter::class, 'clinical_case_id');
    }

    public function performedProcedures(): HasMany
    {
        return $this->hasMany(PerformedProcedure::class);
    }
}
