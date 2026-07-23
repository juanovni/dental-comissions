<?php
    /** @var \Illuminate\Support\Collection $this->appointments */
    $appointments = $this->appointments();
?>

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

            <div class="flex gap-6 ml-auto text-sm">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-warning-500 inline-block"></span>
                    <span>Esperando: <strong>{{ $this->waitingCount() }}</strong></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-info-500 inline-block"></span>
                    <span>En consulta: <strong>{{ $this->inConsultationCount() }}</strong></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-primary-500 inline-block"></span>
                    <span>Confirmados: <strong>{{ $this->confirmedCount() }}</strong></span>
                </div>
            </div>
        </div>

        {{-- Tabla de citas --}}
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Hora</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Paciente</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Doctor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Procedimiento</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Estado</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Esperando</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Prioridad</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($appointments as $apt)
                        @php
                            $waiting = $apt['waiting_minutes'];
                            $priorityClass = match (true) {
                                $waiting === null => '',
                                $waiting < 10 => 'bg-green-50 dark:bg-green-950/20',
                                $waiting < 20 => 'bg-yellow-50 dark:bg-yellow-950/20',
                                default => 'bg-red-50 dark:bg-red-950/20',
                            };
                            $priorityDot = match (true) {
                                $waiting === null => 'bg-gray-300',
                                $waiting < 10 => 'bg-green-500',
                                $waiting < 20 => 'bg-yellow-500',
                                default => 'bg-red-500',
                            };
                        @endphp
                        <tr class="{{ $priorityClass }} hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-mono">
                                {{ $apt['scheduled_time'] }}
                                @if ($apt['is_late'])
                                    <span class="ml-1 text-xs text-danger-500">+{{ $apt['late_minutes'] }}min</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                {{ $apt['patient_name'] }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                {{ $apt['doctor_name'] }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                {{ $apt['procedure_name'] }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <x-filament::badge :color="$apt['status_color']">
                                    {{ $apt['status_label'] }}
                                </x-filament::badge>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-mono">
                                @if ($apt['has_checked_in'] && $waiting !== null)
                                    {{ $waiting }} min
                                @elseif ($apt['has_checked_in'])
                                    <span class="text-xs text-gray-400">calculando...</span>
                                @else
                                    <span class="text-xs text-gray-400">--</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if ($apt['has_checked_in'])
                                    <span class="inline-block w-4 h-4 rounded-full {{ $priorityDot }}"></span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex gap-1">
                                    @if (!$apt['has_checked_in'])
                                        <x-filament::button
                                            wire:click="checkIn({{ $apt['id'] }})"
                                            color="success"
                                            size="xs"
                                            icon="heroicon-m-check-circle">
                                            Llego
                                        </x-filament::button>
                                        <x-filament::button
                                            wire:click="markNoShow({{ $apt['id'] }})"
                                            color="danger"
                                            size="xs"
                                            icon="heroicon-m-x-circle">
                                            No show
                                        </x-filament::button>
                                    @elseif ($apt['status'] === \App\Enums\AppointmentStatus::Waiting->value)
                                        <x-filament::button
                                            wire:click="startConsultation({{ $apt['id'] }})"
                                            color="info"
                                            size="xs"
                                            icon="heroicon-m-play">
                                            Iniciar
                                        </x-filament::button>
                                    @elseif ($apt['status'] === \App\Enums\AppointmentStatus::InConsultation->value)
                                        <x-filament::button
                                            wire:click="finishConsultation({{ $apt['id'] }})"
                                            color="success"
                                            size="xs"
                                            icon="heroicon-m-check">
                                            Finalizar
                                        </x-filament::button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-gray-400">
                                No hay citas operativas para esta fecha.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
