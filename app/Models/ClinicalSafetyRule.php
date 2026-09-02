<?php

namespace App\Models;

use App\Enums\ClinicalAlertLevel;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicalSafetyRule extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'clinical_safety_rule_set_id',
        'rule_name',
        'description',
        'trigger_type',
        'trigger_conditions',
        'action_level',
        'action_message',
        'action_recommendation',
        'source',
        'evidence_source',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'action_level' => ClinicalAlertLevel::class,
            'trigger_conditions' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function ruleSet(): BelongsTo
    {
        return $this->belongsTo(ClinicalSafetyRuleSet::class, 'clinical_safety_rule_set_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ClinicalSafetyRuleApplication::class, 'clinical_safety_rule_id');
    }
}
