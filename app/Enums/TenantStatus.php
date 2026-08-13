<?php

namespace App\Enums;

enum TenantStatus: string
{
    case Draft = 'draft';
    case Provisioning = 'provisioning';
    case Active = 'active';
    case Suspended = 'suspended';
    case ProvisioningFailed = 'provisioning_failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Provisioning => 'Provisionando',
            self::Active => 'Activo',
            self::Suspended => 'Suspendido',
            self::ProvisioningFailed => 'Provisionamiento fallido',
        };
    }

    public function allowsTenantAccess(): bool
    {
        return $this === self::Active;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}
