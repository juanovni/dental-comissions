<?php

namespace App\Models;

use App\Enums\SocialCommentClassification;
use App\Enums\SocialCommentStatus;
use App\Enums\SocialConversionStatus;
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
        'social_identity_id',
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
        'suggested_procedure_id',
        'tracking_token',
        'conversion_status',
        'interest_score',
        'hot_lead_at',
        'last_smart_link_visited_at',
        'reheated_at',
        'contacted_at',
        'follow_up_at',
        'follow_up_notes',
        'lost_at',
        'lost_reason',
        'converted_patient_id',
        'converted_at',
        'is_emergency',
        'whatsapp_redirected_at',
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
            'conversion_status' => SocialConversionStatus::class,
            'suggested_action' => SocialSuggestedAction::class,
            'response_channel' => SocialResponseChannel::class,
            'interest_score' => 'integer',
            'hot_lead_at' => 'datetime',
            'last_smart_link_visited_at' => 'datetime',
            'reheated_at' => 'datetime',
            'contacted_at' => 'datetime',
            'follow_up_at' => 'datetime',
            'lost_at' => 'datetime',
            'converted_at' => 'datetime',
            'is_emergency' => 'boolean',
            'whatsapp_redirected_at' => 'datetime',
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

    public function socialIdentity(): BelongsTo
    {
        return $this->belongsTo(SocialIdentity::class);
    }

    public function suggestedProcedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class, 'suggested_procedure_id');
    }

    public function convertedPatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'converted_patient_id');
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

    public function linkEvents(): HasMany
    {
        return $this->hasMany(SocialLinkEvent::class);
    }

    public function leadAlerts(): HasMany
    {
        return $this->hasMany(SocialLeadAlert::class);
    }

    public function activityRecords(): HasMany
    {
        return $this->hasMany(ActivityRecord::class);
    }
}
