<x-filament-panels::page>
    @php
        $summary = $this->summary();
        $columns = $this->columns();
        $selected = $this->selectedAppointment();
        $alerts = $this->alerts();
        $alertSummary = $this->alertSummary();
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
        .reception-alerts { background: #fff; border-color: #e5e7eb; color: #0f172a; overflow: hidden; padding: 0; }
        .reception-alerts-head { align-items: center; background: #fff; border-bottom: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; gap: .65rem; padding: .85rem 1rem; }
        .reception-alerts-icon { align-items: center; background: #fff7f7; border: 1px solid #fecaca; border-radius: .65rem; color: #dc2626; display: inline-flex; height: 2rem; justify-content: center; width: 2rem; }
        .reception-alerts-icon svg { height: 1rem; width: 1rem; }
        .reception-alerts-heading { color: #0f172a; font-size: .92rem; font-weight: 700; }
        .reception-alerts-hint { color: #64748b; font-size: .78rem; }
        .reception-alerts-toggle { align-items: center; background: transparent; border: 0; color: #64748b; display: inline-flex; height: 2rem; justify-content: center; margin-left: auto; width: 2rem; }
        .reception-alerts-toggle svg { height: 1rem; width: 1rem; }
        .reception-alerts-toggle.is-collapsed svg { transform: rotate(180deg); }
        .reception-alerts-badge { border-radius: 999px; display: inline-flex; font-size: .72rem; font-weight: 700; padding: .3rem .6rem; }
        .reception-alerts-badge-critical { background: #ff6b63; color: #111827; }
        .reception-alerts-badge-warning { background: #fbbf24; color: #111827; }
        .reception-alerts-body { display: grid; gap: .55rem; padding: .75rem 1rem 1rem; }
        .reception-alert-card { align-items: center; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: .65rem; color: inherit; cursor: pointer; display: grid; gap: .75rem; grid-template-columns: minmax(0, 1fr) auto; padding: .75rem .85rem; text-align: left; transition: border-color .14s ease, background .14s ease, box-shadow .14s ease; width: 100%; }
        .reception-alert-card:hover { box-shadow: 0 8px 18px rgba(15, 23, 42, .08); }
        .reception-alert-card-critical { background: #4b1717; border-color: #b91c1c; color: #fff; }
        .reception-alert-card-warning { background: #422b05; border-color: #a16207; color: #fff; }
        .reception-alert-card-neutral { background: #f8fafc; border-color: #e5e7eb; color: #0f172a; }
        .reception-alert-card:focus-visible { outline: 2px solid #14b8a6; outline-offset: 2px; }
        .reception-alert-main { align-items: start; display: grid; gap: .6rem; grid-template-columns: auto minmax(0, 1fr); }
        .reception-alert-dot { border-radius: 999px; height: .55rem; margin-top: .35rem; width: .55rem; }
        .reception-alert-dot-critical { background: #ff6b63; }
        .reception-alert-dot-warning { background: #fbbf24; }
        .reception-alert-dot-neutral { background: #94a3b8; }
        .reception-alert-name { display: block; font-size: .9rem; font-weight: 700; line-height: 1.25; }
        .reception-alert-meta { color: #cbd5e1; display: block; font-size: .78rem; margin-top: .35rem; }
        .reception-alert-card-neutral .reception-alert-meta, .reception-alert-card-neutral .reception-alert-column { color: #64748b; }
        .reception-alert-side { align-items: center; align-self: center; display: flex; gap: .45rem; justify-content: flex-end; }
        .reception-alert-column { color: #cbd5e1; font-size: .78rem; }
        .reception-alert-time { align-items: center; border-radius: 999px; display: inline-flex; font-size: .78rem; font-weight: 700; gap: .3rem; padding: .28rem .55rem; }
        .reception-alert-time svg { height: .85rem; width: .85rem; }
        .reception-alert-time-critical { background: #ff6b63; color: #111827; }
        .reception-alert-time-warning { background: #fbbf24; color: #111827; }
        .reception-alert-time-neutral { background: #e2e8f0; color: #334155; }
        .reception-alert-title, .reception-column-title { align-items: center; display: flex; font-size: .86rem; font-weight: 600; gap: .45rem; justify-content: space-between; }
        .reception-alert-list { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: .65rem; }
        .reception-board { display: grid; gap: .75rem; grid-template-columns: repeat(5, minmax(14rem, 1fr)); overflow-x: auto; }
        .reception-column { min-height: 32rem; overflow: hidden; }
        .reception-column-title { border-bottom: 1px solid #e5e7eb; padding: .75rem .85rem; }
        .reception-count { background: #f1f5f9; border-radius: 999px; color: #334155; font-size: .75rem; padding: .1rem .45rem; }
        .reception-card-list { display: grid; gap: .55rem; padding: .65rem; }
        .reception-card-shell { position: relative; }
        .reception-card { cursor: pointer; display: grid; gap: .55rem; padding: .7rem; text-align: left; transition: border-color .14s ease, background .14s ease; width: 100%; }
        .reception-card:hover { background: #f8fafc; border-color: #cbd5e1; }
        .reception-quick-action { align-items: center; background: #fff; border: 1px solid #e5e7eb; border-radius: .55rem; box-shadow: 0 8px 18px rgba(15, 23, 42, .1); color: #0f172a; display: inline-flex; height: 2.35rem; justify-content: center; opacity: 0; pointer-events: none; position: absolute; right: .7rem; top: .7rem; transform: translateX(.25rem); transition: opacity .14s ease, transform .14s ease, background .14s ease, border-color .14s ease; width: 2.35rem; z-index: 2; }
        .reception-quick-action svg { height: 1rem; width: 1rem; }
        .reception-card-shell:hover .reception-quick-action, .reception-card-shell:focus-within .reception-quick-action { opacity: 1; pointer-events: auto; transform: translateX(0); }
        .reception-quick-action:hover { background: #f9fafb; border-color: #d1d5db; }
        .reception-card-head { align-items: start; display: grid; gap: .6rem; grid-template-columns: auto 1fr; }
        .reception-card-body { display: grid; gap: .18rem; }
        .reception-card-time { align-items: center; display: flex; gap: .3rem; }
        .reception-card-time svg { height: .9rem; width: .9rem; }
        .reception-avatar { align-items: center; background: #f1f5f9; border-radius: 999px; color: #0f172a; display: inline-flex; font-size: .75rem; font-weight: 600; height: 2.25rem; justify-content: center; width: 2.25rem; }
        .reception-name { font-size: .9rem; font-weight: 650; }
        .reception-meta { color: #64748b; font-size: .78rem; line-height: 1.45; }
        .reception-badge { background: #ecfdf5; border-radius: .4rem; color: #047857; display: inline-flex; font-size: .72rem; font-weight: 600; padding: .2rem .45rem; width: fit-content; }
        .reception-wait { align-items: center; color: #64748b; display: inline-flex; font-size: .75rem; font-weight: 600; gap: .25rem; justify-self: end; }
        .reception-wait svg { height: .85rem; width: .85rem; }
        .reception-wait-warning { color: #ca8a04; }
        .reception-wait-critical { color: #dc2626; }
        .reception-card-foot { align-items: center; display: flex; justify-content: space-between; }
        .reception-drawer { background: rgba(15, 23, 42, .22); inset: 0; position: fixed; z-index: 60; }
        .reception-drawer-panel { background: #fff; border: 1px solid #e5e7eb; border-radius: .875rem; box-shadow: 0 8px 20px rgba(15, 23, 42, .08); color: #0f172a; display: flex; flex-direction: column; max-height: calc(100vh - 2rem); overflow: hidden; position: fixed; right: 1rem; top: 1rem; width: min(38rem, calc(100vw - 2rem)); }
        .reception-drawer-head { align-items: flex-start; border-bottom: 1px solid #e5e7eb; display: flex; flex: 0 0 auto; gap: .75rem; justify-content: space-between; padding: 1rem; }
        .reception-drawer-body { align-content: start; display: grid; flex: 1 1 auto; gap: .8rem; overflow-y: auto; padding: 1rem; }
        .reception-drawer-footer { border-top: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; gap: .45rem; padding: .9rem 1rem; }
        .reception-drawer-footer .reception-action { flex: 1; justify-content: center; min-height: 2.25rem; }
        .reception-close { align-items: center; background: transparent; border: 1px solid transparent; border-radius: .45rem; color: #64748b; cursor: pointer; display: inline-flex; flex: 0 0 auto; font-size: 1rem; font-weight: 500; height: 2rem; justify-content: center; line-height: 1; transition: .14s ease; width: 2rem; }
        .reception-close:hover { background: #f9fafb; border-color: #e5e7eb; color: #0f172a; }
        .reception-detail-card { background: #fff; border: 1px solid #e5e7eb; border-radius: .75rem; display: grid; gap: .55rem; padding: .85rem; }
        .reception-fact { display: grid; gap: .75rem; grid-template-columns: 1fr 1fr; }
        .reception-fact span:first-child { color: #64748b; font-size: .8rem; }
        .reception-fact span:last-child { font-size: .82rem; font-weight: 600; text-align: right; }
        .reception-actions { align-items: center; display: flex; flex-wrap: wrap; gap: .5rem; }
        .reception-action { align-items: center; background: #fff; border: 1px solid #e5e7eb; border-radius: .45rem; color: #111827; display: inline-flex; font-size: .76rem; font-weight: 500; gap: .35rem; justify-content: center; line-height: 1; min-height: 2rem; padding: .38rem .65rem; text-decoration: none; transition: background-color .14s ease, border-color .14s ease, color .14s ease; }
        .reception-action:hover { background: #f9fafb; border-color: #d1d5db; color: #111827; }
        .reception-action-primary { background: #000; border-color: #000; color: #fff; }
        .reception-action-primary:hover { background: #1a1a1a; border-color: #1a1a1a; color: #fff; }
        .reception-note-preview { background: #f8fafc; border-radius: .5rem; color: #475569; font-size: .76rem; padding: .45rem .55rem; }
        .reception-modal-card { background: #fff; border: 1px solid #e5e7eb; border-radius: .875rem; box-shadow: 0 8px 20px rgba(15, 23, 42, .08); display: grid; gap: .8rem; padding: 1rem; width: min(100%, 30rem); }
        .reception-textarea { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: .5rem; font-size: .82rem; min-height: 7rem; padding: .65rem; width: 100%; }
        .dark .reception-page { color: #e5e7eb; }
        .dark .reception-subtitle, .dark .reception-kpi-label, .dark .reception-meta, .dark .reception-alerts-hint, .dark .reception-alert-column, .dark .reception-fact span:first-child, .dark .reception-close, .dark .reception-wait { color: #94a3b8; }
        .dark .reception-search, .dark .reception-kpi, .dark .reception-alerts, .dark .reception-column, .dark .reception-card, .dark .reception-detail-card, .dark .reception-drawer-panel, .dark .reception-modal-card { background: #111827; border-color: #263244; color: #e5e7eb; }
        .dark .reception-search { box-shadow: none; }
        .dark .reception-search::placeholder { color: #64748b; }
        .dark .reception-kpi-icon { background: rgba(20, 184, 166, .12); color: #2dd4bf; }
        .dark .reception-kpi-value, .dark .reception-alerts-heading, .dark .reception-alert-title, .dark .reception-column-title, .dark .reception-name, .dark .reception-fact span:last-child { color: #f8fafc; }
        .dark .reception-alerts-head, .dark .reception-column-title, .dark .reception-drawer-head, .dark .reception-drawer-footer { background: #111827; border-color: #263244; }
        .dark .reception-alerts-icon { background: rgba(220, 38, 38, .14); border-color: rgba(248, 113, 113, .35); color: #f87171; }
        .dark .reception-alert-card { background: #0f172a; border-color: #263244; color: #f8fafc; }
        .dark .reception-alert-card:hover, .dark .reception-card:hover, .dark .reception-action:hover, .dark .reception-close:hover, .dark .reception-quick-action:hover { background: #172033; border-color: #334155; color: #f8fafc; }
        .dark .reception-alert-card-critical { background: rgba(127, 29, 29, .18); border-color: #ef4444; color: #f8fafc; }
        .dark .reception-alert-card-warning { background: rgba(113, 63, 18, .2); border-color: #ca8a04; color: #f8fafc; }
        .dark .reception-alert-card-neutral { background: #0f172a; border-color: #334155; color: #f8fafc; }
        .dark .reception-alert-card-critical .reception-alert-meta, .dark .reception-alert-card-critical .reception-alert-column, .dark .reception-alert-card-warning .reception-alert-meta, .dark .reception-alert-card-warning .reception-alert-column, .dark .reception-alert-card-neutral .reception-alert-meta, .dark .reception-alert-card-neutral .reception-alert-column { color: #cbd5e1; }
        .dark .reception-alert-time-critical { background: #ef4444; color: #111827; }
        .dark .reception-alert-time-warning, .dark .reception-alerts-badge-warning { background: #fbbf24; color: #111827; }
        .dark .reception-alert-time-neutral { background: #334155; color: #e5e7eb; }
        .dark .reception-alerts-badge-critical { background: #ef4444; color: #111827; }
        .dark .reception-count, .dark .reception-avatar { background: #1f2937; color: #e5e7eb; }
        .dark .reception-badge { background: rgba(16, 185, 129, .14); color: #34d399; }
        .dark .reception-card { border-color: #263244; }
        .dark .reception-quick-action { background: #111827; border-color: #263244; box-shadow: 0 8px 18px rgba(0, 0, 0, .28); color: #f8fafc; }
        .dark .reception-wait-warning { color: #fbbf24; }
        .dark .reception-wait-critical { color: #f87171; }
        .dark .reception-drawer { background: rgba(2, 6, 23, .56); }
        .dark .reception-action { background: #111827; border-color: #263244; color: #e5e7eb; }
        .dark .reception-action-primary { background: #f8fafc; border-color: #f8fafc; color: #0f172a; }
        .dark .reception-action-primary:hover { background: #e5e7eb; border-color: #e5e7eb; color: #0f172a; }
        .dark .reception-note-preview, .dark .reception-textarea { background: #0f172a; border-color: #263244; color: #cbd5e1; }
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

        @if ($alerts->isNotEmpty())
            <section class="reception-alerts">
                <header class="reception-alerts-head">
                    <span class="reception-alerts-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"></path></svg></span>
                    <span class="reception-alerts-heading">Alertas</span>
                    @if ($alertSummary['critical'] > 0)
                        <span class="reception-alerts-badge reception-alerts-badge-critical">{{ $alertSummary['critical'] }} criticas</span>
                    @endif
                    @if ($alertSummary['warning'] > 0)
                        <span class="reception-alerts-badge reception-alerts-badge-warning">{{ $alertSummary['warning'] }} medias</span>
                    @endif
                    <span class="reception-alerts-hint">Toca una alerta para ubicar al paciente</span>
                    <button type="button" class="reception-alerts-toggle @if (! $showAlerts) is-collapsed @endif" wire:click="toggleAlerts" aria-label="{{ $showAlerts ? 'Ocultar alertas' : 'Mostrar alertas' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5"></path></svg>
                    </button>
                </header>
                @if ($showAlerts)
                    <div class="reception-alerts-body">
                        @foreach ($alerts as $alert)
                            <button type="button" class="reception-alert-card reception-alert-card-{{ $alert['level'] }}" wire:click="focusAppointment({{ $alert['id'] }})">
                                <span class="reception-alert-main">
                                    <span class="reception-alert-dot reception-alert-dot-{{ $alert['level'] }}"></span>
                                    <span>
                                        <span class="reception-alert-name">{{ $alert['message'] }}</span>
                                        <span class="reception-alert-meta">{{ $alert['procedure'] }} · {{ $alert['doctor'] }}</span>
                                    </span>
                                </span>
                                <span class="reception-alert-side">
                                    <span class="reception-alert-column">{{ $alert['column'] }}</span>
                                    <span class="reception-alert-time reception-alert-time-{{ $alert['level'] }}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path></svg>{{ $alert['minutes'] }} min</span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        <div class="reception-board">
            @foreach ($columns as $key => $label)
                @php($cards = $this->cards($key))
                <section class="reception-column">
                    <div class="reception-column-title"><span>{{ $label }}</span><span class="reception-count">{{ $cards->count() }}</span></div>
                    <div class="reception-card-list">
                        @forelse ($cards as $appointment)
                            @php($nextTransitions = $this->availableTransitions($appointment))
                            @php($nextStatus = array_key_first($nextTransitions))
                            <div id="reception-appointment-{{ $appointment->id }}" class="reception-card-shell">
                                @if ($nextStatus)
                                    <button type="button" class="reception-quick-action" title="{{ $nextTransitions[$nextStatus] }}" aria-label="{{ $nextTransitions[$nextStatus] }}" wire:click="transition({{ $appointment->id }}, '{{ $nextStatus }}')">
                                        @switch($nextStatus)
                                            @case(\App\Enums\AppointmentStatus::CheckedIn->value)
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path></svg>
                                                @break
                                            @case(\App\Enums\AppointmentStatus::Preparing->value)
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"></path></svg>
                                                @break
                                            @case(\App\Enums\AppointmentStatus::ReadyForDoctor->value)
                                            @case(\App\Enums\AppointmentStatus::Completed->value)
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path></svg>
                                                @break
                                            @case(\App\Enums\AppointmentStatus::InConsultation->value)
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"></path></svg>
                                                @break
                                            @default
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"></path></svg>
                                        @endswitch
                                    </button>
                                @endif
                                <button type="button" class="reception-card" wire:click="selectAppointment({{ $appointment->id }})">
                                    <span class="reception-card-head">
                                        <span class="reception-avatar">{{ str($appointment->patient?->full_name ?? 'P')->explode(' ')->map(fn ($part) => str($part)->substr(0, 1))->take(2)->implode('') }}</span>
                                        <span class="reception-card-body">
                                            <span class="reception-name">{{ $appointment->patient?->full_name ?? 'Paciente sin nombre' }}</span>
                                            <span class="reception-meta reception-card-time"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path></svg>{{ $appointment->scheduled_at?->format('h:i a') }} · {{ $appointment->doctor?->name ?? 'Sin doctor' }}</span>
                                            <span class="reception-meta">{{ $appointment->procedure?->name ?? 'Sin procedimiento' }}</span>
                                        </span>
                                    </span>
                                    <span class="reception-card-foot">
                                        <span class="reception-badge">{{ $appointment->status->label() }}</span>
                                        @if ($appointment->waitingMinutes() !== null)
                                            @php($waitingMinutes = $appointment->waitingMinutes())
                                            <span class="reception-wait @if ($waitingMinutes >= 20) reception-wait-critical @elseif ($waitingMinutes >= 10) reception-wait-warning @endif"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path></svg>{{ $waitingMinutes }} min</span>
                                        @endif
                                    </span>
                                    @if ($appointment->latestAppointmentNote)
                                        <span class="reception-note-preview">{{ str($appointment->latestAppointmentNote->note)->limit(70) }}</span>
                                    @endif
                                </button>
                            </div>
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

                <div class="reception-drawer-body">
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
                </div>

                <div class="reception-drawer-footer">
                    <button type="button" class="reception-action" wire:click="openNoteModal({{ $selected->id }})">Agregar nota</button>
                    <a class="reception-action" href="{{ $this->appointmentUrl($selected) }}">Abrir cita completa</a>
                </div>
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

    <script>
        window.addEventListener('reception-focus-appointment', (event) => {
            const appointmentId = event.detail?.appointmentId;

            window.setTimeout(() => {
                const card = document.getElementById(`reception-appointment-${appointmentId}`);

                if (! card) {
                    return;
                }

                card.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
            }, 80);
        });
    </script>
</x-filament-panels::page>
