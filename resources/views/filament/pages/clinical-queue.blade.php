<x-filament-panels::page>
    @php
        $summary = $this->summary();
        $columns = $this->columns();
        $selected = $this->selectedAppointment();
        $alerts = $this->alerts();
        $alertSummary = $this->alertSummary();
        $patientFlowSettings = app(\App\Services\SocialCrmSettingsService::class);
        $waitWarningMinutes = $patientFlowSettings->patientFlowWaitWarningMinutes();
        $waitCriticalMinutes = $patientFlowSettings->patientFlowWaitCriticalMinutes();
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
        .cq-toolbar { align-items: center; display: flex; gap: .75rem; justify-content: flex-end; margin-bottom: .9rem; margin-top: -4.05rem; }
        .cq-search { background: #fff; border: 1px solid #e5e7eb; border-radius: .5rem; box-shadow: none; color: #0f172a; font-size: .82rem; height: 2.35rem; outline: none; padding: .45rem .75rem; width: min(100%, 24rem); }
        .cq-kpis { display: grid; gap: .75rem; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .cq-kpi, .cq-alerts, .cq-column, .cq-card, .cq-detail-card { background: #fff; border: 1px solid #e5e7eb; border-radius: .75rem; }
        .cq-kpi { align-items: center; display: flex; gap: .75rem; padding: .85rem; }
        .cq-kpi-icon { align-items: center; background: #eef8f8; border-radius: .65rem; color: #0f766e; display: inline-flex; height: 2.35rem; justify-content: center; width: 2.35rem; }
        .cq-kpi-icon svg { height: 1.15rem; width: 1.15rem; }
        .cq-kpi-label { color: #64748b; font-size: .74rem; font-weight: 500; }
        .cq-kpi-value { color: #0f172a; font-size: 1.1rem; font-weight: 650; }
        .cq-kpi-count { color: #0f172a; font-size: 1.1rem; font-weight: 650; }
        .cq-alerts { background: #fff; border-color: #e5e7eb; color: #0f172a; overflow: hidden; padding: 0; }
        .cq-alerts-head { align-items: center; background: #fff; border-bottom: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; gap: .65rem; padding: .85rem 1rem; }
        .cq-alerts-icon { align-items: center; background: #fff7f7; border: 1px solid #fecaca; border-radius: .65rem; color: #dc2626; display: inline-flex; height: 2rem; justify-content: center; width: 2rem; }
        .cq-alerts-icon svg { height: 1rem; width: 1rem; }
        .cq-alerts-heading { color: #0f172a; font-size: .92rem; font-weight: 600; }
        .cq-alerts-hint { color: #64748b; font-size: .78rem; }
        .cq-alerts-toggle { align-items: center; background: transparent; border: 0; color: #64748b; display: inline-flex; height: 2rem; justify-content: center; margin-left: auto; width: 2rem; }
        .cq-alerts-toggle svg { height: 1rem; width: 1rem; }
        .cq-alerts-toggle.is-collapsed svg { transform: rotate(180deg); }
        .cq-alerts-badge { border-radius: 999px; display: inline-flex; font-size: .72rem; font-weight: 600; padding: .3rem .6rem; }
        .cq-alerts-badge-critical { background: #ff6b63; color: #111827; }
        .cq-alerts-badge-warning { background: #fbbf24; color: #111827; }
        .cq-alerts-body { display: grid; gap: .55rem; padding: .75rem 1rem 1rem; }
        .cq-alert-card { align-items: center; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: .65rem; color: inherit; cursor: pointer; display: grid; gap: .75rem; grid-template-columns: minmax(0, 1fr) auto; padding: .75rem .85rem; text-align: left; transition: border-color .14s ease, background .14s ease, box-shadow .14s ease; width: 100%; }
        .cq-alert-card:hover { box-shadow: 0 8px 18px rgba(15, 23, 42, .08); }
        .cq-alert-card-critical { background: #fff; border-color: #dc2626; color: #111827; }
        .cq-alert-card-warning { background: #fff; border-color: #ca8a04; color: #111827; }
        .cq-alert-card:focus-visible { outline: 2px solid #14b8a6; outline-offset: 2px; }
        .cq-alert-main { align-items: start; display: grid; gap: .6rem; grid-template-columns: auto minmax(0, 1fr); }
        .cq-alert-dot { border-radius: 999px; height: .55rem; margin-top: .35rem; width: .55rem; }
        .cq-alert-dot-critical { background: #ff6b63; }
        .cq-alert-dot-warning { background: #fbbf24; }
        .cq-alert-name { display: block; font-size: .9rem; font-weight: 600; line-height: 1.25; }
        .cq-alert-meta { color: #cbd5e1; display: block; font-size: .78rem; margin-top: .35rem; }
        .cq-alert-card-critical .cq-alert-meta, .cq-alert-card-critical .cq-alert-column, .cq-alert-card-warning .cq-alert-meta, .cq-alert-card-warning .cq-alert-column { color: #64748b; }
        .cq-alert-side { align-items: center; align-self: center; display: flex; gap: .45rem; justify-content: flex-end; }
        .cq-alert-column { color: #cbd5e1; font-size: .78rem; }
        .cq-alert-time { align-items: center; border-radius: 999px; display: inline-flex; font-size: .78rem; font-weight: 600; gap: .3rem; padding: .28rem .55rem; }
        .cq-alert-time svg { height: .85rem; width: .85rem; }
        .cq-alert-time-critical { background: #ff6b63; color: #111827; }
        .cq-alert-time-warning { background: #fbbf24; color: #111827; }
        .cq-title { align-items: center; display: flex; font-size: .86rem; font-weight: 600; justify-content: space-between; }
        .cq-alert-list { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: .65rem; }
        .cq-alert-chip { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: .5rem; color: #475569; font-size: .78rem; padding: .35rem .55rem; }
        .cq-alert-chip-warning { background: #fffbeb; border-color: #fde68a; color: #92400e; }
        .cq-alert-chip-critical { background: #fff7f7; border-color: #fecaca; color: #dc2626; }
        .cq-alert-chip-neutral { background: #f8fafc; border-color: #e5e7eb; color: #475569; }
        .cq-board { display: grid; gap: .75rem; grid-template-columns: repeat(4, minmax(15rem, 1fr)); overflow-x: auto; }
        .cq-column { min-height: 32rem; overflow: hidden; }
        .cq-column-head { border-bottom: 1px solid #e5e7eb; padding: .75rem .85rem; }
        .cq-count { background: #f1f5f9; border-radius: 999px; color: #334155; font-size: .75rem; padding: .1rem .45rem; }
        .cq-card-list { display: grid; gap: .55rem; padding: .65rem .65rem .65rem 0.65rem; }
        .cq-card-shell { position: relative; }
        .cq-card { cursor: pointer; display: grid; gap: .55rem; padding: .7rem; text-align: left; width: 100%; }
        .cq-card:hover { background: #f8fafc; border-color: #cbd5e1; }
        .cq-quick-action { align-items: center; background: #fff; border: 1px solid #e5e7eb; border-radius: .55rem; box-shadow: 0 8px 18px rgba(15, 23, 42, .1); color: #0f172a; display: inline-flex; height: 2.35rem; justify-content: center; opacity: 0; pointer-events: none; position: absolute; right: .7rem; top: .7rem; transform: translateX(.25rem); transition: opacity .14s ease, transform .14s ease, background .14s ease, border-color .14s ease; width: 2.35rem; z-index: 2; }
        .cq-quick-action svg { height: 1rem; width: 1rem; }
        .cq-card-shell:hover .cq-quick-action, .cq-card-shell:focus-within .cq-quick-action { opacity: 1; pointer-events: auto; transform: translateX(0); }
        .cq-quick-action:hover { background: #f9fafb; border-color: #d1d5db; }
        .cq-card-head { align-items: start; display: grid; gap: .6rem; grid-template-columns: auto 1fr; }
        .cq-card-body { display: grid; gap: .18rem; }
        .cq-card-time { align-items: center; display: flex; gap: .3rem; }
        .cq-card-time svg { height: .9rem; width: .9rem; }
        .cq-avatar { align-items: center; background: #f1f5f9; border-radius: 999px; color: #0f172a; display: inline-flex; font-size: .75rem; font-weight: 600; height: 2.25rem; justify-content: center; width: 2.25rem; }
        .cq-name { font-size: .9rem; font-weight: 650; }
        .cq-meta { color: #64748b; font-size: .78rem; line-height: 1.45; }
        .cq-badge { background: #ecfdf5; border-radius: .4rem; color: #047857; display: inline-flex; font-size: .72rem; font-weight: 600; padding: .2rem .45rem; width: fit-content; }
        .cq-wait { align-items: center; color: #64748b; display: inline-flex; font-size: .75rem; font-weight: 600; gap: .25rem; justify-self: end; }
        .cq-wait svg { height: .85rem; width: .85rem; }
        .cq-wait-warning { color: #ca8a04; }
        .cq-wait-critical { color: #dc2626; }
        .cq-card-foot { align-items: center; display: flex; justify-content: space-between; }
        .cq-drawer { background: rgba(15, 23, 42, .22); inset: 0; position: fixed; z-index: 60; }
        .cq-drawer-panel { background: #fff; border: 1px solid #e5e7eb; border-radius: .875rem; box-shadow: 0 8px 20px rgba(15, 23, 42, .08); color: #0f172a; display: flex; flex-direction: column; max-height: calc(100vh - 2rem); overflow: hidden; position: fixed; right: 1rem; top: 1rem; width: min(38rem, calc(100vw - 2rem)); }
        .cq-drawer-head { align-items: flex-start; border-bottom: 1px solid #e5e7eb; display: flex; flex: 0 0 auto; gap: .75rem; justify-content: space-between; padding: 1rem; }
        .cq-drawer-body { align-content: start; display: grid; flex: 1 1 auto; gap: .8rem; overflow-y: auto; padding: 1rem; }
        .cq-drawer-footer { border-top: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; gap: .45rem; padding: .9rem 1rem; }
        .cq-drawer-footer .cq-action { flex: 1; justify-content: center; min-height: 2.25rem; }
        .cq-close { align-items: center; background: transparent; border: 1px solid transparent; border-radius: .45rem; color: #64748b; cursor: pointer; display: inline-flex; flex: 0 0 auto; font-size: 1rem; font-weight: 500; height: 2rem; justify-content: center; line-height: 1; transition: .14s ease; width: 2rem; }
        .cq-close:hover { background: #f9fafb; border-color: #e5e7eb; color: #0f172a; }
        .cq-detail-card { background: #fff; border: 1px solid #e5e7eb; border-radius: .75rem; display: grid; gap: .55rem; padding: .85rem; }
        .cq-fact { display: grid; gap: .75rem; grid-template-columns: 1fr 1fr; }
        .cq-fact span:first-child { color: #64748b; font-size: .8rem; }
        .cq-fact span:last-child { font-size: .82rem; font-weight: 600; text-align: right; }
        .cq-actions { align-items: center; display: flex; flex-wrap: wrap; gap: .5rem; }
        .cq-action { align-items: center; background: #fff; border: 1px solid #e5e7eb; border-radius: .45rem; color: #111827; display: inline-flex; font-size: .76rem; font-weight: 500; gap: .35rem; justify-content: center; line-height: 1; min-height: 2rem; padding: .38rem .65rem; text-decoration: none; transition: background-color .14s ease, border-color .14s ease, color .14s ease; }
        .cq-action:hover { background: #f9fafb; border-color: #d1d5db; color: #111827; }
        .cq-action-primary { background: #000; border-color: #000; color: #fff; }
        .cq-action-primary:hover { background: #1a1a1a; border-color: #1a1a1a; color: #fff; }
        .cq-note-preview { background: #f8fafc; border-radius: .5rem; color: #475569; font-size: .76rem; padding: .45rem .55rem; }
        .cq-modal-card { background: #fff; border: 1px solid #e5e7eb; border-radius: .875rem; box-shadow: 0 8px 20px rgba(15, 23, 42, .08); display: grid; gap: .8rem; padding: 1rem; width: min(100%, 30rem); }
        .cq-textarea { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: .5rem; font-size: .82rem; min-height: 7rem; padding: .65rem; width: 100%; }
        .dark .cq-page { color: #e5e7eb; }
        .dark .cq-subtitle, .dark .cq-kpi-label, .dark .cq-meta, .dark .cq-alerts-hint, .dark .cq-alert-column, .dark .cq-fact span:first-child, .dark .cq-close, .dark .cq-wait { color: #94a3b8; }
        .dark .cq-search, .dark .cq-kpi, .dark .cq-alerts, .dark .cq-column, .dark .cq-card, .dark .cq-detail-card, .dark .cq-drawer-panel, .dark .cq-modal-card { background: #111827; border-color: #263244; color: #e5e7eb; }
        .dark .cq-search { box-shadow: none; }
        .dark .cq-search::placeholder { color: #64748b; }
        .dark .cq-kpi-icon { background: rgba(20, 184, 166, .12); color: #2dd4bf; }
        .dark .cq-kpi-count, .dark .cq-kpi-value, .dark .cq-alerts-heading, .dark .cq-title, .dark .cq-name, .dark .cq-fact span:last-child { color: #f8fafc; }
        .dark .cq-alerts-head, .dark .cq-column-head, .dark .cq-drawer-head, .dark .cq-drawer-footer { background: #111827; border-color: #263244; }
        .dark .cq-alerts-icon { background: rgba(220, 38, 38, .14); border-color: rgba(248, 113, 113, .35); color: #f87171; }
        .dark .cq-alert-card { background: #0f172a; border-color: #263244; color: #f8fafc; }
        .dark .cq-alert-card:hover, .dark .cq-card:hover, .dark .cq-action:hover, .dark .cq-close:hover, .dark .cq-quick-action:hover { background: #172033; border-color: #334155; color: #f8fafc; }
        .dark .cq-alert-card-critical { background: rgba(127, 29, 29, .18); border-color: #ef4444; color: #f8fafc; }
        .dark .cq-alert-card-warning { background: rgba(113, 63, 18, .2); border-color: #ca8a04; color: #f8fafc; }
        .dark .cq-alert-card-critical .cq-alert-meta, .dark .cq-alert-card-critical .cq-alert-column, .dark .cq-alert-card-warning .cq-alert-meta, .dark .cq-alert-card-warning .cq-alert-column { color: #cbd5e1; }
        .dark .cq-alert-time-critical { background: #ef4444; color: #111827; }
        .dark .cq-alert-time-warning, .dark .cq-alerts-badge-warning { background: #fbbf24; color: #111827; }
        .dark .cq-alerts-badge-critical { background: #ef4444; color: #111827; }
        .dark .cq-count, .dark .cq-avatar { background: #1f2937; color: #e5e7eb; }
        .dark .cq-badge { background: rgba(16, 185, 129, .14); color: #34d399; }
        .dark .cq-card { border-color: #263244; }
        .dark .cq-quick-action { background: #111827; border-color: #263244; box-shadow: 0 8px 18px rgba(0, 0, 0, .28); color: #f8fafc; }
        .dark .cq-wait-warning { color: #fbbf24; }
        .dark .cq-wait-critical { color: #f87171; }
        .dark .cq-drawer { background: rgba(2, 6, 23, .56); }
        .dark .cq-action { background: #111827; border-color: #263244; color: #e5e7eb; }
        .dark .cq-action-primary { background: #f8fafc; border-color: #f8fafc; color: #0f172a; }
        .dark .cq-action-primary:hover { background: #e5e7eb; border-color: #e5e7eb; color: #0f172a; }
        .dark .cq-note-preview, .dark .cq-textarea { background: #0f172a; border-color: #263244; color: #cbd5e1; }
        @media (max-width: 900px) { .cq-toolbar { margin-top: 0; } .cq-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } .cq-board { grid-template-columns: repeat(4, 16rem); } }
    </style>

    <div class="cq-page" wire:poll.visible.10s>
        <p class="cq-subtitle">Asistencia a doctores · pacientes en preparacion y consulta</p>
        <div class="cq-toolbar"><input class="cq-search" type="search" wire:model.live.debounce.350ms="search" placeholder="Buscar paciente..."></div>

        <div class="cq-kpis">
            <div class="cq-kpi"><span class="cq-kpi-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path></svg></span><div><div class="cq-kpi-label">En espera</div><div class="cq-kpi-count">{{ $summary[\App\Enums\AppointmentStatus::CheckedIn->value] }}</div></div></div>
            <div class="cq-kpi"><span class="cq-kpi-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"></path></svg></span><div><div class="cq-kpi-label">En preparacion</div><div class="cq-kpi-count">{{ $summary[\App\Enums\AppointmentStatus::Preparing->value] }}</div></div></div>
            <div class="cq-kpi"><span class="cq-kpi-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path></svg></span><div><div class="cq-kpi-label">Listo para doctor</div><div class="cq-kpi-count">{{ $summary[\App\Enums\AppointmentStatus::ReadyForDoctor->value] }}</div></div></div>
            <div class="cq-kpi"><span class="cq-kpi-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"></path></svg></span><div><div class="cq-kpi-label">En consulta</div><div class="cq-kpi-count">{{ $summary[\App\Enums\AppointmentStatus::InConsultation->value] }}</div></div></div>
        </div>

        @if ($alerts->isNotEmpty())
            <section class="cq-alerts">
                <header class="cq-alerts-head">
                    <span class="cq-alerts-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-triangle-alert size-4" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg></span>
                    <span class="cq-alerts-heading">Alertas</span>
                    @if ($alertSummary['critical'] > 0)
                        <span class="cq-alerts-badge cq-alerts-badge-critical">{{ $alertSummary['critical'] }} criticas</span>
                    @endif
                    @if ($alertSummary['warning'] > 0)
                        <span class="cq-alerts-badge cq-alerts-badge-warning">{{ $alertSummary['warning'] }} medias</span>
                    @endif
                    <span class="cq-alerts-hint">Toca una alerta para ubicar al paciente</span>
                    <button type="button" class="cq-alerts-toggle @if (! $showAlerts) is-collapsed @endif" wire:click="toggleAlerts" aria-label="{{ $showAlerts ? 'Ocultar alertas' : 'Mostrar alertas' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5"></path></svg>
                    </button>
                </header>
                @if ($showAlerts)
                    <div class="cq-alerts-body">
                        @foreach ($alerts as $alert)
                            <button type="button" class="cq-alert-card cq-alert-card-{{ $alert['level'] }}" wire:click="focusAppointment({{ $alert['id'] }})">
                                <span class="cq-alert-main">
                                    <span class="cq-alert-dot cq-alert-dot-{{ $alert['level'] }}"></span>
                                    <span>
                                        <span class="cq-alert-name">{{ $alert['message'] }}</span>
                                        <span class="cq-alert-meta">{{ $alert['procedure'] }} · {{ $alert['doctor'] }}</span>
                                    </span>
                                </span>
                                <span class="cq-alert-side">
                                    <span class="cq-alert-column">{{ $alert['column'] }}</span>
                                    <span class="cq-alert-time cq-alert-time-{{ $alert['level'] }}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path></svg>{{ $alert['minutes'] }} min</span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        <div class="cq-board">
            @foreach ($columns as $status => $label)
                @php($cards = $this->cards($status))
                <section class="cq-column">
                    <div class="cq-column-head cq-title"><span>{{ $label }}</span><span class="cq-count">{{ $cards->count() }}</span></div>
                    <div class="cq-card-list">
                        @forelse ($cards as $appointment)
                            @php($nextTransitions = $this->availableTransitions($appointment))
                            @php($nextStatus = array_key_first($nextTransitions))
                            <div id="cq-appointment-{{ $appointment->id }}" class="cq-card-shell">
                                @if ($nextStatus)
                                    <button type="button" class="cq-quick-action" title="{{ $nextTransitions[$nextStatus] }}" aria-label="{{ $nextTransitions[$nextStatus] }}" wire:click="transition({{ $appointment->id }}, '{{ $nextStatus }}')">
                                        @switch($nextStatus)
                                            @case(\App\Enums\AppointmentStatus::Preparing->value)
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"></path></svg>
                                                @break
                                            @case(\App\Enums\AppointmentStatus::ReadyForDoctor->value)
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path></svg>
                                                @break
                                            @case(\App\Enums\AppointmentStatus::CheckedIn->value)
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path></svg>
                                                @break
                                            @default
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"></path></svg>
                                        @endswitch
                                    </button>
                                @endif
                                <button type="button" class="cq-card" wire:click="selectAppointment({{ $appointment->id }})">
                                    <span class="cq-card-head"><span class="cq-avatar">{{ str($appointment->patient?->full_name ?? 'P')->explode(' ')->map(fn ($part) => str($part)->substr(0, 1))->take(2)->implode('') }}</span><span class="cq-card-body"><span class="cq-name">{{ $appointment->patient?->full_name ?? 'Paciente sin nombre' }}</span><span class="cq-meta cq-card-time"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path></svg>{{ $appointment->scheduled_at?->format('h:i a') }} · {{ $appointment->doctor?->name ?? 'Sin doctor' }}</span><span class="cq-meta">{{ $appointment->procedure?->name ?? 'Sin procedimiento' }}</span></span></span>
                                    <span class="cq-card-foot"><span class="cq-badge">{{ $appointment->status->label() }}</span>@if ($appointment->waitingMinutes() !== null)@php($waitingMinutes = $appointment->waitingMinutes())<span class="cq-wait @if ($waitingMinutes >= $waitCriticalMinutes) cq-wait-critical @elseif ($waitingMinutes >= $waitWarningMinutes) cq-wait-warning @endif"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path></svg>{{ $waitingMinutes }} min</span>@endif</span>
                                    @if ($appointment->latestAppointmentNote)<span class="cq-note-preview">{{ str($appointment->latestAppointmentNote->note)->limit(70) }}</span>@endif
                                </button>
                            </div>
                        @empty
                            <div class="cq-meta" style="padding: .8rem; text-align: center;">Sin pacientes.</div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    @if ($selected)
        <div class="cq-drawer" wire:click.self="closeDetail">
            <aside class="cq-drawer-panel">
                <div class="cq-drawer-head">
                    <div style="display: flex; gap: .75rem;">
                        <span class="cq-avatar">{{ str($selected->patient?->full_name ?? 'P')->explode(' ')->map(fn ($part) => str($part)->substr(0, 1))->take(2)->implode('') }}</span>
                        <div>
                            <div class="cq-name">{{ $selected->patient?->full_name ?? 'Paciente sin nombre' }}</div>
                            <div class="cq-meta">{{ $selected->patient?->phone ?? 'Sin telefono' }}</div>
                            <span class="cq-badge" style="margin-top: .35rem;">{{ $selected->status->label() }}</span>
                        </div>
                    </div>
                    <button type="button" class="cq-close" wire:click="closeDetail">×</button>
                </div>

                <div class="cq-drawer-body">
                    <div class="cq-detail-card"><div class="cq-title">Cita</div><div class="cq-fact"><span>Hora</span><span>{{ $selected->scheduled_at?->format('h:i a') ?? '-' }}</span></div><div class="cq-fact"><span>Doctor</span><span>{{ $selected->doctor?->name ?? '-' }}</span></div><div class="cq-fact"><span>Procedimiento</span><span>{{ $selected->procedure?->name ?? '-' }}</span></div><div class="cq-fact"><span>Esperando</span><span>{{ $selected->waitingMinutes() !== null ? $selected->waitingMinutes().' min' : '-' }}</span></div></div>
                    @if ($selected->notes)<div class="cq-detail-card"><div class="cq-title">Nota</div><div class="cq-meta">{{ $selected->notes }}</div></div>@endif
                    @if ($selected->latestAppointmentNote)<div class="cq-detail-card"><div class="cq-title">Ultima nota operativa</div><div class="cq-meta">{{ $selected->latestAppointmentNote->note }}</div></div>@endif
                    <div class="cq-detail-card"><div class="cq-title">Siguiente paso</div><div class="cq-actions">@foreach ($this->availableTransitions($selected) as $status => $label)<button type="button" class="cq-action @if ($loop->first) cq-action-primary @endif" wire:click="transition({{ $selected->id }}, '{{ $status }}')">{{ $label }}</button>@endforeach</div></div>
                </div>

                <div class="cq-drawer-footer">
                    <button type="button" class="cq-action" wire:click="openNoteModal({{ $selected->id }})">Agregar nota</button>
                </div>
            </aside>
        </div>
    @endif

    @if ($noteAppointmentId)
        <div class="cq-drawer" wire:click.self="closeNoteModal" style="display: flex; align-items: center; justify-content: center;"><div class="cq-modal-card"><div class="cq-title">Agregar nota operativa</div><textarea class="cq-textarea" wire:model="noteText" placeholder="Ej.: Paciente ansioso, aplicar comunicacion calmada."></textarea>@error('noteText') <div class="cq-meta" style="color: #dc2626;">{{ $message }}</div> @enderror<div class="cq-actions" style="justify-content: flex-end;"><button type="button" class="cq-action" wire:click="closeNoteModal">Cancelar</button><button type="button" class="cq-action cq-action-primary" wire:click="saveNote">Guardar nota</button></div></div></div>
    @endif

    <script>
        window.addEventListener('cq-focus-appointment', (event) => {
            const appointmentId = event.detail?.appointmentId;

            window.setTimeout(() => {
                const card = document.getElementById(`cq-appointment-${appointmentId}`);

                if (! card) {
                    return;
                }

                card.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
            }, 80);
        });
    </script>
</x-filament-panels::page>
