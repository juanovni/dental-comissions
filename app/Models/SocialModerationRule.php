<?php

namespace App\Models;

use App\Enums\SocialPlatform;
use App\Enums\SocialPriority;
use App\Enums\SocialSuggestedAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialModerationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'platform',
        'condition_type',
        'condition_value',
        'suggested_action',
        'priority',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'platform' => SocialPlatform::class,
            'suggested_action' => SocialSuggestedAction::class,
            'priority' => SocialPriority::class,
            'is_active' => 'boolean',
        ];
    }
}
