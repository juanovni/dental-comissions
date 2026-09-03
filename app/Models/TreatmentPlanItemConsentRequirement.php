<?php

namespace App\Models;

use App\Enums\ConsentRequirementStatus;
use App\Enums\ConsentScope;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TreatmentPlanItemConsentRequirement extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'treatment_plan_item_id',
        'template_id',
        'is_mandatory',
        'scope',
        'status',
        'satisfied_by_consent_id',
        'waiver_reason',
        'waived_by',
        'waived_at',
    ];

    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
            'scope' => ConsentScope::class,
            'status' => ConsentRequirementStatus::class,
            'waived_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanItem::class, 'treatment_plan_item_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(InformedConsentTemplate::class, 'template_id');
    }

    public function satisfiedByConsent(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanItemConsent::class, 'satisfied_by_consent_id');
    }

    public function waivedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'waived_by');
    }

    public function consents(): HasMany
    {
        return $this->hasMany(TreatmentPlanItemConsent::class, 'requirement_id');
    }

    public function latestConsent(): HasOne
    {
        return $this->hasOne(TreatmentPlanItemConsent::class, 'requirement_id')
            ->latestOfMany('created_at');
    }

    public function isSatisfied(): bool
    {
        return $this->status === ConsentRequirementStatus::satisfied
            || $this->status === ConsentRequirementStatus::waived;
    }

    public function isExpired(): bool
    {
        if ($this->status !== ConsentRequirementStatus::active) {
            return false;
        }

        $consent = $this->latestConsent;

        if ($consent && $consent->valid_until && $consent->valid_until->isBefore(now())) {
            return true;
        }

        return false;
    }
}
