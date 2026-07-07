<?php

namespace App\Models;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialPlatform;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'social_account_id',
        'social_identity_id',
        'social_post_id',
        'suggested_procedure_id',
        'suggested_doctor_id',
        'platform',
        'external_comment_id',
        'author_name',
        'author_username',
        'author_external_id',
        'comment_text',
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
        'is_read',
        'is_starred',
        'needs_review',
        'reviewed_at',
        'reviewed_by',
        'internal_notes',
        'assigned_to',
        'pipeline_stage',
        'pipeline_status',
        'converted_to_lead_at',
        'status',
        'sentiment',
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
        'converted_at' => 'datetime',
        'is_conversion' => 'boolean',
        'last_closing_alert_sent_at' => 'datetime',
        'requires_human_handoff' => 'boolean',
        'handoff_resolved_at' => 'datetime',
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
}
