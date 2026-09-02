<?php

namespace App\Models;

use App\Enums\ReadinessMoment;
use App\Enums\ReadinessStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalReadinessEvaluation extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'treatment_plan_item_id',
        'moment',
        'result',
        'evidence',
        'blocking_reasons',
        'evaluated_by',
        'evaluated_at',
    ];

    protected function casts(): array
    {
        return [
            'moment' => ReadinessMoment::class,
            'result' => ReadinessStatus::class,
            'evidence' => 'array',
            'blocking_reasons' => 'array',
            'evaluated_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function evaluatedBy(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'evaluated_by');
    }
}
