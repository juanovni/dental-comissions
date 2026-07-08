<?php

namespace App\Models;

use App\Services\SocialCrmSettingsService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialCrmSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'setting_group',
        'key',
        'label',
        'value_type',
        'value',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn (): mixed => app(SocialCrmSettingsService::class)->clearCache());
        static::deleted(fn (): mixed => app(SocialCrmSettingsService::class)->clearCache());
    }
}
