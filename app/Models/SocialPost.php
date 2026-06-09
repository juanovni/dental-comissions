<?php

namespace App\Models;

use App\Enums\SocialPlatform;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'social_account_id',
        'platform',
        'external_post_id',
        'caption',
        'media_url',
        'permalink',
        'raw_payload',
        'published_at',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'platform' => SocialPlatform::class,
            'raw_payload' => 'array',
            'published_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SocialComment::class);
    }
}
