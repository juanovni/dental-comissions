<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\TenantContext;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class CalendarIntegration extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'clinic_id',
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
        $clinicId = static::currentClinicId();

        return static::firstOrCreate(
            [
                'clinic_id' => $clinicId,
                'provider' => 'google_calendar',
            ],
            [
                'clinic_id' => $clinicId,
                'calendar_id' => 'primary',
                'is_enabled' => false,
            ],
        );
    }

    private static function currentClinicId(): ?int
    {
        $clinicId = app(TenantContext::class)->id();

        if ($clinicId !== null) {
            return $clinicId;
        }

        $tenant = Filament::getTenant();

        if ($tenant instanceof Clinic) {
            return $tenant->getKey();
        }

        $panel = Filament::getCurrentPanel();
        $user = auth()->user();

        if ($panel?->getId() === 'clinic' && $user && method_exists($user, 'getDefaultTenant')) {
            $defaultTenant = $user->getDefaultTenant($panel);

            if ($defaultTenant instanceof Clinic) {
                return $defaultTenant->getKey();
            }
        }

        return null;
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
