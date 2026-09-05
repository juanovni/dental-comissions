<?php

namespace App\Models;

use App\Enums\PatientRiskLevel;
use App\Enums\RecallPolicyStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecallPolicyVersion extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'recall_type_id',
        'version_number',
        'risk_level',
        'interval_months',
        'notes',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'interval_months' => 'integer',
            'risk_level' => PatientRiskLevel::class,
            'status' => RecallPolicyStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function recallType(): BelongsTo
    {
        return $this->belongsTo(RecallType::class, 'recall_type_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }
}
