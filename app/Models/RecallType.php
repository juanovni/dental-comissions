<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecallType extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function policyVersions(): HasMany
    {
        return $this->hasMany(RecallPolicyVersion::class, 'recall_type_id');
    }

    public function activePolicyVersion(): HasMany
    {
        return $this->policyVersions()->where('status', 'active')->latestOfMany('version_number');
    }

    public function recallPlans(): HasMany
    {
        return $this->hasMany(PatientRecallPlan::class, 'recall_type_id');
    }
}
