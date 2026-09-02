<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalSafetyRuleSetVersion extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'clinical_safety_rule_set_id',
        'version_number',
        'rules_snapshot',
        'approved_by',
        'approved_at',
        'valid_from',
        'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'rules_snapshot' => 'array',
            'approved_at' => 'datetime',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function ruleSet(): BelongsTo
    {
        return $this->belongsTo(ClinicalSafetyRuleSet::class, 'clinical_safety_rule_set_id');
    }
}
