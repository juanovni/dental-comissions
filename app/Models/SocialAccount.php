<?php

namespace App\Models;

use App\Enums\SocialPlatform;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform',
        'account_name',
        'external_account_id',
        'page_id',
        'instagram_business_account_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_active',
        'last_synced_at',
        'sync_settings',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'platform' => SocialPlatform::class,
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
            'sync_settings' => 'array',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SocialComment::class);
    }
}
