<?php

namespace App\Models\Concerns;

use App\Models\Clinic;
use App\Support\TenantContext;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function scopeForCurrentTenant(Builder $query): Builder
    {
        $clinicId = app(TenantContext::class)->id();

        if ($clinicId === null) {
            if (Filament::getCurrentPanel()?->getId() === 'clinic') {
                return $query->whereRaw('1 = 0');
            }

            return $query;
        }

        return $query->where($query->getModel()->getTable().'.clinic_id', $clinicId);
    }

    public function scopeForTenant(Builder $query, Clinic|int $clinic): Builder
    {
        $clinicId = $clinic instanceof Clinic ? $clinic->getKey() : $clinic;

        return $query->where($query->getModel()->getTable().'.clinic_id', $clinicId);
    }
}
