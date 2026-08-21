<?php

namespace App\Filament\Resources\Clinics\Pages;

use App\Filament\Resources\Clinics\ClinicResource;
use App\Services\EasypanelDomainService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditClinic extends EditRecord
{
    protected static string $resource = ClinicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if (! $this->record->wasChanged('primary_domain')) {
            return;
        }

        $created = app(EasypanelDomainService::class)->ensureDomainForClinic($this->record);

        $notification = Notification::make()
            ->title($created ? 'Dominio actualizado en Easypanel' : 'Dominio no actualizado en Easypanel')
            ->body($created
                ? 'El dominio del tenant fue enviado para configuracion.'
                : 'El tenant fue guardado, pero revisa la configuracion/API de Easypanel para el dominio.');

        ($created ? $notification->success() : $notification->warning())->send();
    }
}
