<?php

namespace App\Models;

use App\Enums\ConsentTemplateStatus;
use App\Enums\ConsentTemplateType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InformedConsentTemplate extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'name',
        'description',
        'type',
        'is_active',
        'current_version_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => ConsentTemplateType::class,
            'is_active' => 'boolean',
        ];
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(InformedConsentTemplateVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(InformedConsentTemplateVersion::class, 'template_id');
    }

    public function activeVersion(): HasOne
    {
        return $this->hasOne(InformedConsentTemplateVersion::class)
            ->where('status', ConsentTemplateStatus::active)
            ->latestOfMany('version_number');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(TreatmentPlanItemConsentRequirement::class, 'template_id');
    }
}
