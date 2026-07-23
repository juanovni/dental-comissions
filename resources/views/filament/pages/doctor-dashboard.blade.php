<?php /** @var \Illuminate\Support\Collection $this->doctors */ ?>
<x-filament-panels::page>
    <div class="space-y-6">
        @php $doctors = $this->doctors; @endphp

        @forelse ($doctors as $doctor)
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                        <x-filament::icon name="heroicon-o-user" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">{{ $doctor['name'] }}</h3>
                        @if ($doctor['next'])
                            <p class="text-sm text-gray-500">Proximo: <strong>{{ $doctor['next']['patient_name'] }}</strong></p>
                        @else
                            <p class="text-sm text-gray-400">Sin pacientes en espera</p>
                        @endif
                    </div>
                </div>

                {{-- Proximo paciente --}}
                @if ($doctor['next'])
                    <div class="bg-primary-50 dark:bg-primary-950/30 rounded-lg p-4 mb-6 border border-primary-200 dark:border-primary-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Proximo paciente</p>
                                <p class="text-xl font-bold">{{ $doctor['next']['patient_name'] }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $doctor['next']['procedure_name'] }} &middot; {{ $doctor['next']['scheduled_time'] }}
                                </p>
                            </div>
                            <div class="text-right">
                                @if ($doctor['next']['waiting_minutes'] !== null)
                                    <p class="text-lg font-mono font-bold
                                        @if ($doctor['next']['waiting_minutes'] < 10) text-green-600
                                        @elseif ($doctor['next']['waiting_minutes'] < 20) text-yellow-600
                                        @else text-red-600 @endif">
                                        Esperando {{ $doctor['next']['waiting_minutes'] }} min
                                    </p>
                                @endif
                                <x-filament::badge :color="$doctor['next']['status_color']">
                                    {{ $doctor['next']['status_label'] }}
                                </x-filament::badge>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Cola de pacientes --}}
                @if ($doctor['queue']->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                            <thead>
                                <tr class="text-xs font-semibold uppercase tracking-wider text-gray-400">
                                    <th class="px-3 py-2 text-left">Paciente</th>
                                    <th class="px-3 py-2 text-left">Hora</th>
                                    <th class="px-3 py-2 text-left">Estado</th>
                                    <th class="px-3 py-2 text-left">Espera</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($doctor['queue'] as $pat)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td class="px-3 py-2 text-sm font-medium">{{ $pat['patient_name'] }}</td>
                                        <td class="px-3 py-2 text-sm font-mono">{{ $pat['scheduled_time'] }}</td>
                                        <td class="px-3 py-2">
                                            <x-filament::badge :color="$pat['status_color']" size="sm">
                                                {{ $pat['status_label'] }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="px-3 py-2 text-sm font-mono">
                                            @if ($pat['waiting_minutes'] !== null)
                                                {{ $pat['waiting_minutes'] }} min
                                            @else
                                                --
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-400 text-center py-4">Sin pacientes en cola</p>
                @endif
            </div>
        @empty
            <div class="text-center py-12 text-gray-400">
                No hay doctores activos registrados.
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
