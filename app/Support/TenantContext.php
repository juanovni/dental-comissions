<?php

namespace App\Support;

use App\Models\Clinic;
use Closure;

class TenantContext
{
    private ?Clinic $clinic = null;

    public function set(Clinic $clinic): void
    {
        $this->clinic = $clinic;
    }

    public function get(): ?Clinic
    {
        return $this->clinic;
    }

    public function id(): ?int
    {
        return $this->clinic?->getKey();
    }

    public function require(): Clinic
    {
        if ($this->clinic === null) {
            throw new \RuntimeException('Tenant context is not set.');
        }

        return $this->clinic;
    }

    public function clear(): void
    {
        $this->clinic = null;
    }

    public function run(Clinic|int $clinic, Closure $callback): mixed
    {
        $previous = $this->clinic;
        $resolved = $clinic instanceof Clinic ? $clinic : Clinic::query()->findOrFail($clinic);

        $this->set($resolved);

        try {
            return $callback();
        } finally {
            $this->clinic = $previous;
        }
    }
}
