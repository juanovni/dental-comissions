<?php

namespace App\Models;

use App\Enums\VoiceEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'voice_call_id',
        'type',
        'direction',
        'provider_event_id',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'type' => VoiceEventType::class,
            'payload' => 'array',
        ];
    }

    public function voiceCall(): BelongsTo
    {
        return $this->belongsTo(VoiceCall::class);
    }
}
