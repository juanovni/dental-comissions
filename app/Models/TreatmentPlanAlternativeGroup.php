<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreatmentPlanAlternativeGroup extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'treatment_plan_id',
        'name',
        'notes',
        'is_mutually_exclusive',
    ];

    protected function casts(): array
    {
        return [
            'is_mutually_exclusive' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class, 'treatment_plan_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TreatmentPlanItem::class, 'alternative_group_id');
    }
}
