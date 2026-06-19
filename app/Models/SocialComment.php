<?php

namespace App\Models;

use App\Enums\SocialCommentClassification;
use App\Enums\SocialCommentStatus;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialPipelineStage;
use App\Enums\SocialPlatform;
use App\Enums\SocialPriority;
use App\Enums\SocialReputationRisk;
use App\Enums\SocialResponseChannel;
use App\Enums\SocialSentiment;
use App\Enums\SocialSuggestedAction;
use Illuminate\Database\Eloquent\Builder;
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
        'recent_engagement_score',
        'last_engagement_at',
        'engagement_event_count_1h',
        'engagement_event_count_24h',
        'last_engagement_event_type',
        'engagement_priority_reason',
        'pipeline_stage',
        'estimated_value',
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
            'pipeline_stage' => SocialPipelineStage::class,
            'estimated_value' => 'decimal:2',
            'suggested_action' => SocialSuggestedAction::class,
            'response_channel' => SocialResponseChannel::class,
            'interest_score' => 'integer',
            'recent_engagement_score' => 'integer',
            'last_engagement_at' => 'datetime',
            'engagement_event_count_1h' => 'integer',
            'engagement_event_count_24h' => 'integer',
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

    protected static function booted(): void
    {
        static::saving(function (SocialComment $comment): void {
            if ($comment->isDirty('conversion_status') && ! $comment->isDirty('pipeline_stage')) {
                $comment->pipeline_stage = SocialPipelineStage::fromConversionStatus($comment->conversion_status);
            }

            if ($comment->isDirty('pipeline_stage') && ! $comment->isDirty('conversion_status')) {
                $lostStatuses = [SocialConversionStatus::Lost];
                if ($comment->pipeline_stage === SocialPipelineStage::Lost && $comment->conversion_status && ! in_array($comment->conversion_status, $lostStatuses, true)) {
                    $comment->lost_at ??= now();
                }
            }
        });
    }

    public function scopeByPipelineStage(Builder $query, SocialPipelineStage $stage): Builder
    {
        return $query->where('pipeline_stage', $stage);
    }

    public function scopeHotLeads(Builder $query): Builder
    {
        return $query->where('last_smart_link_visited_at', '>=', now()->subMinutes(10));
    }

    public function scopeColdLeads(Builder $query, ?int $hours = null): Builder
    {
        $hours ??= 48;

        return $query->where(function (Builder $q) use ($hours): void {
            $q->whereNull('last_smart_link_visited_at')
                ->orWhere('last_smart_link_visited_at', '<', now()->subHours($hours));
        });
    }

    public function scopeFollowUpToday(Builder $query): Builder
    {
        return $query->whereNotNull('follow_up_at')
            ->whereDate('follow_up_at', today());
    }

    public static function totalEstimatedValueByStage(?SocialPipelineStage $stage = null): array
    {
        $query = self::query()
            ->selectRaw('pipeline_stage, count(*) as total_count, coalesce(sum(estimated_value), 0) as total_value')
            ->whereNotNull('pipeline_stage')
            ->groupBy('pipeline_stage');

        if ($stage) {
            $query->where('pipeline_stage', $stage);
        }

        return $query
            ->pluck('total_value', 'pipeline_stage')
            ->toArray();
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
