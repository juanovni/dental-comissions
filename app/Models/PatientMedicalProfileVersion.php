<?php

namespace App\Models;

use App\Enums\MedicalProfileVersionStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientMedicalProfileVersion extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'patient_medical_profile_id',
        'version_number',
        'status',
        'reporter',
        'allergies_snapshot',
        'medications_snapshot',
        'conditions_snapshot',
        'notes',
        'submitted_by',
        'reviewed_by',
        'submitted_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => MedicalProfileVersionStatus::class,
            'version_number' => 'integer',
            'allergies_snapshot' => 'array',
            'medications_snapshot' => 'array',
            'conditions_snapshot' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PatientMedicalProfile::class, 'patient_medical_profile_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(MedicalProfileReview::class, 'version_id');
    }
}
