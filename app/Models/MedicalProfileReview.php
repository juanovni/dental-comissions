<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalProfileReview extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'version_id',
        'reviewed_by',
        'outcome',
        'acknowledged_alerts',
        'changes_noted',
        'notes',
        'next_review_at',
    ];

    protected function casts(): array
    {
        return [
            'acknowledged_alerts' => 'array',
            'changes_noted' => 'array',
            'next_review_at' => 'datetime',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(PatientMedicalProfileVersion::class, 'version_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'reviewed_by');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
