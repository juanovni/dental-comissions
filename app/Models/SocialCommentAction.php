<?php

namespace App\Models;

use App\Enums\SocialCommentActionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialCommentAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'social_comment_id',
        'action',
        'performed_by',
        'notes',
        'response_text',
        'external_response',
    ];

    protected function casts(): array
    {
        return [
            'action' => SocialCommentActionType::class,
            'external_response' => 'array',
        ];
    }

    public function socialComment(): BelongsTo
    {
        return $this->belongsTo(SocialComment::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
