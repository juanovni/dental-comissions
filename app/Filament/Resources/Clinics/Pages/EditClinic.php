<?php

namespace App\Filament\Resources\Clinics\Pages;

use App\Filament\Resources\Clinics\ClinicResource;
use App\Models\Clinic;
use App\Services\EasypanelDomainService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditClinic extends EditRecord
{
    protected static string $resource = ClinicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $phoneNumberId = data_get($data, 'settings.integrations.whatsapp.phone_number_id');

        if (filled($phoneNumberId)) {
            $duplicate = Clinic::query()
                ->whereKeyNot($this->record->getKey())
                ->where(function ($query) use ($phoneNumberId): void {
                    $query
                        ->where('settings->integrations->whatsapp->phone_number_id', $phoneNumberId)
                        ->orWhere('settings->whatsapp_phone_number_id', $phoneNumberId);
                })
                ->first();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'data.settings.integrations.whatsapp.phone_number_id' => "Este Phone Number ID ya esta configurado en {$duplicate->primary_domain}.",
                ]);
            }
        }

        return $data;
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
