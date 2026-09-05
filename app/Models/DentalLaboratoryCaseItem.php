<?php

namespace App\Models;

use App\Enums\LaboratoryCaseItemStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DentalLaboratoryCaseItem extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'case_id',
        'treatment_plan_item_id',
        'procedure_id',
        'tooth',
        'work_type',
        'material',
        'shade',
        'specifications',
        'quantity',
        'status',
        'quality_notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'status' => LaboratoryCaseItemStatus::class,
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(DentalLaboratoryCase::class, 'case_id');
    }

    public function treatmentPlanItem(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanItem::class, 'treatment_plan_item_id');
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class, 'procedure_id');
    }
}
