<?php

namespace App\Models;

use App\Enums\TreatmentPlanRevisionStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreatmentPlanRevision extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'treatment_plan_id',
        'revision_number',
        'status',
        'currency',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'payment_terms',
        'patient_notes',
        'internal_change_reason',
        'valid_until',
        'content_checksum',
        'created_by',
        'approved_by',
        'approved_at',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TreatmentPlanRevisionStatus::class,
            'revision_number' => 'integer',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'valid_until' => 'date',
            'approved_at' => 'datetime',
            'issued_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class, 'treatment_plan_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TreatmentPlanRevisionItem::class, 'treatment_plan_revision_id');
    }
}
