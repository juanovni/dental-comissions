<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentSlotHold extends Model
{
    protected $fillable = [
        'appointment_slot_offer_id',
        'social_comment_id',
        'appointment_id',
        'doctor_id',
        'procedure_id',
        'starts_at',
        'ends_at',
        'expires_at',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(AppointmentSlotOffer::class, 'appointment_slot_offer_id');
    }

    public function socialComment(): BelongsTo
    {
        return $this->belongsTo(SocialComment::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'doctor_id');
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }
}
