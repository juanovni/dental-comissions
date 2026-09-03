<?php

namespace App\Models;

use App\Enums\ConsentTemplateStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InformedConsentTemplateVersion extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'template_id',
        'version_number',
        'language',
        'title',
        'content_body',
        'risks',
        'alternatives',
        'post_procedure_instructions',
        'patient_rights',
        'effective_from',
        'effective_until',
        'content_checksum',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'effective_from' => 'date',
            'effective_until' => 'date',
            'approved_at' => 'datetime',
            'status' => ConsentTemplateStatus::class,
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(InformedConsentTemplate::class, 'template_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function consents(): HasMany
    {
        return $this->hasMany(TreatmentPlanItemConsent::class, 'template_version_id');
    }

    public function isCurrentlyValid(): bool
    {
        $now = now();

        if ($this->effective_from && $this->effective_from->isAfter($now)) {
            return false;
        }

        if ($this->effective_until && $this->effective_until->isBefore($now)) {
            return false;
        }

        return $this->status === ConsentTemplateStatus::active;
    }
}
