<?php

namespace App\Models;

use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialIdentity extends Model
{
    use HasFactory;

    protected $fillable = [
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

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SocialComment::class);
    }

    public function activityRecords(): HasMany
    {
        return $this->hasMany(ActivityRecord::class);
    }
}
