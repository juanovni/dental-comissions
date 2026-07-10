<x-filament-panels::page>
    {{ $this->form }}

    <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
        <x-filament::button wire:click="save" type="button">
            Guardar configuración
        </x-filament::button>
    </div>
</x-filament-panels::page>
