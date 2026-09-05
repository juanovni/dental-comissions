<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientRecallEvent extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'recall_plan_id',
        'event_type',
        'previous_date',
        'new_date',
        'professional_id',
        'reason',
        'source',
        'policy_version_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'previous_date' => 'date',
            'new_date' => 'date',
            'metadata' => 'array',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function recallPlan(): BelongsTo
    {
        return $this->belongsTo(PatientRecallPlan::class, 'recall_plan_id');
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'professional_id');
    }

    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(RecallPolicyVersion::class, 'policy_version_id');
    }
}
