<?php

namespace App\Models;

use App\Enums\SocialPlatform;
use App\Enums\SocialPriority;
use App\Enums\SocialSuggestedAction;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialModerationRule extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'clinic_id',
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

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
