<?php

namespace App\Models\Concerns;

use App\Models\Clinic;
use App\Support\TenantContext;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function ($model): void {
            if ($model->getAttribute('clinic_id') !== null) {
                return;
            }

            $clinicId = self::currentTenantId();

            if ($clinicId !== null) {
                $model->setAttribute('clinic_id', $clinicId);
            }
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function scopeForCurrentTenant(Builder $query): Builder
    {
        $clinicId = self::currentTenantId();

        if ($clinicId === null) {
            if (Filament::getCurrentPanel()?->getId() === 'clinic') {
                return $query->whereRaw('1 = 0');
            }

            return $query;
        }

        return $query->where($query->getModel()->getTable().'.clinic_id', $clinicId);
    }

    private static function currentTenantId(): ?int
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

    public function scopeForTenant(Builder $query, Clinic|int $clinic): Builder
    {
        $clinicId = $clinic instanceof Clinic ? $clinic->getKey() : $clinic;

        return $query->where($query->getModel()->getTable().'.clinic_id', $clinicId);
    }
}
