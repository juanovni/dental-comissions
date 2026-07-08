<?php

namespace App\Models;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialCommentClassification;
use App\Enums\SocialCommentStatus;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialPlatform;
use App\Enums\SocialPipelineStage;
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
        'suggested_procedure_id',
        'suggested_doctor_id',
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
        'raw_payload',
        'published_at',
        'processed_at',
        'auto_replied_at',
        'auto_reply_external_id',
        'auto_reply_error',
        'auto_reply_attempts',
        'auto_reply_message',
        'type',
        'has_smart_link',
        'smart_link_url',
        'smart_link_engaged_at',
        'smart_link_clicks',
        'is_hidden',
        'is_lead',
        'ai_summary',
        'ai_intent',
        'ai_confidence',
        'tracking_token',
        'conversion_status',
        'converted_patient_id',
        'is_emergency',
        'whatsapp_redirected_at',
        'interest_score',
        'hot_lead_at',
        'last_smart_link_visited_at',
        'reheated_at',
        'recent_engagement_score',
        'last_engagement_at',
        'engagement_event_count_1h',
        'engagement_event_count_24h',
        'last_engagement_event_type',
        'engagement_priority_reason',
        'contacted_at',
        'follow_up_at',
        'follow_up_notes',
        'lost_at',
        'lost_reason',
        'is_read',
        'is_starred',
        'needs_review',
        'reviewed_at',
        'reviewed_by',
        'internal_notes',
        'assigned_to',
        'pipeline_stage',
        'estimated_value',
        'pipeline_status',
        'converted_to_lead_at',
        'source_url',
        'parent_external_comment_id',
        'root_external_comment_id',
        'label',
        'conversion_notes',
        'appointment_scheduled_at',
        'appointment_notes',
        'conversion_value',
        'is_conversion',
        'converted_at',
        'closing_opportunity_score',
        'last_closing_alert_sent_at',
        'requires_human_handoff',
        'handoff_reason',
        'handoff_assigned_to',
        'handoff_resolved_at',
    ];

    protected $casts = [
        'platform' => SocialPlatform::class,
        'type' => SocialCommentActionType::class,
        'classification' => SocialCommentClassification::class,
        'sentiment' => SocialSentiment::class,
        'priority' => SocialPriority::class,
        'reputation_risk' => SocialReputationRisk::class,
        'status' => SocialCommentStatus::class,
        'suggested_action' => SocialSuggestedAction::class,
        'response_channel' => SocialResponseChannel::class,
        'has_smart_link' => 'boolean',
        'smart_link_engaged_at' => 'datetime',
        'is_hidden' => 'boolean',
        'is_lead' => 'boolean',
        'is_read' => 'boolean',
        'is_starred' => 'boolean',
        'needs_review' => 'boolean',
        'reviewed_at' => 'datetime',
        'converted_to_lead_at' => 'datetime',
        'appointment_scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'processed_at' => 'datetime',
        'auto_replied_at' => 'datetime',
        'auto_reply_attempts' => 'integer',
        'ai_response' => 'array',
        'raw_payload' => 'array',
        'converted_at' => 'datetime',
        'whatsapp_redirected_at' => 'datetime',
        'is_emergency' => 'boolean',
        'hot_lead_at' => 'datetime',
        'last_smart_link_visited_at' => 'datetime',
        'reheated_at' => 'datetime',
        'last_engagement_at' => 'datetime',
        'interest_score' => 'integer',
        'ai_confidence' => 'integer',
        'recent_engagement_score' => 'integer',
        'engagement_event_count_1h' => 'integer',
        'engagement_event_count_24h' => 'integer',
        'contacted_at' => 'datetime',
        'follow_up_at' => 'datetime',
        'lost_at' => 'datetime',
        'is_conversion' => 'boolean',
        'last_closing_alert_sent_at' => 'datetime',
        'requires_human_handoff' => 'boolean',
        'handoff_resolved_at' => 'datetime',
        'conversion_status' => SocialConversionStatus::class,
        'pipeline_stage' => SocialPipelineStage::class,
        'estimated_value' => 'decimal:2',
    ];

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function socialIdentity(): BelongsTo
    {
        return $this->belongsTo(SocialIdentity::class);
    }

    public function socialPost(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class);
    }

    public function suggestedProcedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class, 'suggested_procedure_id');
    }

    public function suggestedDoctor(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'suggested_doctor_id');
    }

    public function convertedPatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'converted_patient_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(SocialCommentAction::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_comment_id');
    }

    public function leadAlerts(): HasMany
    {
        return $this->hasMany(SocialLeadAlert::class);
    }

    public function linkEvents(): HasMany
    {
        return $this->hasMany(SocialLinkEvent::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'social_comment_id');
    }
}
