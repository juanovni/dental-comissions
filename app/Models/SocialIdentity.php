<?php

namespace App\Models;

use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialIdentity extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'platform',
        'platform_user_id',
        'username',
        'display_name',
        'phone',
        'normalized_phone',
        'status',
        'linked_at',
        'first_seen_at',
        'last_seen_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'platform' => SocialPlatform::class,
            'status' => SocialIdentityStatus::class,
            'linked_at' => 'datetime',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function patient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SocialComment::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
