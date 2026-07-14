<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentSlotOffer extends Model
{
    protected $fillable = [
        'social_comment_id',
        'whatsapp_message_id',
        'appointment_id',
        'token',
        'status',
        'selected_option_index',
        'expires_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'selected_option_index' => 'integer',
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function socialComment(): BelongsTo
    {
        return $this->belongsTo(SocialComment::class);
    }

    public function whatsappMessage(): BelongsTo
    {
        return $this->belongsTo(WhatsappMessage::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function holds(): HasMany
    {
        return $this->hasMany(AppointmentSlotHold::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
