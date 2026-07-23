<?php

namespace App\Models;

use App\Enums\VoiceCallStatus;
use App\Enums\VoiceChannelType;
use App\Enums\VoiceHandoffReason;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoiceCall extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'channel',
        'provider',
        'provider_call_id',
        'from_phone',
        'to_phone',
        'status',
        'handoff_reason',
        'started_at',
        'ended_at',
        'duration_seconds',
        'transcript',
        'ai_summary',
        'last_error',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'channel' => VoiceChannelType::class,
            'status' => VoiceCallStatus::class,
            'handoff_reason' => VoiceHandoffReason::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(VoiceEvent::class, 'voice_call_id');
    }
}
