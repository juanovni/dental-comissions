<?php

namespace App\Models;

use App\Enums\IdentityStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Patient extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'full_name',
        'normalized_name',
        'phone',
        'date_of_birth',
        'notes',
        'identity_status',
        'current_identity_version_id',
        'medical_profile_complete',
        'profile_last_verified_at',
        'next_recall_due_at',
        'recall_interval_months',
        'recall_notes',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'identity_status' => IdentityStatus::class,
            'medical_profile_complete' => 'boolean',
            'profile_last_verified_at' => 'date',
            'next_recall_due_at' => 'date',
        ];
    }

    public function medicalProfile(): HasOne
    {
        return $this->hasOne(PatientMedicalProfile::class);
    }

    public function clinicalAlerts(): HasMany
    {
        return $this->hasMany(PatientClinicalAlert::class);
    }

    public function activeAlerts(): HasMany
    {
        return $this->clinicalAlerts()->where('status', 'active');
    }

    public function currentIdentityVersion(): BelongsTo
    {
        return $this->belongsTo(PatientMedicalProfileVersion::class, 'current_identity_version_id');
    }

    public function socialIdentities(): HasMany
    {
        return $this->hasMany(SocialIdentity::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function convertedSocialComments(): HasMany
    {
        return $this->hasMany(SocialComment::class, 'converted_patient_id');
    }
}
