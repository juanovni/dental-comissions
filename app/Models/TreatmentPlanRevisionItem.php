<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentPlanRevisionItem extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'treatment_plan_revision_id',
        'treatment_plan_item_id',
        'procedure_name_snapshot',
        'procedure_code_snapshot',
        'description_snapshot',
        'tooth_snapshot',
        'surface_snapshot',
        'quantity',
        'unit_price',
        'discount_type',
        'discount_value',
        'tax_rate',
        'subtotal',
        'total',
        'duration_minutes_snapshot',
        'sessions_snapshot',
        'clinical_priority_snapshot',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'duration_minutes_snapshot' => 'integer',
            'sessions_snapshot' => 'integer',
            'display_order' => 'integer',
        ];
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanRevision::class, 'treatment_plan_revision_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanItem::class, 'treatment_plan_item_id');
    }
}
