<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialLinkEvent extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'social_comment_id',
        'event_type',
        'session_id',
        'duration_seconds',
        'ip_hash',
        'user_agent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function socialComment(): BelongsTo
    {
        return $this->belongsTo(SocialComment::class);
    }
}
