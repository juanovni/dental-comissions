<?php

namespace App\Models;

use App\Enums\PhaseStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreatmentPlanPhase extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'treatment_plan_id',
        'name',
        'sequence_order',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => PhaseStatus::class,
            'sequence_order' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class, 'treatment_plan_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TreatmentPlanItem::class, 'phase_id');
    }
}
