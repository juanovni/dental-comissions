<?php

namespace App\Models;

use App\Enums\ClinicalAlertLevel;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalSafetyRuleApplication extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'clinical_safety_rule_id',
        'patient_id',
        'treatment_plan_item_id',
        'evaluation_id',
        'result',
        'resulting_level',
        'evidence',
        'context',
        'was_overridden',
        'override_reason',
        'overridden_by_professional_id',
        'overridden_at',
    ];

    protected function casts(): array
    {
        return [
            'resulting_level' => ClinicalAlertLevel::class,
            'was_overridden' => 'boolean',
            'overridden_at' => 'datetime',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ClinicalSafetyRule::class, 'clinical_safety_rule_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function overriddenBy(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'overridden_by_professional_id');
    }
}
