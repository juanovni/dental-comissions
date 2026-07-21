<x-filament-panels::page>
    @php
        $periods = [
            'all' => 'Todas',
            'today' => 'Hoy',
            'upcoming' => 'Proximas',
            'past' => 'Pasadas',
        ];

        $sourceClasses = [
            'whatsapp_ai' => 'appointment-source-whatsapp',
            'whatsapp_human' => 'appointment-source-whatsapp',
            'smart_link' => 'appointment-source-web',
            'admin_manual' => 'appointment-source-manual',
            'external_provider' => 'appointment-source-external',
            'voice_call' => 'appointment-source-voice',
        ];
    @endphp

    <style>
        .appointments-agenda {
            --agenda-border: rgba(148, 163, 184, .28);
            --agenda-border-strong: rgba(100, 116, 139, .34);
            --agenda-card: rgba(255, 255, 255, .92);
            --agenda-muted: #64748b;
            --agenda-text: #0f172a;
            --agenda-soft: #f1f5f9;
            --agenda-primary: #008a70;
            --agenda-shadow: 0 1px 2px rgba(15, 23, 42, .05), 0 10px 26px rgba(15, 23, 42, .035);
            color: var(--agenda-text);
        }

        .appointments-toolbar {
            display: grid;
            gap: .9rem;
        }

        .appointments-tabs {
            display: inline-flex;
            width: max-content;
            gap: .18rem;
            padding: .28rem;
            border-radius: .7rem;
            background: #eef4f8;
        }

        .appointments-tab {
            border: 0;
            border-radius: .55rem;
            padding: .5rem .85rem;
            color: #475569;
            font-size: .9rem;
            font-weight: 650;
            line-height: 1;
            transition: all .16s ease;
        }

        .appointments-tab:hover,
        .appointments-tab.active {
            background: #fff;
            color: #020617;
            box-shadow: var(--agenda-shadow);
        }

        .appointments-filter-row {
            display: grid;
            grid-template-columns: minmax(16rem, 1fr) auto;
            gap: .65rem;
            align-items: center;
        }

        .appointments-search {
            display: flex;
            align-items: center;
            gap: .65rem;
            border: 1px solid var(--agenda-border);
            border-radius: .7rem;
            background: var(--agenda-card);
            box-shadow: var(--agenda-shadow);
            padding: .7rem .85rem;
        }

        .appointments-search svg {
            width: 1rem;
            height: 1rem;
            color: #64748b;
            flex: 0 0 auto;
        }

        .appointments-search input {
            width: 100%;
            border: 0;
            background: transparent;
            color: #334155;
            font-size: .94rem;
            outline: none;
        }

        .appointments-filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
            justify-content: flex-end;
        }

        .appointment-filter {
            position: relative;
        }

        .appointment-filter-button {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            border: 1px solid var(--agenda-border);
            border-radius: .65rem;
            background: #fff;
            box-shadow: var(--agenda-shadow);
            color: #0f172a;
            font-size: .86rem;
            font-weight: 700;
            line-height: 1;
            padding: .72rem .9rem;
        }

        .appointment-filter-button svg {
            width: .95rem;
            height: .95rem;
        }

        .appointment-filter-menu,
        .appointment-action-menu {
            position: absolute;
            z-index: 30;
            right: 0;
            min-width: 15.5rem;
            margin-top: .45rem;
            overflow: hidden;
            border: 1px solid var(--agenda-border);
            border-radius: .8rem;
            background: #fff;
            box-shadow: 0 22px 55px rgba(15, 23, 42, .13), 0 2px 4px rgba(15, 23, 42, .06);
        }

        .appointment-filter-heading {
            padding: .8rem .95rem;
            border-bottom: 1px solid rgba(226, 232, 240, .9);
            font-weight: 600;
            color: #1e293b;
        }

        .appointment-filter-option,
        .appointment-action-item {
            display: flex;
            width: 100%;
            align-items: center;
            gap: .72rem;
            padding: .74rem .95rem;
            color: #1e293b;
            text-align: left;
            font-size: .92rem;
            transition: background .14s ease;
        }

        .appointment-filter-option:hover,
        .appointment-action-item:hover {
            background: #f8fafc;
        }

        .appointment-filter-option.active {
            color: var(--agenda-primary);
            font-weight: 600;
        }

        .appointment-action-item svg {
            width: 1rem;
            height: 1rem;
            flex: 0 0 auto;
        }

        .appointment-action-item.danger {
            color: #e11d48;
        }

        .appointment-action-separator {
            height: 1px;
            background: #e2e8f0;
        }

        .appointments-meta-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #475569;
            font-size: .84rem;
            margin-top: 1.1rem;
        }

        .appointments-sort-label {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .appointments-sort-label svg {
            width: .9rem;
            height: .9rem;
        }

        .appointment-date-group {
            margin-top: 1rem;
        }

        .appointment-date-heading {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: .8rem;
            color: #475569;
            font-size: .78rem;
            font-weight: 600;
            letter-spacing: .045em;
            text-transform: uppercase;
            margin: 0 .2rem .55rem;
        }

        .appointment-date-heading::after {
            content: '';
            height: 1px;
            background: #dbe4ee;
        }

        .appointment-date-count {
            color: #3b5580;
            font-weight: 700;
        }

        .appointment-day-card {
            overflow: visible;
            border: 1px solid var(--agenda-border-strong);
            border-radius: .95rem;
            background: #fff;
        }

        .appointment-row {
            display: grid;
            grid-template-columns: 5rem 3.8rem minmax(0, 1fr) auto;
            align-items: center;
            gap: .35rem 1rem;
            min-height: 5.65rem;
            padding: .95rem 1.1rem;
        }

        .appointment-row + .appointment-row {
            border-top: 1px solid #e2e8f0;
        }

        .appointment-time {
            display: grid;
            gap: .28rem;
            color: #020617;
            font-weight: 600;
        }

        .appointment-time-main {
            line-height: 1.18;
        }

        .appointment-duration {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            color: #64748b;
            font-size: .78rem;
            font-weight: 500;
        }

        .appointment-duration svg {
            width: .78rem;
            height: .78rem;
        }

        .appointment-avatar {
            display: grid;
            width: 2.65rem;
            height: 2.65rem;
            place-items: center;
            border-radius: 999px;
            background: #eff6fb;
            color: #0f172a;
            font-size: .82rem;
            font-weight: 600;
        }

        .appointment-main {
            min-width: 0;
        }

        .appointment-title-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .45rem;
        }

        .appointment-patient {
            color: #020617;
            font-weight: 600;
        }

        .appointment-status {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            border-radius: 999px;
            padding: .18rem .48rem;
            font-size: .72rem;
            font-weight: 600;
            line-height: 1;
        }

        .appointment-status::before {
            content: '';
            width: .38rem;
            height: .38rem;
            border-radius: 999px;
            background: currentColor;
        }

        .appointment-status-success { background: #dffaf0; color: #008f68; }
        .appointment-status-warning { background: #fff7e5; color: #d97706; }
        .appointment-status-info { background: #e5f3ff; color: #0284c7; }
        .appointment-status-danger { background: #ffe8ee; color: #e11d48; }

        .appointment-line {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem;
            color: #475569;
            font-size: .85rem;
            margin-top: .25rem;
        }

        .appointment-line svg {
            width: .9rem;
            height: .9rem;
            color: #64748b;
        }

        .appointment-dot-separator {
            width: .18rem;
            height: .18rem;
            border-radius: 999px;
            background: #cbd5e1;
        }

        .appointment-side {
            display: flex;
            align-items: center;
            gap: .75rem;
            justify-content: flex-end;
        }

        .appointment-source {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border: 1px solid #dbe4ee;
            border-radius: .55rem;
            background: #fff;
            color: #0f172a;
            font-size: .8rem;
            line-height: 1;
            padding: .42rem .62rem;
            white-space: nowrap;
        }

        .appointment-source svg {
            width: .8rem;
            height: .8rem;
        }

        .appointment-source-whatsapp svg { color: #00a884; }
        .appointment-source-web svg { color: #64748b; }
        .appointment-source-manual svg { color: #475569; }
        .appointment-source-voice svg { color: #008a70; }
        .appointment-source-external svg { color: #2563eb; }

        .appointment-sync {
            width: .62rem;
            height: .62rem;
            border-radius: 999px;
            background: #eab308;
        }

        .appointment-sync.synced {
            display: grid;
            width: 1rem;
            height: 1rem;
            place-items: center;
            border: 1px solid #10b981;
            background: #ecfdf5;
            color: #10b981;
        }

        .appointment-sync.synced svg {
            width: .65rem;
            height: .65rem;
        }

        .appointment-more {
            position: relative;
        }

        .appointment-more-button {
            display: grid;
            width: 1.8rem;
            height: 1.8rem;
            place-items: center;
            border-radius: .5rem;
            color: #475569;
        }

        .appointment-more-button:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .appointment-more-button svg {
            width: 1rem;
            height: 1rem;
        }

        .appointments-empty {
            display: grid;
            min-height: 10rem;
            place-items: center;
            border: 1px dashed #cbd5e1;
            border-radius: .95rem;
            background: rgba(255, 255, 255, .72);
            margin-top: .9rem;
            text-align: center;
        }

        .appointments-empty-icon {
            width: 2rem;
            height: 2rem;
            margin: 0 auto .65rem;
            color: #64748b;
        }

        .appointments-empty-title {
            font-weight: 600;
            color: #020617;
        }

        .appointments-empty-copy {
            margin-top: .35rem;
            color: #64748b;
            font-size: .82rem;
        }

        .appointments-empty-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: .8rem;
            border: 1px solid var(--agenda-border);
            border-radius: .6rem;
            background: #fff;
            color: #0f172a;
            font-size: .82rem;
            font-weight: 600;
            padding: .5rem .8rem;
            box-shadow: var(--agenda-shadow);
        }

        @media (max-width: 900px) {
            .appointments-filter-row {
                grid-template-columns: 1fr;
            }

            .appointments-filter-actions {
                justify-content: flex-start;
            }

            .appointment-row {
                grid-template-columns: 4.6rem 3.1rem minmax(0, 1fr);
            }

            .appointment-side {
                grid-column: 2 / -1;
                justify-content: flex-start;
                padding-left: 0;
            }
        }

        @media (max-width: 640px) {
            .appointments-tabs {
                width: 100%;
                overflow-x: auto;
            }

            .appointments-tab {
                flex: 1 0 auto;
            }

            .appointment-row {
                grid-template-columns: 1fr;
                align-items: start;
                padding: 1rem;
            }

            .appointment-avatar {
                display: none;
            }

            .appointment-side {
                grid-column: auto;
                width: 100%;
                justify-content: space-between;
            }

            .appointment-filter-menu,
            .appointment-action-menu {
                left: 0;
                right: auto;
                min-width: min(17rem, calc(100vw - 2rem));
            }
        }
    </style>

    <section class="appointments-agenda">
        <div class="appointments-toolbar">
            <div class="appointments-tabs" role="tablist" aria-label="Periodo de citas">
                @foreach ($periods as $key => $label)
                    <button
                        type="button"
                        role="tab"
                        @class(['appointments-tab', 'active' => $period === $key])
                        wire:click="setPeriod('{{ $key }}')"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="appointments-filter-row">
                <label class="appointments-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21 21-4.35-4.35"/><circle cx="11" cy="11" r="7"/></svg>
                    <input
                        type="search"
                        wire:model.live.debounce.350ms="search"
                        placeholder="Buscar por paciente, doctor, procedimiento o telefono..."
                    >
                </label>

                <div class="appointments-filter-actions">
                    <div class="appointment-filter" x-data="{ open: false }" @keydown.escape.window="open = false">
                        <button class="appointment-filter-button" type="button" @click="open = ! open" :aria-expanded="open.toString()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M7 12h10M10 18h4"/><path d="M8 4v4M16 10v4M12 16v4"/></svg>
                            <span>{{ $statusFilter ? ($this->statusOptions[$statusFilter] ?? 'Estado') : 'Estado' }}</span>
                        </button>
                        <div class="appointment-filter-menu" x-cloak x-show="open" x-transition @click.outside="open = false">
                            <div class="appointment-filter-heading">Filtrar por estado</div>
                            <button class="appointment-filter-option {{ blank($statusFilter) ? 'active' : '' }}" type="button" wire:click="$set('statusFilter', null)" @click="open = false">Todos</button>
                            @foreach ($this->statusOptions as $value => $label)
                                <button class="appointment-filter-option {{ $statusFilter === $value ? 'active' : '' }}" type="button" wire:click="$set('statusFilter', '{{ $value }}')" @click="open = false">{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>

                    <div class="appointment-filter" x-data="{ open: false }" @keydown.escape.window="open = false">
                        <button class="appointment-filter-button" type="button" @click="open = ! open" :aria-expanded="open.toString()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M7 12h10M10 18h4"/><path d="M8 4v4M16 10v4M12 16v4"/></svg>
                            <span>{{ $doctorFilter ? ($this->doctorOptions[$doctorFilter] ?? 'Doctor') : 'Doctor' }}</span>
                        </button>
                        <div class="appointment-filter-menu" x-cloak x-show="open" x-transition @click.outside="open = false">
                            <div class="appointment-filter-heading">Filtrar por doctor</div>
                            <button class="appointment-filter-option {{ blank($doctorFilter) ? 'active' : '' }}" type="button" wire:click="$set('doctorFilter', null)" @click="open = false">Todos</button>
                            @foreach ($this->doctorOptions as $id => $name)
                                <button class="appointment-filter-option {{ (int) $doctorFilter === (int) $id ? 'active' : '' }}" type="button" wire:click="$set('doctorFilter', {{ $id }})" @click="open = false">{{ $name }}</button>
                            @endforeach
                        </div>
                    </div>

                    <div class="appointment-filter" x-data="{ open: false }" @keydown.escape.window="open = false">
                        <button class="appointment-filter-button" type="button" @click="open = ! open" :aria-expanded="open.toString()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M7 12h10M10 18h4"/><path d="M8 4v4M16 10v4M12 16v4"/></svg>
                            <span>{{ $patientFilter ? ($this->patientOptions[$patientFilter] ?? 'Paciente') : 'Paciente' }}</span>
                        </button>
                        <div class="appointment-filter-menu" x-cloak x-show="open" x-transition @click.outside="open = false">
                            <div class="appointment-filter-heading">Filtrar por paciente</div>
                            <button class="appointment-filter-option {{ blank($patientFilter) ? 'active' : '' }}" type="button" wire:click="$set('patientFilter', null)" @click="open = false">Todos</button>
                            @foreach ($this->patientOptions as $id => $name)
                                <button class="appointment-filter-option {{ (int) $patientFilter === (int) $id ? 'active' : '' }}" type="button" wire:click="$set('patientFilter', {{ $id }})" @click="open = false">{{ $name }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="appointments-meta-row">
            <span>{{ $this->appointmentsCount }} {{ $this->appointmentsCount === 1 ? 'cita' : 'citas' }}</span>
            <span class="appointments-sort-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M7 12h10M10 18h4"/><path d="M8 4v4M16 10v4M12 16v4"/></svg>
                Ordenado por fecha
            </span>
        </div>

        @forelse ($this->groupedAppointments as $group)
            <div class="appointment-date-group">
                <div class="appointment-date-heading">
                    <span>{{ $group['label'] }}</span>
                    <span></span>
                    <span class="appointment-date-count">{{ $group['count'] }}</span>
                </div>

                <div class="appointment-day-card">
                    @foreach ($group['appointments'] as $appointment)
                        @php
                            $patientName = $appointment->patient?->full_name ?? 'Paciente sin nombre';
                            $parts = collect(explode(' ', trim($patientName)))->filter()->values();
                            $initials = strtoupper(substr($parts->get(0, 'P'), 0, 1) . substr($parts->get(1, ''), 0, 1));
                            $statusColor = $appointment->status->color();
                            $statusClass = match ($statusColor) {
                                'success' => 'appointment-status-success',
                                'warning' => 'appointment-status-warning',
                                'info' => 'appointment-status-info',
                                'danger' => 'appointment-status-danger',
                                default => 'appointment-status-info',
                            };
                            $sourceClass = $sourceClasses[$appointment->source?->value] ?? 'appointment-source-external';
                            $viewUrl = \App\Filament\Resources\Appointments\AppointmentResource::getUrl('view', ['record' => $appointment]);
                            $editUrl = \App\Filament\Resources\Appointments\AppointmentResource::getUrl('edit', ['record' => $appointment]);
                        @endphp

                        <article class="appointment-row" wire:key="appointment-row-{{ $appointment->id }}">
                            <div class="appointment-time">
                                <div class="appointment-time-main">{{ $appointment->scheduled_at?->format('h:i') }}<br>{{ $appointment->scheduled_at?->format('a') === 'am' ? 'a.m.' : 'p.m.' }}</div>
                                <div class="appointment-duration">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                    <span>{{ $appointment->duration_minutes ?? 0 }}m</span>
                                </div>
                            </div>

                            <div class="appointment-avatar">{{ $initials ?: 'P' }}</div>

                            <div class="appointment-main">
                                <div class="appointment-title-row">
                                    <a class="appointment-patient" href="{{ $viewUrl }}">{{ $patientName }}</a>
                                    <span class="appointment-status {{ $statusClass }}">{{ $this->statusLabel($appointment->status) }}</span>
                                </div>
                                <div class="appointment-line">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11.5a3 3 0 1 0 6 0M6.5 11.5h11M8 5h8M8 19h8"/><path d="M12 2v3M12 19v3"/></svg>
                                    <span>{{ $appointment->procedure?->name ?? 'Sin procedimiento' }}</span>
                                    <span class="appointment-dot-separator"></span>
                                    <span>{{ $appointment->doctor?->name ?? 'Sin doctor' }}</span>
                                </div>
                            </div>

                            <div class="appointment-side">
                                <span class="appointment-source {{ $sourceClass }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 8h10M7 12h7M21 12a8.5 8.5 0 0 1-12.8 7.35L3 21l1.65-5.2A8.5 8.5 0 1 1 21 12Z"/></svg>
                                    <span>{{ $appointment->source?->label() ?? 'Sin origen' }}</span>
                                </span>

                                @if ($appointment->isSynced())
                                    <span class="appointment-sync synced" title="Sincronizada">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                                    </span>
                                @else
                                    <span class="appointment-sync" title="Pendiente de sincronizacion"></span>
                                @endif

                                <div class="appointment-more" x-data="{ open: false }" @keydown.escape.window="open = false">
                                    <button class="appointment-more-button" type="button" aria-label="Mas acciones" @click="open = ! open">
                                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="19" cy="12" r="1.7"/></svg>
                                    </button>

                                    <div class="appointment-action-menu" x-cloak x-show="open" x-transition @click.outside="open = false">
                                        <div class="appointment-filter-heading">Acciones</div>
                                        <a class="appointment-action-item" href="{{ $viewUrl }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                            <span>Ver detalle</span>
                                        </a>
                                        <a class="appointment-action-item" href="{{ $editUrl }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16.86 4.49 2.65 2.65M4 20h4.5L19.5 9a1.88 1.88 0 0 0 0-2.65L17.65 4.5a1.88 1.88 0 0 0-2.65 0L4 15.5V20Z"/></svg>
                                            <span>Editar</span>
                                        </a>
                                        <a class="appointment-action-item" href="{{ $editUrl }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 11a8.1 8.1 0 0 0-15.5-2M4 5v4h4M4 13a8.1 8.1 0 0 0 15.5 2M20 19v-4h-4"/></svg>
                                            <span>Reprogramar</span>
                                        </a>

                                        <div class="appointment-action-separator"></div>

                                        <button class="appointment-action-item" type="button" wire:click="completeAppointment({{ $appointment->id }})" wire:confirm="Marcar esta cita como completada?" @disabled(! $this->canUpdateStatus($appointment))>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 4 4L19 6"/><circle cx="12" cy="12" r="9"/></svg>
                                            <span>Marcar completada</span>
                                        </button>
                                        <button class="appointment-action-item" type="button" wire:click="markNoShow({{ $appointment->id }})" wire:confirm="Marcar esta cita como no asistio?" @disabled(! $this->canUpdateStatus($appointment))>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m17 8 5 5m0-5-5 5"/></svg>
                                            <span>Marcar no asistio</span>
                                        </button>

                                        <div class="appointment-action-separator"></div>

                                        <button class="appointment-action-item danger" type="button" wire:click="cancelAppointment({{ $appointment->id }})" wire:confirm="Cancelar esta cita?" @disabled(! in_array($appointment->status, [\App\Enums\AppointmentStatus::PendingConfirmation, \App\Enums\AppointmentStatus::Scheduled, \App\Enums\AppointmentStatus::Confirmed, \App\Enums\AppointmentStatus::Rescheduled], true))>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m15 9-6 6m0-6 6 6"/></svg>
                                            <span>Cancelar cita</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="appointments-empty">
                <div>
                    <svg class="appointments-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4M16 2v4M3.5 9.5h17M6 5h12a2.5 2.5 0 0 1 2.5 2.5v10A2.5 2.5 0 0 1 18 20H6a2.5 2.5 0 0 1-2.5-2.5v-10A2.5 2.5 0 0 1 6 5Z"/></svg>
                    <div class="appointments-empty-title">No hay citas con estos filtros</div>
                    <div class="appointments-empty-copy">Prueba a limpiar filtros o crea una nueva cita.</div>
                    <button class="appointments-empty-action" type="button" wire:click="clearFilters">Limpiar filtros</button>
                </div>
            </div>
        @endforelse
    </section>
</x-filament-panels::page>
