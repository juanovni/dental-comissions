<x-filament-panels::page>
    @php
        $summary = $this->summary();
        $columns = $this->columns();
        $selected = $this->selectedAppointment();
    @endphp

    <style>
        .reception-page { color: #0f172a; display: grid; gap: 1rem; }
        .reception-toolbar { display: flex; justify-content: flex-end; margin-top: -3.8rem; }
        .reception-search { background: #fff; border: 1px solid #e5e7eb; border-radius: .65rem; box-shadow: 0 1px 2px rgba(15, 23, 42, .05); font-size: .84rem; height: 2.65rem; outline: none; padding: 0 .85rem; width: min(100%, 24rem); }
        .reception-kpis { display: grid; gap: .75rem; grid-template-columns: repeat(5, minmax(0, 1fr)); }
        .reception-kpi, .reception-alerts, .reception-column, .reception-card, .reception-detail-card { background: #fff; border: 1px solid #e5e7eb; border-radius: .75rem; }
        .reception-kpi { align-items: center; display: flex; gap: .75rem; padding: .85rem; }
        .reception-kpi-icon { align-items: center; background: #eef8f8; border-radius: .65rem; color: #0f766e; display: inline-flex; font-size: .82rem; font-weight: 600; height: 2.35rem; justify-content: center; width: 2.35rem; }
        .reception-kpi-label { color: #64748b; font-size: .74rem; font-weight: 500; }
        .reception-kpi-value { color: #0f172a; font-size: 1.1rem; font-weight: 650; }
        .reception-alerts { background: #fff7f7; border-color: #fecaca; color: #dc2626; padding: .85rem; }
        .reception-alert-title, .reception-column-title { align-items: center; display: flex; font-size: .8rem; font-weight: 650; gap: .45rem; justify-content: space-between; text-transform: uppercase; }
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
        @media (max-width: 900px) { .reception-toolbar { margin-top: 0; } .reception-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } .reception-board { grid-template-columns: repeat(5, 16rem); } }
    </style>

    <div class="reception-page">
        <div class="reception-toolbar">
            <input class="reception-search" type="search" wire:model.live.debounce.350ms="search" placeholder="Buscar paciente, doctor...">
        </div>

        <div class="reception-kpis">
            <div class="reception-kpi"><span class="reception-kpi-icon">PL</span><div><div class="reception-kpi-label">Por llegar</div><div class="reception-kpi-value">{{ $summary['arriving'] }}</div></div></div>
            <div class="reception-kpi"><span class="reception-kpi-icon">EE</span><div><div class="reception-kpi-label">En espera</div><div class="reception-kpi-value">{{ $summary['waiting'] }}</div></div></div>
            <div class="reception-kpi"><span class="reception-kpi-icon">RT</span><div><div class="reception-kpi-label">Retrasados</div><div class="reception-kpi-value">{{ $summary['overdue'] }}</div></div></div>
            <div class="reception-kpi"><span class="reception-kpi-icon">EC</span><div><div class="reception-kpi-label">En consulta</div><div class="reception-kpi-value">{{ $summary['in_consultation'] }}</div></div></div>
            <div class="reception-kpi"><span class="reception-kpi-icon">NS</span><div><div class="reception-kpi-label">No Show</div><div class="reception-kpi-value">{{ $summary['no_show'] }}</div></div></div>
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
                        <div class="reception-alert-title">Nota</div>
                        <div class="reception-meta">{{ $selected->notes }}</div>
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

                <a class="reception-action" style="display: inline-flex; justify-content: center; text-decoration: none;" href="{{ $this->appointmentUrl($selected) }}">
                    Abrir cita completa
                </a>
            </aside>
        </div>
    @endif
</x-filament-panels::page>
