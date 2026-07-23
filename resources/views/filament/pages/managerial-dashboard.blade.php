<?php $kpis = $this->kpis(); ?>
<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filtro de fecha --}}
        <div class="flex items-center gap-4">
            <x-filament::input.wrapper>
                <x-filament::input type="date" wire:model.live="selectedDate" />
            </x-filament::input.wrapper>
            <x-filament::button wire:click="today" color="gray" size="sm">
                Hoy
            </x-filament::button>

            <div class="ml-auto text-sm text-gray-400">
                {{ \Carbon\Carbon::parse($this->selectedDate)->isoFormat('dddd D [de] MMMM [de] YYYY') }}
            </div>
        </div>

        {{-- KPIs principales --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Citas hoy</p>
                <p class="text-3xl font-bold mt-1">{{ $kpis['total_citas'] }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Asistieron</p>
                <p class="text-3xl font-bold mt-1 text-success-600">{{ $kpis['asistieron'] }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">No Show</p>
                <p class="text-3xl font-bold mt-1 text-danger-600">{{ $kpis['no_show'] }}
                    @if ($kpis['tasa_no_show'] > 0)
                        <span class="text-sm font-normal text-gray-400">({{ $kpis['tasa_no_show'] }}%)</span>
                    @endif
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Canceladas</p>
                <p class="text-3xl font-bold mt-1 text-gray-500">{{ $kpis['canceladas'] }}</p>
            </div>
        </div>

        {{-- Metricas de tiempo --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-4">Tiempos promedio</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">Espera en recepcion</span>
                        <span class="text-2xl font-bold font-mono">
                            {{ $kpis['tiempo_promedio_espera'] ?? '--' }}
                            @if ($kpis['tiempo_promedio_espera']) <span class="text-sm font-normal text-gray-400">min</span> @endif
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">Duracion consulta</span>
                        <span class="text-2xl font-bold font-mono">
                            {{ $kpis['tiempo_promedio_consulta'] ?? '--' }}
                            @if ($kpis['tiempo_promedio_consulta']) <span class="text-sm font-normal text-gray-400">min</span> @endif
                        </span>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-4">Doctores</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">Menor tiempo de espera</span>
                        <span class="font-semibold text-success-600">{{ $kpis['doctor_menor_espera'] ?? '--' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">Mayor retraso promedio</span>
                        <span class="font-semibold text-danger-600">{{ $kpis['doctor_mayor_retraso'] ?? '--' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
