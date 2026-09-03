<?php

namespace App\Models;

use App\Enums\PerformedProcedureStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformedProcedure extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'clinical_encounter_id',
        'appointment_id',
        'treatment_plan_item_id',
        'procedure_id',
        'procedure_name_snapshot',
        'procedure_code_snapshot',
        'tooth',
        'surface',
        'quantity',
        'status',
        'performed_by',
        'started_at',
        'completed_at',
        'clinical_notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'status' => PerformedProcedureStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(ClinicalEncounter::class, 'clinical_encounter_id');
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'performed_by');
    }

    public function clinicalCase(): BelongsTo
    {
        return $this->belongsTo(ClinicalCase::class);
    }
}
