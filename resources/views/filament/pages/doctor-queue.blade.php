<x-filament-panels::page>
    @php
        $summary = $this->summary();
        $queue = $this->queueAppointments();
        $selected = $this->selectedAppointment();
        $filters = $this->queueFilters();
        $activeFilterLabel = $filters[$this->activeQueueFilter] ?? 'Todos';
        $totalPatients = $summary['waiting'] + $summary['preparing'] + $summary['ready'] + $summary['in_consultation'];
    @endphp

    <style>
        .dq-subtitle { color: #64748b; font-size: .84rem; margin: -1.05rem 0 1.25rem; }
        .dq-page { color: #0f172a; display: grid; gap: 1.25rem; }
        .dq-top { align-items: start; display: flex; gap: 1rem; justify-content: space-between; margin-top: -3.8rem; }
        .dq-tabs { display: flex; flex-wrap: wrap; gap: .55rem; justify-content: flex-end; }
        .dq-tab { background: #fff; border: 1px solid #dbe3ea; border-radius: .65rem; color: #475569; font-size: .82rem; font-weight: 600; min-height: 2.35rem; padding: .5rem .85rem; transition: border-color .16s ease, color .16s ease, background .16s ease; }
        .dq-tab-active { background: #ecfdf5; border-color: #14b8a6; color: #0f766e; }
        .dq-grid { display: grid; gap: 1.35rem; grid-template-columns: minmax(0, 1fr) 24.5rem; }
        .dq-main-stack, .dq-side { align-content: start; display: grid; gap: 1.25rem; }
        .dq-card, .dq-panel, .dq-patient { background: #fff; border: 1px solid #e5e7eb; border-radius: .85rem; }
        .dq-card { overflow: hidden; }
        .dq-card-head, .dq-panel-head { align-items: center; display: flex; gap: .75rem; justify-content: space-between; }
        .dq-card-head { border-bottom: 1px solid #e5e7eb; padding: .9rem 1.25rem; }
        .dq-card-body { display: grid; gap: 1.4rem; padding: 1.6rem 1.6rem 1.25rem; }
        .dq-title { color: #0f172a; font-size: .9rem; font-weight: 600; line-height: 1.25; }
        .dq-title-muted { color: #64748b; font-size: .78rem; font-weight: 600; letter-spacing: .02em; text-transform: uppercase; }
        .dq-status { background: #ecfdf5; border: 1px solid #99f6e4; border-radius: 999px; color: #0f766e; font-size: .75rem; font-weight: 600; padding: .25rem .6rem; }
        .dq-main { align-items: center; display: grid; gap: 1rem; grid-template-columns: auto 1fr; }
        .dq-avatar { align-items: center; background: #f1f5f9; border: 1px solid #dbe3ea; border-radius: 999px; color: #0f766e; display: inline-flex; font-size: .9rem; font-weight: 600; height: 3.95rem; justify-content: center; width: 3.95rem; }
        .dq-name { color: #0f172a; font-size: 1.45rem; font-weight: 600; line-height: 1.15; }
        .dq-procedure { color: #475569; font-size: .95rem; margin-top: .35rem; }
        .dq-meta { color: #64748b; font-size: .84rem; line-height: 1.5; }
        .dq-meta-row { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: .75rem; }
        .dq-wait { color: #64748b; font-weight: 600; }
        .dq-wait-warning { color: #ca8a04; }
        .dq-wait-critical { color: #dc2626; }
        .dq-actions { display: flex; flex-wrap: wrap; gap: .6rem; }
        .dq-action { align-items: center; background: #fff; border: 1px solid #dbe3ea; border-radius: .48rem; color: #0f172a; display: inline-flex; font-size: .78rem; font-weight: 500; gap: .4rem; justify-content: center; line-height: 1; min-height: 2.05rem; padding: .56rem .72rem; text-decoration: none; transition: background-color .16s ease, border-color .16s ease, filter .16s ease; }
        .dq-action svg { flex: 0 0 auto; height: 1rem; width: 1rem; }
        .dq-action:hover { filter: brightness(.98); }
        .dq-action-primary { background: oklch(55% .12 185); border-color: oklch(55% .12 185); color: #ffffff; }
        .dq-notes { border-top: 1px solid #e5e7eb; display: grid; gap: .75rem; padding-top: 1.25rem; }
        .dq-notes-head { align-items: center; color: #0f172a; display: flex; gap: .45rem; font-size: .9rem; font-weight: 600; }
        .dq-note { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: .65rem; display: grid; gap: .4rem; padding: .75rem .85rem; }
        .dq-note-head { align-items: center; display: flex; gap: .75rem; justify-content: space-between; }
        .dq-note-author { color: #0f766e; font-size: .76rem; font-weight: 600; letter-spacing: .02em; text-transform: uppercase; }
        .dq-note-time { color: #64748b; font-size: .76rem; font-weight: 600; }
        .dq-note-body { color: #0f172a; font-size: .86rem; line-height: 1.45; }
        .dq-panel { display: grid; gap: .95rem; padding: 1.05rem; }
        .dq-panel-count { color: #64748b; font-size: .8rem; font-weight: 600; }
        .dq-pending { display: grid; gap: .6rem; }
        .dq-patient { align-items: center; display: grid; gap: .75rem; grid-template-columns: auto 1fr auto; padding: .8rem; text-align: left; width: 100%; }
        .dq-patient-active { background: #ecfdf5; border-color: #14b8a6; }
        .dq-patient-avatar { align-items: center; background: #f1f5f9; border-radius: 999px; color: #475569; display: inline-flex; font-size: .75rem; font-weight: 600; height: 2.5rem; justify-content: center; width: 2.5rem; }
        .dq-patient-name { color: #0f172a; font-size: .9rem; font-weight: 600; line-height: 1.2; }
        .dq-patient-meta { color: #64748b; font-size: .78rem; line-height: 1.35; }
        .dq-patient-marker { color: #14b8a6; font-size: 1.1rem; font-weight: 600; }
        .dq-modal { align-items: center; background: rgba(15, 23, 42, .45); display: flex; inset: 0; justify-content: center; position: fixed; z-index: 60; }
        .dq-modal-card { background: #fff; border-radius: .875rem; display: grid; gap: .75rem; padding: 1.25rem; width: min(100%, 30rem); }
        .dq-textarea { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: .5rem; font-size: .82rem; min-height: 7rem; padding: .65rem; width: 100%; }
        @media (max-width: 1000px) { .dq-top { margin-top: 0; } .dq-grid { grid-template-columns: 1fr; } .dq-tabs { justify-content: start; } }
        .dark .dq-subtitle, .dark .dq-meta, .dark .dq-panel-count, .dark .dq-note-time, .dark .dq-patient-meta { color: #9fb6bd; }
        .dark .dq-page, .dark .dq-title, .dark .dq-name, .dark .dq-note-body, .dark .dq-patient-name, .dark .dq-action { color: #f8fafc; }
        .dark .dq-tab, .dark .dq-card, .dark .dq-panel, .dark .dq-patient, .dark .dq-action, .dark .dq-modal-card { background: #101c1c; border-color: rgba(148, 163, 184, .22); }
        .dark .dq-card-head, .dark .dq-notes { border-color: rgba(148, 163, 184, .22); }
        .dark .dq-tab-active, .dark .dq-patient-active { background: #122d29; border-color: rgba(20, 184, 166, .75); color: #2dd4bf; }
        .dark .dq-avatar, .dark .dq-patient-avatar, .dark .dq-note, .dark .dq-textarea { background: #142323; border-color: rgba(148, 163, 184, .22); }
        .dark .dq-procedure, .dark .dq-title-muted { color: #b7cbd1; }
        .dark .dq-status { background: rgba(20, 184, 166, .12); border-color: rgba(20, 184, 166, .35); color: #2dd4bf; }
        .dark .dq-wait-warning { color: #fbbf24; }
        .dark .dq-wait-critical { color: #f87171; }
        .dark .dq-action-primary { background: oklch(55% .12 185); border-color: oklch(55% .12 185); color: #ffffff; }
    </style>

    <div class="dq-page">
        <p class="dq-subtitle">{{ $totalPatients }} pacientes pendientes · {{ $summary['in_consultation'] > 0 ? $summary['in_consultation'].' en consulta' : 'sin consulta activa' }}</p>

        <div class="dq-top">
            <span></span>
            <div class="dq-tabs" role="tablist" aria-label="Filtro de cola del dia">
                @foreach ($filters as $filter => $label)
                    <button type="button" class="dq-tab {{ $this->activeQueueFilter === $filter ? 'dq-tab-active' : '' }}" wire:click="selectQueueFilter('{{ $filter }}')">
                        {{ $label }} {{ $this->countForFilter($filter) }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="dq-grid">
            <main class="dq-main-stack">
                <section class="dq-card">
                    <header class="dq-card-head">
                        <div class="dq-title">Siguiente a atender</div>
                        @if ($selected)
                            <span class="dq-status">{{ $selected->status->label() }}</span>
                        @endif
                    </header>

                    <div class="dq-card-body">
                        @if ($selected)
                            <div class="dq-main">
                                <span class="dq-avatar">{{ str($selected->patient?->full_name ?? 'P')->explode(' ')->map(fn ($part) => str($part)->substr(0, 1))->take(2)->implode('') }}</span>
                                <div>
                                    <div class="dq-name">{{ $selected->patient?->full_name ?? 'Paciente sin nombre' }}</div>
                                    <div class="dq-procedure">{{ $selected->procedure?->name ?? 'Sin procedimiento' }}</div>
                                    <div class="dq-meta dq-meta-row">
                                        <span>Cita {{ $selected->scheduled_at?->format('h:i a') }}</span>
                                        @php($selectedWaitingMinutes = $selected->waitingMinutes() ?? 0)
                                        <span class="dq-wait @if ($selectedWaitingMinutes >= 20) dq-wait-critical @elseif ($selectedWaitingMinutes >= 10) dq-wait-warning @endif">Esperando {{ $selectedWaitingMinutes }} min</span>
                                    </div>
                                </div>
                            </div>

                            <div class="dq-actions">
                                @foreach ($this->availableTransitions($selected) as $status => $label)
                                    <button class="dq-action dq-action-primary" wire:click="transition({{ $selected->id }}, '{{ $status }}')">
                                        @if ($status === \App\Enums\AppointmentStatus::InConsultation->value)
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-play size-4" aria-hidden="true"><path d="M5 5a2 2 0 0 1 3.008-1.728l11.997 6.998a2 2 0 0 1 .003 3.458l-12 7A2 2 0 0 1 5 19z"></path></svg>
                                        @endif
                                        {{ $label }}
                                    </button>
                                @endforeach
                                <button class="dq-action" type="button" wire:click="openNoteModal({{ $selected->id }})">Agregar nota</button>
                            </div>

                            <section class="dq-notes">
                                @php($notesCount = $selected->appointmentNotes->count() + ($selected->notes ? 1 : 0))
                                <div class="dq-notes-head">Notas operativas <span class="dq-meta">{{ $notesCount }}</span></div>
                                @if ($selected->notes)
                                    <article class="dq-note">
                                        <div class="dq-note-head"><span class="dq-note-author">Nota cita</span><span class="dq-note-time">Cita</span></div>
                                        <div class="dq-note-body">{{ $selected->notes }}</div>
                                    </article>
                                @endif
                                @foreach ($selected->appointmentNotes as $note)
                                    <article class="dq-note">
                                        <div class="dq-note-head">
                                            <span class="dq-note-author">{{ $this->noteOwnerLabel($note) }}</span>
                                            <span class="dq-note-time">hace {{ (int) ($note->created_at?->diffInMinutes(now()) ?? 0) }} min</span>
                                        </div>
                                        <div class="dq-note-body">{{ $note->note }}</div>
                                    </article>
                                @endforeach
                                @if ($notesCount === 0)
                                    <div class="dq-meta">Sin notas operativas.</div>
                                @endif
                            </section>
                        @else
                            <div class="dq-meta">No hay pacientes pendientes.</div>
                        @endif
                    </div>
                </section>
            </main>

            <aside class="dq-side">
                <section class="dq-panel">
                    <header class="dq-panel-head">
                        <div class="dq-title">Cola del dia</div>
                        <span class="dq-panel-count">{{ $queue->count() }} pacientes</span>
                    </header>
                    <div class="dq-title-muted">{{ $activeFilterLabel }} {{ $queue->count() }}</div>
                    <div class="dq-pending">
                        @forelse ($queue as $appointment)
                            @php($waitingMinutes = $appointment->waitingMinutes() ?? 0)
                            <button type="button" class="dq-patient {{ $selected?->id === $appointment->id ? 'dq-patient-active' : '' }}" wire:click="selectAppointment({{ $appointment->id }})">
                                <span class="dq-patient-avatar">{{ str($appointment->patient?->full_name ?? 'P')->explode(' ')->map(fn ($part) => str($part)->substr(0, 1))->take(2)->implode('') }}</span>
                                <span>
                                    <span class="dq-patient-name">{{ $appointment->patient?->full_name ?? 'Paciente sin nombre' }}</span>
                                    <span class="dq-patient-meta">{{ $appointment->procedure?->name ?? 'Sin procedimiento' }} · {{ $appointment->scheduled_at?->format('h:i a') }} · <span class="dq-wait @if ($waitingMinutes >= 20) dq-wait-critical @elseif ($waitingMinutes >= 10) dq-wait-warning @endif">{{ $waitingMinutes }} min</span> · {{ $appointment->appointmentNotes->count() }} notas</span>
                                </span>
                                <span class="dq-patient-marker">{{ $selected?->id === $appointment->id ? '•' : '' }}</span>
                            </button>
                        @empty
                            <div class="dq-meta">No hay pacientes en esta vista.</div>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>
    </div>

    @if ($noteAppointmentId)
        <div class="dq-modal" wire:click.self="closeNoteModal">
            <div class="dq-modal-card">
                <div class="dq-title">Agregar nota operativa</div>
                <textarea class="dq-textarea" wire:model="noteText" placeholder="Ej.: Paciente ansioso, aplicar comunicacion calmada."></textarea>
                @error('noteText') <div class="dq-meta" style="color: #dc2626;">{{ $message }}</div> @enderror
                <div class="dq-actions" style="justify-content: flex-end;"><button type="button" class="dq-action" wire:click="closeNoteModal">Cancelar</button><button type="button" class="dq-action dq-action-primary" wire:click="saveNote">Guardar nota</button></div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
