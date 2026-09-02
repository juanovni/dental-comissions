<?php

namespace App\Models;

use App\Enums\DependencyType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentPlanItemDependency extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'treatment_plan_item_id',
        'depends_on_item_id',
        'dependency_type',
        'minimum_interval_days',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'dependency_type' => DependencyType::class,
            'minimum_interval_days' => 'integer',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanItem::class, 'treatment_plan_item_id');
    }

    public function dependsOnItem(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanItem::class, 'depends_on_item_id');
    }
}
