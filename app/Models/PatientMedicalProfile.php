<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PatientMedicalProfile extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'current_version_id',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(PatientMedicalProfileVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PatientMedicalProfileVersion::class, 'patient_medical_profile_id');
    }

    public function allergies(): HasMany
    {
        return $this->hasMany(PatientAllergy::class);
    }

    public function medications(): HasMany
    {
        return $this->hasMany(PatientMedication::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(PatientMedicalCondition::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(MedicalProfileReview::class);
    }
}
