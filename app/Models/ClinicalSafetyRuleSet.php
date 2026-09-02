<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ClinicalSafetyRuleSet extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'name',
        'description',
        'source',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function activeVersion(): HasOne
    {
        return $this->hasOne(ClinicalSafetyRuleSetVersion::class)
            ->where('approved_at', '!=', null)
            ->latestOfMany('version_number');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ClinicalSafetyRuleSetVersion::class, 'clinical_safety_rule_set_id');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(ClinicalSafetyRule::class, 'clinical_safety_rule_set_id');
    }

    public function activeRules(): HasMany
    {
        return $this->rules()->where('is_active', true)->orderBy('sort_order');
    }
}
