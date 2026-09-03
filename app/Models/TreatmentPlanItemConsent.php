<?php

namespace App\Models;

use App\Enums\ConsentDecisionStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreatmentPlanItemConsent extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'requirement_id',
        'template_version_id',
        'patient_id',
        'signer_id',
        'signer_type',
        'signer_name',
        'signer_relationship',
        'professional_id',
        'professional_who_explained',
        'status',
        'signed_at',
        'explanation_notes',
        'patient_questions',
        'rejection_reason',
        'signature_data',
        'signature_ip_hash',
        'signature_user_agent',
        'valid_from',
        'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'status' => ConsentDecisionStatus::class,
            'signed_at' => 'datetime',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanItemConsentRequirement::class, 'requirement_id');
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(InformedConsentTemplateVersion::class, 'template_version_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'signer_id');
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'professional_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(InformedConsentEvent::class, 'consent_id');
    }

    public function isActive(): bool
    {
        if ($this->status !== ConsentDecisionStatus::signed) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->isBefore(now())) {
            return false;
        }

        return true;
    }
}
