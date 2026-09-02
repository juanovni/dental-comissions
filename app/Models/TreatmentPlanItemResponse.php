<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentPlanItemResponse extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'treatment_plan_id',
        'treatment_plan_revision_id',
        'treatment_plan_revision_item_id',
        'treatment_plan_public_link_id',
        'response',
        'responded_at',
        'source',
        'notes',
        'ip_hash',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class, 'treatment_plan_id');
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanRevision::class, 'treatment_plan_revision_id');
    }

    public function revisionItem(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanRevisionItem::class, 'treatment_plan_revision_item_id');
    }
}
