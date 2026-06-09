<?php

namespace App\Models;

use App\Enums\WhatsappMessageDirection;
use App\Enums\WhatsappMessageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'professional_id',
        'direction',
        'status',
        'from_phone',
        'to_phone',
        'message_body',
        'message_sid',
        'related_message_id',
        'error_message',
        'ai_response',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'direction' => WhatsappMessageDirection::class,
            'status' => WhatsappMessageStatus::class,
            'ai_response' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function relatedMessage(): BelongsTo
    {
        return $this->belongsTo(WhatsappMessage::class, 'related_message_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(WhatsappMessage::class, 'related_message_id');
    }

    public static function findByPhone(string $phone): ?Professional
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? $phone;
        $phones = array_values(array_unique([$phone, $digits, '+' . $digits]));

        return Professional::whereIn('whatsapp_phone', $phones)
            ->where('is_active', true)
            ->where('can_register_via_whatsapp', true)
            ->first();
    }

    public function markAsConfirmed(): void
    {
        $this->update([
            'status' => WhatsappMessageStatus::Confirmed,
            'processed_at' => now(),
        ]);
    }

    public function markAsParsed(array $data): void
    {
        $this->update([
            'status' => WhatsappMessageStatus::Parsed,
            'ai_response' => $data,
            'processed_at' => now(),
        ]);
    }

    public function markAsNeedsReview(string $notes): void
    {
        $this->update([
            'status' => WhatsappMessageStatus::NeedsReview,
            'error_message' => $notes,
            'processed_at' => now(),
        ]);
    }

    public function markAsProcessed(): void
    {
        $this->update([
            'status' => WhatsappMessageStatus::Processed,
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => WhatsappMessageStatus::Failed,
            'error_message' => $error,
            'processed_at' => now(),
        ]);
    }
}
