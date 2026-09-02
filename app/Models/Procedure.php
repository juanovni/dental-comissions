<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Procedure extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'name',
        'code',
        'category',
        'internal_rate',
        'is_active',
        'notes',
        'commercial_price',
        'estimated_duration_minutes',
        'specialty_id',
        'requires_lab',
        'requires_consent',
        'is_provider_dependent',
        'traceable',
        'has_multiple_units',
        'risk_sensitive',
        'default_body_region',
        'default_tooth_number',
    ];

    protected function casts(): array
    {
        return [
            'internal_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'commercial_price' => 'decimal:2',
            'estimated_duration_minutes' => 'integer',
            'requires_lab' => 'boolean',
            'requires_consent' => 'boolean',
            'is_provider_dependent' => 'boolean',
            'traceable' => 'boolean',
            'has_multiple_units' => 'boolean',
            'risk_sensitive' => 'boolean',
        ];
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    public function suggestedSocialComments(): HasMany
    {
        return $this->hasMany(SocialComment::class, 'suggested_procedure_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
