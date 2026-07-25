<x-filament-panels::page>
    @php
        $summary = $this->summary();
        $next = $this->nextPatient();
        $current = $this->currentConsultation();
        $pending = $this->pendingPatients();
    @endphp

    <style>
        .dq-page { color: #0f172a; display: grid; gap: 1rem; }
        .dq-kpis { display: flex; gap: .55rem; justify-content: flex-end; margin-top: -3.8rem; }
        .dq-kpi { background: #f1f5f9; border-radius: .65rem; color: #475569; font-size: .78rem; font-weight: 600; padding: .55rem .7rem; }
        .dq-grid { display: grid; gap: 1rem; grid-template-columns: minmax(0, 1fr) 24rem; }
        .dq-card, .dq-panel, .dq-patient { background: #fff; border: 1px solid #e5e7eb; border-radius: .75rem; }
        .dq-card { display: grid; gap: .85rem; padding: 1.1rem; }
        .dq-title { color: #475569; font-size: .78rem; font-weight: 650; text-transform: uppercase; }
        .dq-main { align-items: start; display: grid; gap: .8rem; grid-template-columns: auto 1fr; }
        .dq-avatar { align-items: center; background: #f1f5f9; border-radius: 999px; color: #0f172a; display: inline-flex; font-size: .85rem; font-weight: 600; height: 3rem; justify-content: center; width: 3rem; }
        .dq-name { font-size: 1.15rem; font-weight: 700; }
        .dq-meta { color: #64748b; font-size: .84rem; line-height: 1.5; }
        .dq-note { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: .55rem; font-size: .82rem; padding: .65rem; }
        .dq-actions { display: flex; flex-wrap: wrap; gap: .55rem; }
        .dq-action { background: #fff; border: 1px solid #e5e7eb; border-radius: .55rem; font-size: .8rem; font-weight: 600; min-height: 2.35rem; padding: .5rem .8rem; }
        .dq-action-primary { background: #0f766e; border-color: #0f766e; color: #fff; }
        .dq-modal { background: rgba(15, 23, 42, .45); inset: 0; position: fixed; z-index: 60; display: flex; align-items: center; justify-content: center; }
        .dq-modal-card { background: #fff; border-radius: .875rem; display: grid; gap: .75rem; padding: 1.25rem; width: min(100%, 30rem); }
        .dq-textarea { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: .5rem; font-size: .82rem; min-height: 7rem; padding: .65rem; width: 100%; }
        .dq-side { display: grid; gap: 1rem; align-content: start; }
        .dq-panel { display: grid; gap: .75rem; padding: .85rem; }
        .dq-patient { display: grid; gap: .35rem; padding: .75rem; }
        .dq-pending { display: grid; gap: .55rem; }
        @media (max-width: 1000px) { .dq-kpis { justify-content: start; margin-top: 0; flex-wrap: wrap; } .dq-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="dq-page">
        <div class="dq-kpis"><span class="dq-kpi">En espera {{ $summary['waiting'] }}</span><span class="dq-kpi">Preparando {{ $summary['preparing'] }}</span><span class="dq-kpi">Listos {{ $summary['ready'] }}</span><span class="dq-kpi">En consulta {{ $summary['in_consultation'] }}</span></div>

        <div class="dq-grid">
            <main style="display: grid; gap: 1rem;">
                <section class="dq-card">
                    <div class="dq-title">{{ $next?->status === \App\Enums\AppointmentStatus::ReadyForDoctor ? 'Proximo paciente' : ($next ? 'Proximo en flujo' : 'Proximo paciente') }}</div>
                    @if ($next)
                        <div class="dq-main"><span class="dq-avatar">{{ str($next->patient?->full_name ?? 'P')->explode(' ')->map(fn ($part) => str($part)->substr(0, 1))->take(2)->implode('') }}</span><div><div class="dq-name">{{ $next->patient?->full_name ?? 'Paciente sin nombre' }}</div><div class="dq-meta">Cita {{ $next->scheduled_at?->format('h:i a') }} · Esperando {{ $next->waitingMinutes() ?? 0 }} min</div><div>{{ $next->procedure?->name ?? 'Sin procedimiento' }}</div></div></div>
                        @if ($next->notes)<div class="dq-note">NOTA CITA · {{ $next->notes }}</div>@endif
                        @if ($next->latestAppointmentNote)<div class="dq-note">NOTA OPERATIVA · {{ $next->latestAppointmentNote->note }}</div>@endif
                        <div class="dq-actions">
                            @if ($next->status === \App\Enums\AppointmentStatus::ReadyForDoctor)
                                <button class="dq-action dq-action-primary" wire:click="transition({{ $next->id }}, '{{ \App\Enums\AppointmentStatus::InConsultation->value }}')">Iniciar consulta</button>
                            @elseif ($next->status === \App\Enums\AppointmentStatus::Preparing)
                                <button class="dq-action dq-action-primary" type="button">Solicitar actualizacion</button>
                            @else
                                <button class="dq-action dq-action-primary" type="button">Solicitar preparacion</button>
                            @endif
                            <button class="dq-action" type="button">Ver contacto</button><button class="dq-action" type="button" wire:click="openNoteModal({{ $next->id }})">Nota</button>
                        </div>
                    @else
                        <div class="dq-meta">No hay pacientes pendientes.</div>
                    @endif
                </section>

                <section class="dq-panel"><div class="dq-title">Pacientes pendientes</div><div class="dq-pending">@forelse ($pending as $appointment)<div class="dq-patient"><strong>{{ $appointment->patient?->full_name ?? 'Paciente sin nombre' }}</strong><span class="dq-meta">{{ $appointment->status->label() }} · {{ $appointment->scheduled_at?->format('h:i a') }} · {{ $appointment->procedure?->name ?? 'Sin procedimiento' }}</span></div>@empty <div class="dq-meta">No hay pacientes en espera.</div>@endforelse</div></section>
            </main>

            <aside class="dq-side">
                <section class="dq-panel"><div class="dq-title">En consulta</div>@if ($current)<div class="dq-patient"><strong>{{ $current->patient?->full_name ?? 'Paciente sin nombre' }}</strong><span class="dq-meta">{{ $current->procedure?->name ?? 'Sin procedimiento' }} · {{ $current->consultationMinutes() ?? 0 }} min</span>@if ($current->latestAppointmentNote)<span class="dq-note">{{ $current->latestAppointmentNote->note }}</span>@endif<button class="dq-action dq-action-primary" wire:click="transition({{ $current->id }}, '{{ \App\Enums\AppointmentStatus::Completed->value }}')">Finalizar consulta</button><button class="dq-action" type="button" wire:click="openNoteModal({{ $current->id }})">Nota</button></div>@else <div class="dq-meta">Sin paciente en consulta.</div>@endif</section>
                <section class="dq-panel"><div class="dq-title">Comunicacion</div><button class="dq-action" type="button">Mensaje a recepcion</button><button class="dq-action" type="button">Solicitar asistente</button></section>
            </aside>
        </div>
    </div>

    @if ($noteAppointmentId)
        <div class="dq-modal" wire:click.self="closeNoteModal"><div class="dq-modal-card"><div class="dq-title">Agregar nota operativa</div><textarea class="dq-textarea" wire:model="noteText" placeholder="Ej.: Paciente ansioso, aplicar comunicacion calmada."></textarea>@error('noteText') <div class="dq-meta" style="color: #dc2626;">{{ $message }}</div> @enderror<div class="dq-actions" style="justify-content: flex-end;"><button type="button" class="dq-action" wire:click="closeNoteModal">Cancelar</button><button type="button" class="dq-action dq-action-primary" wire:click="saveNote">Guardar nota</button></div></div></div>
    @endif
</x-filament-panels::page>
