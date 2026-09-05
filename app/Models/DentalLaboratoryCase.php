<?php

namespace App\Models;

use App\Enums\LaboratoryCaseStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DentalLaboratoryCase extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'treatment_plan_item_id',
        'professional_id',
        'laboratory_id',
        'laboratory_name',
        'status',
        'priority',
        'promised_date',
        'received_date',
        'ready_date',
        'clinical_notes',
        'lab_instructions',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => LaboratoryCaseStatus::class,
            'promised_date' => 'date',
            'received_date' => 'date',
            'ready_date' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function treatmentPlanItem(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanItem::class, 'treatment_plan_item_id');
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'professional_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DentalLaboratoryCaseItem::class, 'case_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(DentalLaboratoryEvent::class, 'case_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DentalLaboratoryDocument::class, 'case_id');
    }
}
