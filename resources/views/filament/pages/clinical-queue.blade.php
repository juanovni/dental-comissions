<x-filament-panels::page>
    @php
        $summary = $this->summary();
        $columns = $this->columns();
        $selected = $this->selectedAppointment();
    @endphp

    <style>
        .cq-subtitle {
            color: #64748b;
            font-size: .84rem;
            margin: -1.05rem 0 1.25rem;
        }
        @media (min-width: 900px) {
            .cq-subtitle {
                margin-bottom: 1rem;
                max-width: 28rem;
            }
        }
        .cq-page { color: #0f172a; display: grid; gap: 1rem; }
        .cq-toolbar { display: flex; justify-content: flex-end; margin-top: -3.8rem; }
        .cq-search { background: #fff; border: 1px solid #e5e7eb; border-radius: .65rem; box-shadow: 0 1px 2px rgba(15, 23, 42, .05); font-size: .84rem; height: 2.65rem; outline: none; padding: 0 .85rem; width: min(100%, 24rem); }
        .cq-kpis { display: grid; gap: .75rem; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .cq-kpi, .cq-alerts, .cq-column, .cq-card, .cq-detail-card { background: #fff; border: 1px solid #e5e7eb; border-radius: .75rem; }
        .cq-kpi { align-items: center; display: flex; gap: .75rem; padding: .85rem; }
        .cq-kpi-icon { align-items: center; background: #eef8f8; border-radius: .65rem; color: #0f766e; display: inline-flex; height: 2.35rem; justify-content: center; width: 2.35rem; }
        .cq-kpi-icon svg { height: 1.15rem; width: 1.15rem; }
        .cq-kpi-label { color: #64748b; font-size: .74rem; font-weight: 500; }
        .cq-kpi-value { color: #0f172a; font-size: 1.1rem; font-weight: 650; }
        .cq-kpi-count { color: #0f172a; font-size: 1.1rem; font-weight: 650; }
        .cq-alerts { background: #fff7f7; border-color: #fecaca; color: #dc2626; padding: .85rem; }
        .cq-title { align-items: center; display: flex; font-size: .86rem; font-weight: 600; justify-content: space-between; }
        .cq-alert-list { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: .65rem; }
        .cq-alert-chip { background: #fff; border: 1px solid #fecaca; border-radius: .5rem; font-size: .78rem; padding: .35rem .55rem; }
        .cq-board { display: grid; gap: .75rem; grid-template-columns: repeat(4, minmax(15rem, 1fr)); overflow-x: auto; }
        .cq-column { min-height: 32rem; overflow: hidden; }
        .cq-column-head { border-bottom: 1px solid #e5e7eb; padding: .75rem .85rem; }
        .cq-count { background: #f1f5f9; border-radius: 999px; color: #334155; font-size: .75rem; padding: .1rem .45rem; }
        .cq-card-list { display: grid; gap: .55rem; padding: .65rem; }
        .cq-card { cursor: pointer; display: grid; gap: .55rem; padding: .7rem; text-align: left; width: 100%; }
        .cq-card:hover { background: #f8fafc; border-color: #cbd5e1; }
        .cq-card-head { align-items: start; display: grid; gap: .6rem; grid-template-columns: auto 1fr; }
        .cq-avatar { align-items: center; background: #f1f5f9; border-radius: 999px; color: #0f172a; display: inline-flex; font-size: .75rem; font-weight: 600; height: 2.25rem; justify-content: center; width: 2.25rem; }
        .cq-name { font-size: .9rem; font-weight: 650; }
        .cq-meta { color: #64748b; font-size: .78rem; line-height: 1.45; }
        .cq-badge { background: #ecfdf5; border-radius: .4rem; color: #047857; display: inline-flex; font-size: .72rem; font-weight: 600; padding: .2rem .45rem; width: fit-content; }
        .cq-wait { color: #b45309; font-size: .75rem; font-weight: 600; justify-self: end; }
        .cq-card-foot { align-items: center; display: flex; justify-content: space-between; }
        .cq-drawer { background: rgba(15, 23, 42, .45); inset: 0; position: fixed; z-index: 60; }
        .cq-drawer-panel { background: #fff; border-left: 1px solid #e5e7eb; display: grid; gap: .85rem; height: 100%; margin-left: auto; overflow-y: auto; padding: 1rem; width: min(100%, 28rem); }
        .cq-drawer-head { align-items: start; display: flex; gap: .75rem; justify-content: space-between; }
        .cq-close { border: 1px solid #e5e7eb; border-radius: 999px; color: #64748b; height: 1.85rem; width: 1.85rem; }
        .cq-detail-card { display: grid; gap: .55rem; padding: .85rem; }
        .cq-fact { display: grid; gap: .75rem; grid-template-columns: 1fr 1fr; }
        .cq-fact span:first-child { color: #64748b; font-size: .8rem; }
        .cq-fact span:last-child { font-size: .82rem; font-weight: 600; text-align: right; }
        .cq-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
        .cq-action { background: #fff; border: 1px solid #e5e7eb; border-radius: .5rem; font-size: .78rem; font-weight: 600; min-height: 2.15rem; padding: .45rem .65rem; }
        .cq-action-primary { background: #0f766e; border-color: #0f766e; color: #fff; }
        .cq-note-preview { background: #f8fafc; border-radius: .5rem; color: #475569; font-size: .76rem; padding: .45rem .55rem; }
        .cq-modal-card { background: #fff; border-radius: .875rem; display: grid; gap: .75rem; padding: 1.25rem; width: min(100%, 30rem); }
        .cq-textarea { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: .5rem; font-size: .82rem; min-height: 7rem; padding: .65rem; width: 100%; }
        @media (max-width: 900px) { .cq-toolbar { margin-top: 0; } .cq-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } .cq-board { grid-template-columns: repeat(4, 16rem); } }
    </style>

    <div class="cq-page">
        <p class="cq-subtitle">Asistencia a doctores · pacientes en preparacion y consulta</p>
        <div class="cq-toolbar"><input class="cq-search" type="search" wire:model.live.debounce.350ms="search" placeholder="Buscar paciente..."></div>

        <div class="cq-kpis">
            <div class="cq-kpi"><span class="cq-kpi-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path></svg></span><div><div class="cq-kpi-label">En espera</div><div class="cq-kpi-count">{{ $summary[\App\Enums\AppointmentStatus::CheckedIn->value] }}</div></div></div>
            <div class="cq-kpi"><span class="cq-kpi-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"></path></svg></span><div><div class="cq-kpi-label">En preparacion</div><div class="cq-kpi-count">{{ $summary[\App\Enums\AppointmentStatus::Preparing->value] }}</div></div></div>
            <div class="cq-kpi"><span class="cq-kpi-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path></svg></span><div><div class="cq-kpi-label">Listo para doctor</div><div class="cq-kpi-count">{{ $summary[\App\Enums\AppointmentStatus::ReadyForDoctor->value] }}</div></div></div>
            <div class="cq-kpi"><span class="cq-kpi-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"></path></svg></span><div><div class="cq-kpi-label">En consulta</div><div class="cq-kpi-count">{{ $summary[\App\Enums\AppointmentStatus::InConsultation->value] }}</div></div></div>
        </div>

        @if ($this->alerts()->isNotEmpty())
            <div class="cq-alerts"><div class="cq-title">Alertas</div><div class="cq-alert-list">@foreach ($this->alerts() as $alert)<span class="cq-alert-chip">{{ $alert }}</span>@endforeach</div></div>
        @endif

        <div class="cq-board">
            @foreach ($columns as $status => $label)
                @php($cards = $this->cards($status))
                <section class="cq-column">
                    <div class="cq-column-head cq-title"><span>{{ $label }}</span><span class="cq-count">{{ $cards->count() }}</span></div>
                    <div class="cq-card-list">
                        @forelse ($cards as $appointment)
                            <button type="button" class="cq-card" wire:click="selectAppointment({{ $appointment->id }})">
                                <span class="cq-card-head"><span class="cq-avatar">{{ str($appointment->patient?->full_name ?? 'P')->explode(' ')->map(fn ($part) => str($part)->substr(0, 1))->take(2)->implode('') }}</span><span><span class="cq-name">{{ $appointment->patient?->full_name ?? 'Paciente sin nombre' }}</span><span class="cq-meta">{{ $appointment->scheduled_at?->format('h:i a') }} · {{ $appointment->doctor?->name ?? 'Sin doctor' }}</span><span class="cq-meta">{{ $appointment->procedure?->name ?? 'Sin procedimiento' }}</span></span></span>
                                <span class="cq-card-foot"><span class="cq-badge">{{ $appointment->status->label() }}</span>@if ($appointment->waitingMinutes() !== null)<span class="cq-wait">{{ $appointment->waitingMinutes() }} min</span>@endif</span>
                                @if ($appointment->latestAppointmentNote)<span class="cq-note-preview">{{ str($appointment->latestAppointmentNote->note)->limit(70) }}</span>@endif
                            </button>
                        @empty
                            <div class="cq-meta" style="padding: .8rem; text-align: center;">Sin pacientes.</div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    @if ($selected)
        <div class="cq-drawer" wire:click.self="closeDetail"><aside class="cq-drawer-panel">
            <div class="cq-drawer-head"><div style="display: flex; gap: .75rem;"><span class="cq-avatar">{{ str($selected->patient?->full_name ?? 'P')->explode(' ')->map(fn ($part) => str($part)->substr(0, 1))->take(2)->implode('') }}</span><div><div class="cq-name">{{ $selected->patient?->full_name ?? 'Paciente sin nombre' }}</div><div class="cq-meta">{{ $selected->patient?->phone ?? 'Sin telefono' }}</div><span class="cq-badge" style="margin-top: .35rem;">{{ $selected->status->label() }}</span></div></div><button type="button" class="cq-close" wire:click="closeDetail">×</button></div>
            <div class="cq-detail-card"><div class="cq-title">Cita</div><div class="cq-fact"><span>Hora</span><span>{{ $selected->scheduled_at?->format('h:i a') ?? '-' }}</span></div><div class="cq-fact"><span>Doctor</span><span>{{ $selected->doctor?->name ?? '-' }}</span></div><div class="cq-fact"><span>Procedimiento</span><span>{{ $selected->procedure?->name ?? '-' }}</span></div><div class="cq-fact"><span>Esperando</span><span>{{ $selected->waitingMinutes() !== null ? $selected->waitingMinutes().' min' : '-' }}</span></div></div>
            @if ($selected->notes)<div class="cq-detail-card"><div class="cq-title">Nota</div><div class="cq-meta">{{ $selected->notes }}</div></div>@endif
            @if ($selected->latestAppointmentNote)<div class="cq-detail-card"><div class="cq-title">Ultima nota operativa</div><div class="cq-meta">{{ $selected->latestAppointmentNote->note }}</div></div>@endif
            <div class="cq-detail-card"><div class="cq-title">Siguiente paso</div><div class="cq-actions">@foreach ($this->availableTransitions($selected) as $status => $label)<button type="button" class="cq-action @if ($loop->first) cq-action-primary @endif" wire:click="transition({{ $selected->id }}, '{{ $status }}')">{{ $label }}</button>@endforeach</div></div>
            <button type="button" class="cq-action" wire:click="openNoteModal({{ $selected->id }})">Nota</button>
        </aside></div>
    @endif

    @if ($noteAppointmentId)
        <div class="cq-drawer" wire:click.self="closeNoteModal" style="display: flex; align-items: center; justify-content: center;"><div class="cq-modal-card"><div class="cq-title">Agregar nota operativa</div><textarea class="cq-textarea" wire:model="noteText" placeholder="Ej.: Paciente ansioso, aplicar comunicacion calmada."></textarea>@error('noteText') <div class="cq-meta" style="color: #dc2626;">{{ $message }}</div> @enderror<div class="cq-actions" style="justify-content: flex-end;"><button type="button" class="cq-action" wire:click="closeNoteModal">Cancelar</button><button type="button" class="cq-action cq-action-primary" wire:click="saveNote">Guardar nota</button></div></div></div>
    @endif
</x-filament-panels::page>
