<?php

namespace App\Models;

use App\Enums\ClinicalAlertLevel;
use App\Enums\ClinicalAlertSource;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientClinicalAlert extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'alert_name',
        'description',
        'level',
        'source',
        'scope',
        'context',
        'status',
        'created_by_professional_id',
        'resolved_by_professional_id',
        'resolved_at',
        'expires_at',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'level' => ClinicalAlertLevel::class,
            'source' => ClinicalAlertSource::class,
            'context' => 'array',
            'resolved_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'created_by_professional_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'resolved_by_professional_id');
    }
}
