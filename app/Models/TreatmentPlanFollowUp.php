<?php

namespace App\Models;

use App\Enums\FollowUpChannel;
use App\Enums\FollowUpOutcome;
use App\Enums\FollowUpStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentPlanFollowUp extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'treatment_plan_id',
        'treatment_plan_item_id',
        'channel',
        'reason',
        'status',
        'scheduled_at',
        'performed_at',
        'performed_by',
        'outcome',
        'notes',
        'next_action_at',
        'external_reference',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'channel' => FollowUpChannel::class,
            'status' => FollowUpStatus::class,
            'outcome' => FollowUpOutcome::class,
            'scheduled_at' => 'datetime',
            'performed_at' => 'datetime',
            'next_action_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class, 'treatment_plan_id');
    }

    public function treatmentPlanItem(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanItem::class, 'treatment_plan_item_id');
    }

    public function performedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'performed_by');
    }
}
