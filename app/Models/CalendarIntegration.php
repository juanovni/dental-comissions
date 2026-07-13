<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class CalendarIntegration extends Model
{
    protected $fillable = [
        'provider',
        'account_email',
        'calendar_id',
        'token',
        'token_expires_at',
        'is_enabled',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'is_enabled' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public static function clinicGoogle(): self
    {
        return static::firstOrCreate(
            ['provider' => 'google_calendar'],
            [
                'calendar_id' => 'primary',
                'is_enabled' => false,
            ],
        );
    }

    public function isConnected(): bool
    {
        return $this->is_enabled && filled($this->token);
    }

    public function getTokenDecrypted(): ?array
    {
        if (blank($this->token)) {
            return null;
        }

        return json_decode(Crypt::decryptString($this->token), true);
    }

    public function setToken(array $token): void
    {
        $this->update([
            'token' => Crypt::encryptString(json_encode($token)),
            'token_expires_at' => now()->addSeconds($token['expires_in'] ?? 3600),
        ]);
    }

    public function disconnect(): void
    {
        $this->update([
            'account_email' => null,
            'token' => null,
            'token_expires_at' => null,
            'is_enabled' => false,
            'metadata' => null,
        ]);
    }
}
