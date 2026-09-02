<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentPlanEvent extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'treatment_plan_id',
        'treatment_plan_revision_id',
        'treatment_plan_item_id',
        'event_type',
        'from_status',
        'to_status',
        'occurred_at',
        'created_by',
        'professional_id',
        'source',
        'notes',
        'metadata',
        'request_id',
        'ip_hash',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class, 'treatment_plan_id');
    }
}
