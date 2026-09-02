<?php

namespace App\Models;

use App\Enums\OdontogramEntryStatus;
use App\Enums\OdontogramEntryType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OdontogramEntry extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'odontogram_id',
        'patient_id',
        'clinical_encounter_id',
        'treatment_plan_item_id',
        'performed_procedure_id',
        'tooth_code',
        'entry_type',
        'condition_code',
        'status',
        'severity',
        'diagnosis_text',
        'observations',
        'recorded_by',
        'recorded_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'entry_type' => OdontogramEntryType::class,
            'status' => OdontogramEntryStatus::class,
            'recorded_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function odontogram(): BelongsTo
    {
        return $this->belongsTo(Odontogram::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'recorded_by');
    }

    public function surfaces(): HasMany
    {
        return $this->hasMany(OdontogramEntrySurface::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(OdontogramEntryTarget::class);
    }
}
