<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentPlanPublicLink extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'treatment_plan_id',
        'treatment_plan_revision_id',
        'token_hash',
        'token_prefix',
        'purpose',
        'status',
        'expires_at',
        'revoked_at',
        'first_viewed_at',
        'last_viewed_at',
        'accepted_at',
        'recipient_phone_hash',
        'recipient_email_hash',
        'metadata',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'first_viewed_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'metadata' => 'array',
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
}
