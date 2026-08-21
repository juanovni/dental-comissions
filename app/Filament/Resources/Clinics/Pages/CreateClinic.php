<?php

namespace App\Filament\Resources\Clinics\Pages;

use App\Filament\Resources\Clinics\ClinicResource;
use App\Services\EasypanelDomainService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateClinic extends CreateRecord
{
    protected static string $resource = ClinicResource::class;

    protected function afterCreate(): void
    {
        $created = app(EasypanelDomainService::class)->ensureDomainForClinic($this->record);

        $notification = Notification::make()
            ->title($created ? 'Dominio enviado a Easypanel' : 'Dominio no creado en Easypanel')
            ->body($created
                ? 'El dominio del tenant fue enviado para configuracion.'
                : 'El tenant fue creado, pero revisa la configuracion/API de Easypanel para el dominio.');

        ($created ? $notification->success() : $notification->warning())->send();
    }
}
