<?php

namespace App\Models;

use App\Enums\VoiceEventType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoiceEvent extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
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

    public function voiceCall(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(VoiceCall::class);
    }
}
