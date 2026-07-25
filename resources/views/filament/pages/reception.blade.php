<x-filament-panels::page>
    @php
        $summary = $this->summary();
        $columns = $this->columns();
        $selected = $this->selectedAppointment();
    @endphp

    <style>
        .reception-subtitle {
            color: #64748b;
            font-size: .84rem;
            margin: -1.05rem 0 1.25rem;
        }
        @media (min-width: 900px) {
            .reception-subtitle {
                margin-bottom: 1rem;
                max-width: 28rem;
            }
        }
        .reception-page { color: #0f172a; display: grid; gap: 1rem; }
        .reception-toolbar { display: flex; justify-content: flex-end; margin-top: -3.8rem; }
        .reception-search { background: #fff; border: 1px solid #e5e7eb; border-radius: .65rem; box-shadow: 0 1px 2px rgba(15, 23, 42, .05); font-size: .84rem; height: 2.65rem; outline: none; padding: 0 .85rem; width: min(100%, 24rem); }
        .reception-kpis { display: grid; gap: .75rem; grid-template-columns: repeat(5, minmax(0, 1fr)); }
        .reception-kpi, .reception-alerts, .reception-column, .reception-card, .reception-detail-card { background: #fff; border: 1px solid #e5e7eb; border-radius: .75rem; }
        .reception-kpi { align-items: center; display: flex; gap: .75rem; padding: .85rem; }
        .reception-kpi-icon { align-items: center; background: #eef8f8; border-radius: .65rem; color: #0f766e; display: inline-flex; height: 2.35rem; justify-content: center; width: 2.35rem; }
        .reception-kpi-icon svg { height: 1.15rem; width: 1.15rem; }
        .reception-kpi-label { color: #64748b; font-size: .74rem; font-weight: 500; }
        .reception-kpi-value { color: #0f172a; font-size: 1.1rem; font-weight: 650; }
        .reception-alerts { background: #fff7f7; border-color: #fecaca; color: #dc2626; padding: .85rem; }
        .reception-alert-title, .reception-column-title { align-items: center; display: flex; font-size: .86rem; font-weight: 600; gap: .45rem; justify-content: space-between; }
        .reception-alert-list { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: .65rem; }
        .reception-alert-chip { background: #fff; border: 1px solid #fecaca; border-radius: .5rem; font-size: .78rem; padding: .35rem .55rem; }
        .reception-board { display: grid; gap: .75rem; grid-template-columns: repeat(5, minmax(14rem, 1fr)); overflow-x: auto; }
        .reception-column { min-height: 32rem; overflow: hidden; }
        .reception-column-title { border-bottom: 1px solid #e5e7eb; padding: .75rem .85rem; }
        .reception-count { background: #f1f5f9; border-radius: 999px; color: #334155; font-size: .75rem; padding: .1rem .45rem; }
        .reception-card-list { display: grid; gap: .55rem; padding: .65rem; }
        .reception-card { cursor: pointer; display: grid; gap: .55rem; padding: .7rem; text-align: left; transition: border-color .14s ease, background .14s ease; width: 100%; }
        .reception-card:hover { background: #f8fafc; border-color: #cbd5e1; }
        .reception-card-head { align-items: start; display: grid; gap: .6rem; grid-template-columns: auto 1fr; }
        .reception-avatar { align-items: center; background: #f1f5f9; border-radius: 999px; color: #0f172a; display: inline-flex; font-size: .75rem; font-weight: 600; height: 2.25rem; justify-content: center; width: 2.25rem; }
        .reception-name { font-size: .9rem; font-weight: 650; }
        .reception-meta { color: #64748b; font-size: .78rem; line-height: 1.45; }
        .reception-badge { background: #ecfdf5; border-radius: .4rem; color: #047857; display: inline-flex; font-size: .72rem; font-weight: 600; padding: .2rem .45rem; width: fit-content; }
        .reception-wait { color: #b45309; font-size: .75rem; font-weight: 600; justify-self: end; }
        .reception-card-foot { align-items: center; display: flex; justify-content: space-between; }
        .reception-drawer { background: rgba(15, 23, 42, .45); inset: 0; position: fixed; z-index: 60; }
        .reception-drawer-panel { background: #fff; border-left: 1px solid #e5e7eb; display: grid; gap: .85rem; height: 100%; margin-left: auto; overflow-y: auto; padding: 1rem; width: min(100%, 28rem); }
        .reception-drawer-head { align-items: start; display: flex; gap: .75rem; justify-content: space-between; }
        .reception-close { border: 1px solid #e5e7eb; border-radius: 999px; color: #64748b; height: 1.85rem; width: 1.85rem; }
        .reception-detail-card { display: grid; gap: .55rem; padding: .85rem; }
        .reception-fact { display: grid; gap: .75rem; grid-template-columns: 1fr 1fr; }
        .reception-fact span:first-child { color: #64748b; font-size: .8rem; }
        .reception-fact span:last-child { font-size: .82rem; font-weight: 600; text-align: right; }
        .reception-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
        .reception-action { background: #fff; border: 1px solid #e5e7eb; border-radius: .5rem; font-size: .78rem; font-weight: 600; min-height: 2.15rem; padding: .45rem .65rem; }
        .reception-action-primary { background: #0f766e; border-color: #0f766e; color: #fff; }
        .reception-note-preview { background: #f8fafc; border-radius: .5rem; color: #475569; font-size: .76rem; padding: .45rem .55rem; }
        .reception-modal-card { background: #fff; border-radius: .875rem; display: grid; gap: .75rem; padding: 1.25rem; width: min(100%, 30rem); }
        .reception-textarea { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: .5rem; font-size: .82rem; min-height: 7rem; padding: .65rem; width: 100%; }
        @media (max-width: 900px) { .reception-toolbar { margin-top: 0; } .reception-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } .reception-board { grid-template-columns: repeat(5, 16rem); } }
    </style>

    <div class="reception-page">
        <p class="reception-subtitle">Citas confirmadas para hoy · {{ $summary['arriving'] + $summary['waiting'] + $summary['overdue'] + $summary['in_consultation'] }} pacientes en flujo</p>
        <div class="reception-toolbar">
            <input class="reception-search" type="search" wire:model.live.debounce.350ms="search" placeholder="Buscar paciente, doctor...">
        </div>

        <div class="reception-kpis">
            <div class="reception-kpi"><span class="reception-kpi-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z"></path></svg></span><div><div class="reception-kpi-label">Por llegar</div><div class="reception-kpi-value">{{ $summary['arriving'] }}</div></div></div>
            <div class="reception-kpi"><span class="reception-kpi-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path></svg></span><div><div class="reception-kpi-label">En espera</div><div class="reception-kpi-value">{{ $summary['waiting'] }}</div></div></div>
            <div class="reception-kpi"><span class="reception-kpi-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"></path></svg></span><div><div class="reception-kpi-label">Retrasados</div><div class="reception-kpi-value">{{ $summary['overdue'] }}</div></div></div>
            <div class="reception-kpi"><span class="reception-kpi-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"></path></svg></span><div><div class="reception-kpi-label">En consulta</div><div class="reception-kpi-value">{{ $summary['in_consultation'] }}</div></div></div>
            <div class="reception-kpi"><span class="reception-kpi-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"></path></svg></span><div><div class="reception-kpi-label">No Show</div><div class="reception-kpi-value">{{ $summary['no_show'] }}</div></div></div>
        </div>

        @if ($this->alerts()->isNotEmpty())
            <div class="reception-alerts">
                <div class="reception-alert-title">Alertas operativas</div>
                <div class="reception-alert-list">
                    @foreach ($this->alerts() as $alert)
                        <span class="reception-alert-chip">{{ $alert }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="reception-board">
            @foreach ($columns as $key => $label)
                @php($cards = $this->cards($key))
                <section class="reception-column">
                    <div class="reception-column-title"><span>{{ $label }}</span><span class="reception-count">{{ $cards->count() }}</span></div>
                    <div class="reception-card-list">
                        @forelse ($cards as $appointment)
                            <button type="button" class="reception-card" wire:click="selectAppointment({{ $appointment->id }})">
                                <span class="reception-card-head">
                                    <span class="reception-avatar">{{ str($appointment->patient?->full_name ?? 'P')->explode(' ')->map(fn ($part) => str($part)->substr(0, 1))->take(2)->implode('') }}</span>
                                    <span>
                                        <span class="reception-name">{{ $appointment->patient?->full_name ?? 'Paciente sin nombre' }}</span>
                                        <span class="reception-meta">{{ $appointment->scheduled_at?->format('h:i a') }} · {{ $appointment->doctor?->name ?? 'Sin doctor' }}</span>
                                        <span class="reception-meta">{{ $appointment->procedure?->name ?? 'Sin procedimiento' }}</span>
                                    </span>
                                </span>
                                <span class="reception-card-foot">
                                    <span class="reception-badge">{{ $appointment->status->label() }}</span>
                                    @if ($appointment->waitingMinutes() !== null)
                                        <span class="reception-wait">{{ $appointment->waitingMinutes() }} min</span>
                                    @endif
                                </span>
                                @if ($appointment->latestAppointmentNote)
                                    <span class="reception-note-preview">{{ str($appointment->latestAppointmentNote->note)->limit(70) }}</span>
                                @endif
                            </button>
                        @empty
                            <div class="reception-meta" style="padding: .8rem; text-align: center;">Sin pacientes.</div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    @if ($selected)
        <div class="reception-drawer" wire:click.self="closeDetail">
            <aside class="reception-drawer-panel">
                <div class="reception-drawer-head">
                    <div style="display: flex; gap: .75rem;">
                        <span class="reception-avatar">{{ str($selected->patient?->full_name ?? 'P')->explode(' ')->map(fn ($part) => str($part)->substr(0, 1))->take(2)->implode('') }}</span>
                        <div>
                            <div class="reception-name">{{ $selected->patient?->full_name ?? 'Paciente sin nombre' }}</div>
                            <div class="reception-meta">{{ $selected->patient?->phone ?? 'Sin telefono' }}</div>
                            <span class="reception-badge" style="margin-top: .35rem;">{{ $selected->status->label() }}</span>
                        </div>
                    </div>
                    <button type="button" class="reception-close" wire:click="closeDetail">×</button>
                </div>

                <div class="reception-detail-card">
                    <div class="reception-alert-title">Cita</div>
                    <div class="reception-fact"><span>Hora</span><span>{{ $selected->scheduled_at?->format('h:i a') ?? '-' }}</span></div>
                    <div class="reception-fact"><span>Duracion</span><span>{{ $selected->duration_minutes ? $selected->duration_minutes.' min' : '-' }}</span></div>
                    <div class="reception-fact"><span>Doctor</span><span>{{ $selected->doctor?->name ?? '-' }}</span></div>
                    <div class="reception-fact"><span>Procedimiento</span><span>{{ $selected->procedure?->name ?? '-' }}</span></div>
                    <div class="reception-fact"><span>Canal check-in</span><span>{{ $selected->check_in_source ?? '-' }}</span></div>
                    <div class="reception-fact"><span>Esperando</span><span>{{ $selected->waitingMinutes() !== null ? $selected->waitingMinutes().' min' : '-' }}</span></div>
                </div>

                @if ($selected->notes)
                    <div class="reception-detail-card">
                        <div class="reception-alert-title">Nota de cita</div>
                        <div class="reception-meta">{{ $selected->notes }}</div>
                    </div>
                @endif

                @if ($selected->latestAppointmentNote)
                    <div class="reception-detail-card">
                        <div class="reception-alert-title">Ultima nota operativa</div>
                        <div class="reception-meta">{{ $selected->latestAppointmentNote->note }}</div>
                    </div>
                @endif

                <div class="reception-detail-card">
                    <div class="reception-alert-title">Siguiente paso</div>
                    <div class="reception-actions">
                        @foreach ($this->availableTransitions($selected) as $status => $label)
                            <button type="button" class="reception-action @if ($loop->first) reception-action-primary @endif" wire:click="transition({{ $selected->id }}, '{{ $status }}')">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <button type="button" class="reception-action" wire:click="openNoteModal({{ $selected->id }})">Nota</button>

                <a class="reception-action" style="display: inline-flex; justify-content: center; text-decoration: none;" href="{{ $this->appointmentUrl($selected) }}">
                    Abrir cita completa
                </a>
            </aside>
        </div>
    @endif

    @if ($noteAppointmentId)
        <div class="reception-drawer" wire:click.self="closeNoteModal" style="display: flex; align-items: center; justify-content: center;">
            <div class="reception-modal-card">
                <div class="reception-alert-title">Agregar nota operativa</div>
                <textarea class="reception-textarea" wire:model="noteText" placeholder="Ej.: Llego con acompanante. Paciente ansioso, aplicar comunicacion calmada."></textarea>
                @error('noteText') <div class="reception-meta" style="color: #dc2626;">{{ $message }}</div> @enderror
                <div class="reception-actions" style="justify-content: flex-end;">
                    <button type="button" class="reception-action" wire:click="closeNoteModal">Cancelar</button>
                    <button type="button" class="reception-action reception-action-primary" wire:click="saveNote">Guardar nota</button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
