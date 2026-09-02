<?php

namespace App\Models;

use App\Enums\ClinicalPriority;
use App\Enums\TreatmentPlanItemClinicalStatus;
use App\Enums\TreatmentPlanItemCommercialStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreatmentPlanItem extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'treatment_plan_id',
        'procedure_id',
        'phase_id',
        'alternative_group_id',
        'tooth',
        'surface',
        'quantity',
        'duration_minutes',
        'sessions',
        'specialty_id',
        'suggested_professional_id',
        'clinical_priority',
        'sequence_order',
        'readiness_status',
        'clinical_hold_reason',
        'recommended_from',
        'recommended_until',
        'commercial_status',
        'clinical_status',
        'rejection_reason',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'duration_minutes' => 'integer',
            'sessions' => 'integer',
            'sequence_order' => 'integer',
            'display_order' => 'integer',
            'clinical_priority' => ClinicalPriority::class,
            'commercial_status' => TreatmentPlanItemCommercialStatus::class,
            'clinical_status' => TreatmentPlanItemClinicalStatus::class,
            'recommended_from' => 'date',
            'recommended_until' => 'date',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class, 'treatment_plan_id');
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanPhase::class, 'phase_id');
    }

    public function alternativeGroup(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanAlternativeGroup::class, 'alternative_group_id');
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function suggestedProfessional(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'suggested_professional_id');
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(TreatmentPlanItemDependency::class, 'treatment_plan_item_id');
    }

    public function dependentOn(): HasMany
    {
        return $this->hasMany(TreatmentPlanItemDependency::class, 'depends_on_item_id');
    }
}
