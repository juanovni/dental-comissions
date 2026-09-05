<?php

namespace App\Models;

use App\Enums\ClearanceRequirementType;
use App\Enums\ConsentScope;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentPlanItemClearanceRequirement extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'treatment_plan_item_id',
        'requirement_type',
        'description',
        'scope',
        'status',
        'satisfied_by_decision_id',
        'waiver_reason',
        'waived_by',
        'waived_at',
    ];

    protected function casts(): array
    {
        return [
            'scope' => ConsentScope::class,
            'waived_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanItem::class, 'treatment_plan_item_id');
    }

    public function satisfiedByDecision(): BelongsTo
    {
        return $this->belongsTo(MedicalClearanceDecision::class, 'satisfied_by_decision_id');
    }

    public function waivedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'waived_by');
    }

    public function isSatisfied(): bool
    {
        if ($this->status === 'waived') {
            return true;
        }

        if ($this->status !== 'active') {
            return false;
        }

        $decision = $this->satisfiedByDecision;

        if (! $decision) {
            return false;
        }

        return $decision->isCurrentlyValid() && $decision->isFullySatisfied();
    }
}
