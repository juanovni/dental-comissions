<?php

namespace App\Models;

use App\Enums\SocialCommentClassification;
use App\Enums\SocialCommentStatus;
use App\Enums\SocialPlatform;
use App\Enums\SocialPriority;
use App\Enums\SocialReputationRisk;
use App\Enums\SocialResponseChannel;
use App\Enums\SocialSentiment;
use App\Enums\SocialSuggestedAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'social_account_id',
        'social_post_id',
        'parent_comment_id',
        'platform',
        'external_comment_id',
        'external_parent_comment_id',
        'author_name',
        'author_username',
        'author_external_id',
        'comment_text',
        'classification',
        'sentiment',
        'priority',
        'reputation_risk',
        'status',
        'suggested_action',
        'response_channel',
        'suggested_reply',
        'requires_human_review',
        'ai_reason',
        'ai_response',
        'is_hidden',
        'raw_payload',
        'published_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'platform' => SocialPlatform::class,
            'classification' => SocialCommentClassification::class,
            'sentiment' => SocialSentiment::class,
            'priority' => SocialPriority::class,
            'reputation_risk' => SocialReputationRisk::class,
            'status' => SocialCommentStatus::class,
            'suggested_action' => SocialSuggestedAction::class,
            'response_channel' => SocialResponseChannel::class,
            'requires_human_review' => 'boolean',
            'ai_response' => 'array',
            'is_hidden' => 'boolean',
            'raw_payload' => 'array',
            'published_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function socialPost(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class);
    }

    public function parentComment(): BelongsTo
    {
        return $this->belongsTo(SocialComment::class, 'parent_comment_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SocialComment::class, 'parent_comment_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(SocialCommentAction::class);
    }
}
