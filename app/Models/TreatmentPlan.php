<?php

namespace App\Models;

use App\Enums\TreatmentPlanClinicalStatus;
use App\Enums\TreatmentPlanCommercialStatus;
use App\Enums\TreatmentPlanLifecycleStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TreatmentPlan extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'responsible_professional_id',
        'source_appointment_id',
        'current_revision_id',
        'code',
        'title',
        'diagnosis_summary',
        'clinical_priority',
        'lifecycle_status',
        'commercial_status',
        'clinical_status',
        'currency',
        'valid_until',
        'created_by',
        'approved_by',
        'sent_at',
        'accepted_at',
        'rejected_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'lifecycle_status' => TreatmentPlanLifecycleStatus::class,
            'commercial_status' => TreatmentPlanCommercialStatus::class,
            'clinical_status' => TreatmentPlanClinicalStatus::class,
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function currentRevision(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanRevision::class, 'current_revision_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(TreatmentPlanRevision::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TreatmentPlanItem::class);
    }

    public function phases(): HasMany
    {
        return $this->hasMany(TreatmentPlanPhase::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TreatmentPlanEvent::class);
    }

    public function publicLinks(): HasMany
    {
        return $this->hasMany(TreatmentPlanPublicLink::class);
    }
}
