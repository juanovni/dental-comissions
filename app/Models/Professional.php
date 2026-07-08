<?php

namespace App\Models;

use App\Enums\ProfessionalRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Professional extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'role',
        'whatsapp_phone',
        'email',
        'google_calendar_email',
        'google_calendar_token',
        'google_calendar_token_expires_at',
        'google_calendar_enabled',
        'is_active',
        'can_register_via_whatsapp',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'role' => ProfessionalRole::class,
            'is_active' => 'boolean',
            'can_register_via_whatsapp' => 'boolean',
            'google_calendar_token_expires_at' => 'datetime',
            'google_calendar_enabled' => 'boolean',
        ];
    }

    public function hasGoogleCalendar(): bool
    {
        return $this->google_calendar_enabled && filled($this->google_calendar_token);
    }

    public function getGoogleCalendarTokenDecrypted(): ?array
    {
        if (blank($this->google_calendar_token)) {
            return null;
        }

        return json_decode(Crypt::decryptString($this->google_calendar_token), true);
    }

    public function setGoogleCalendarToken(array $token): void
    {
        $this->update([
            'google_calendar_token' => Crypt::encryptString(json_encode($token)),
            'google_calendar_token_expires_at' => now()->addSeconds($token['expires_in'] ?? 3600),
        ]);
    }

    public function disconnectGoogleCalendar(): void
    {
        $this->update([
            'google_calendar_email' => null,
            'google_calendar_token' => null,
            'google_calendar_token_expires_at' => null,
            'google_calendar_enabled' => false,
        ]);
    }

    public function assignedAssistants(): BelongsToMany
    {
        return $this->belongsToMany(
            Professional::class,
            'doctor_assistant_assignments',
            'doctor_id',
            'assistant_id',
        )->withPivot(['is_active', 'starts_at', 'ends_at'])->withTimestamps();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    public function assignedDoctors(): BelongsToMany
    {
        return $this->belongsToMany(
            Professional::class,
            'doctor_assistant_assignments',
            'assistant_id',
            'doctor_id',
        )->withPivot(['is_active', 'starts_at', 'ends_at'])->withTimestamps();
    }

}
