<?php

namespace App\Models;

use App\Enums\UrgentCareArrivalMode;
use App\Enums\UrgentCareDisposition;
use App\Enums\UrgentCareTriageCategory;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UrgentCareIntake extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'clinical_encounter_id',
        'professional_id',
        'arrival_mode',
        'triage_category',
        'chief_complaint',
        'red_flags',
        'acute_medical_screen_status',
        'disposition',
        'disposition_notes',
        'deferred_completion_due_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'arrival_mode' => UrgentCareArrivalMode::class,
            'triage_category' => UrgentCareTriageCategory::class,
            'disposition' => UrgentCareDisposition::class,
            'deferred_completion_due_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function clinicalEncounter(): BelongsTo
    {
        return $this->belongsTo(ClinicalEncounter::class, 'clinical_encounter_id');
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'professional_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
