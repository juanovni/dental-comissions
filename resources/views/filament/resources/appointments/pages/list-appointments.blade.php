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
            align-items: center;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: .65rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
            color: #64748b;
            display: flex;
            gap: .45rem;
            height: 2.65rem;
            padding: 0 .75rem;
        }

        .appointments-search:focus-within {
            border-color: color-mix(in srgb, var(--agenda-primary) 35%, #e5e7eb);
            box-shadow: 0 8px 18px rgba(15, 23, 42, .08);
        }

        .appointments-search svg {
            flex: 0 0 auto;
            height: 1.05rem;
            width: 1.05rem;
        }

        .appointments-search input {
            background: transparent;
            border: 0;
            box-shadow: none;
            color: var(--agenda-text);
            font-size: .82rem;
            height: 100%;
            outline: none;
            padding: 0;
            width: 100%;
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
            align-items: center;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: .65rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
            color: #111827;
            cursor: pointer;
            display: inline-flex;
            font-size: .82rem;
            font-weight: 600;
            gap: .45rem;
            height: 2.65rem;
            line-height: 1;
            padding: 0 .85rem;
        }

        .appointment-filter-button:hover,
        .appointment-filter-button:focus-visible {
            border-color: color-mix(in srgb, var(--agenda-primary) 35%, #e5e7eb);
            box-shadow: 0 8px 18px rgba(15, 23, 42, .08);
        }

        .appointment-filter-button svg {
            height: 1rem;
            width: 1rem;
        }

        .appointment-filter-menu {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: .75rem;
            box-shadow: 0 18px 35px rgba(15, 23, 42, .14);
            min-width: 14rem;
            padding: .5rem;
            position: absolute;
            right: 0;
            z-index: 30;
            margin-top: .35rem;
        }

        .appointment-filter-heading {
            border-bottom: 1px solid #eef2f7;
            color: #111827;
            font-size: .84rem;
            font-weight: 700;
            margin: -.5rem -.5rem .35rem;
            padding: .8rem .9rem;
        }

        .appointment-filter-option {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: .55rem;
            color: #1f2937;
            cursor: pointer;
            display: flex;
            font-size: .86rem;
            gap: .65rem;
            padding: .55rem .65rem;
            text-align: left;
            width: 100%;
        }

        .appointment-filter-option:hover {
            background: #eef8f8;
            color: var(--agenda-primary);
        }

        .appointment-filter-option.active {
            color: var(--agenda-primary);
            font-weight: 600;
        }

        .appointment-action-heading {
            color: #111827;
            font-size: .8125rem;
            font-weight: 600;
            padding: .5rem .875rem .35rem;
        }

        .appointment-action-menu {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: .75rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
            min-width: 12rem;
            padding: .375rem 0;
            position: absolute;
            right: 0;
            z-index: 50;
            margin-top: .375rem;
        }

        .appointment-action-item {
            align-items: center;
            color: #1f2937;
            cursor: pointer;
            display: flex;
            font-size: .8125rem;
            font-weight: 500;
            gap: .625rem;
            padding: .5rem .875rem;
            text-decoration: none;
            transition: background .12s ease;
            width: 100%;
        }

        .appointment-action-item:hover {
            background: #f3f4f6;
        }

        .appointment-action-item svg {
            color: #9ca3af;
            flex-shrink: 0;
            height: 1rem;
            width: 1rem;
        }

        .appointment-action-item.danger {
            color: #dc2626;
        }

        .appointment-action-item.danger:hover {
            background: #fef2f2;
        }

        .appointment-action-item.danger svg {
            color: #f87171;
        }

        .appointment-action-separator {
            height: 1px;
            background: #e2e8f0;
            margin: .375rem 0;
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
            gap: .35rem .85rem;
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

        .appointment-avatar-wrap {
            height: 2.9rem;
            position: relative;
            width: 2.9rem;
        }

        .appointment-channel-dot {
            align-items: center;
            background: #ffffff;
            border-radius: 999px;
            bottom: -.2rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .18);
            display: inline-flex;
            height: 1.35rem;
            justify-content: center;
            position: absolute;
            right: -.15rem;
            width: 1.35rem;
        }

        .appointment-channel-dot svg { height: 1.3rem; width: 1.3rem; }

        .appointment-avatar {
            align-items: center;
            background: #eef8f8;
            border: 1px solid #dbeafe;
            border-radius: 999px;
            color: #0f172a;
            display: flex;
            flex: 0 0 auto;
            font-size: .76rem;
            font-weight: 600;
            height: 2.9rem;
            justify-content: center;
            text-transform: uppercase;
            width: 2.9rem;
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

        .appointment-row-actions {
            align-items: center;
            display: flex;
            gap: .25rem;
            opacity: 0;
            pointer-events: none;
            transition: opacity .14s ease;
        }

        .appointment-row:hover .appointment-row-actions,
        .appointment-row:focus-within .appointment-row-actions {
            opacity: 1;
            pointer-events: auto;
        }

        .appointment-row-icon-action {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: .45rem;
            color: #475569;
            cursor: pointer;
            display: inline-flex;
            height: 2rem;
            justify-content: center;
            padding: 0;
            width: 2rem;
        }

        .appointment-row-icon-action:hover { background: #eef2f7; }

        .appointment-row-icon-action svg {
            width: 1.05rem;
            height: 1.05rem;
        }

        .appointments-empty {
            display: grid;
            min-height: 10rem;
            place-items: center;
            margin-top: .9rem;
            text-align: center;
        }

        .appointments-empty-icon {
            height: 12.25rem;
            margin: 0 auto 1.1rem;
            object-fit: contain;
            width: 12.8rem;
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

        .reschedule-overlay {
            align-items: center;
            background: rgba(15, 23, 42, .48);
            display: flex;
            inset: 0;
            justify-content: center;
            position: fixed;
            z-index: 60;
        }

        .reschedule-card {
            background: #ffffff;
            border-radius: .875rem;
            box-shadow: 0 4px 24px rgba(15, 23, 42, .12);
            display: grid;
            gap: .75rem;
            max-width: 26rem;
            padding: 1.25rem;
            position: relative;
            width: calc(100% - 2rem);
        }

        .reschedule-close {
            align-items: center;
            background: transparent;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            color: #94a3b8;
            display: inline-flex;
            font-size: .95rem;
            font-weight: 600;
            height: 1.75rem;
            justify-content: center;
            line-height: 1;
            position: absolute;
            right: .85rem;
            top: .85rem;
            transition: .14s ease;
            width: 1.75rem;
            cursor: pointer;
        }

        .reschedule-close:hover {
            background: #eef2f7;
            color: #0f172a;
        }

        .reschedule-header {
            padding-right: 2.6rem;
        }

        .reschedule-header h2 {
            color: #0f172a;
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
        }

        .reschedule-header-sub {
            color: #64748b;
            font-size: .76rem;
            margin-top: .2rem;
        }

        .reschedule-field + .reschedule-field {
            margin-top: .6rem;
        }

        .reschedule-label {
            color: #334155;
            display: block;
            font-size: .7rem;
            font-weight: 600;
            margin-bottom: .25rem;
        }

        .reschedule-input {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
            color: #334155;
            font-size: .78rem;
            font-weight: 500;
            line-height: 1.4;
            padding: .5rem .625rem;
            width: 100%;
            height: auto;
            outline: none;
            transition: border-color .14s ease;
        }

        .reschedule-input:focus {
            border-color: #94a3b8;
        }

        .reschedule-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: .625rem;
            color: #dc2626;
            font-size: .78rem;
            font-weight: 500;
            line-height: 1.5;
            padding: .65rem .75rem;
        }

        .reschedule-footer {
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: .5rem;
            padding-top: .75rem;
        }

        .reschedule-btn {
            align-items: center;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: .45rem;
            color: #111827;
            display: inline-flex;
            font-size: .76rem;
            font-weight: 500;
            gap: .35rem;
            justify-content: center;
            line-height: 1;
            min-height: 2rem;
            padding: .38rem .65rem;
            text-decoration: none;
            transition: background-color .14s ease, border-color .14s ease, color .14s ease;
            cursor: pointer;
        }

        .reschedule-btn:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            color: #111827;
        }

        .reschedule-btn.primary {
            background: #000000;
            border-color: #000000;
            color: #ffffff;
        }

        .reschedule-btn.primary:hover {
            background: #1a1a1a;
            border-color: #1a1a1a;
            color: #ffffff;
        }

        .reschedule-btn.primary svg {
            height: .9rem;
            width: .9rem;
        }

        .reschedule-btn:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        .dark .reschedule-card {
            background: #1e293b;
            border: 1px solid rgba(148, 163, 184, .18);
        }

        .dark .reschedule-close {
            border-color: rgba(148, 163, 184, .18);
            color: #64748b;
        }

        .dark .reschedule-close:hover {
            background: rgba(148, 163, 184, .12);
            color: #e2e8f0;
        }

        .dark .reschedule-header h2 {
            color: #f1f5f9;
        }

        .dark .reschedule-header-sub {
            color: #94a3b8;
        }

        .dark .reschedule-label {
            color: #94a3b8;
        }

        .dark .reschedule-input {
            background: rgba(15, 23, 42, .5);
            border-color: rgba(148, 163, 184, .14);
            color: #cbd5e1;
        }

        .dark .reschedule-input:focus {
            border-color: rgba(148, 163, 184, .35);
        }

        .dark .reschedule-footer {
            border-top-color: rgba(148, 163, 184, .14);
        }

        .dark .reschedule-btn {
            background: transparent;
            border-color: rgba(148, 163, 184, .18);
            color: #cbd5e1;
        }

        .dark .reschedule-btn:hover {
            background: rgba(148, 163, 184, .12);
            border-color: rgba(148, 163, 184, .25);
            color: #e2e8f0;
        }

        .dark .reschedule-btn.primary {
            background: #000000;
            border-color: #000000;
            color: #ffffff;
        }

        .dark .reschedule-btn.primary:hover {
            background: #1a1a1a;
            border-color: #1a1a1a;
        }

        .dark .reschedule-overlay {
            background: rgba(0, 0, 0, .55);
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

            .appointment-avatar-wrap {
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
                            $platform = $appointment->socialComment?->platform?->value;
                            $sourceIcon = match (true) {
                                $platform === 'whatsapp' || in_array($appointment->source?->value, ['whatsapp_ai', 'whatsapp_human']) => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.75 19.25 6 15.6a7 7 0 1 1 2.42 2.35l-3.67 1.3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9.2 8.95c.18-.5.42-.55.74-.55h.43c.22 0 .4.14.49.34l.68 1.52c.08.18.04.39-.1.53l-.47.48c.48.84 1.16 1.52 2 2l.48-.47c.14-.14.35-.18.53-.1l1.52.68c.2.09.34.27.34.49v.43c0 .32-.05.56-.55.74-.4.14-.83.21-1.28.21-2.64 0-5.26-2.62-5.26-5.26 0-.45.07-.88.21-1.28Z" /></svg>',
                                $platform === 'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><rect width="15" height="15" x="4.5" y="4.5" rx="4" /><path stroke-linecap="round" stroke-linejoin="round" d="M15.5 11.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" /><path stroke-linecap="round" d="M16.75 7.75h.01" /></svg>',
                                $platform === 'facebook' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 21v-7h2.35l.35-2.72h-2.7V9.55c0-.79.22-1.33 1.35-1.33h1.44V5.79c-.25-.03-1.1-.1-2.1-.1-2.08 0-3.5 1.27-3.5 3.6v1.99H8.34V14h2.35v7h2.81Z" /></svg>',
                                $appointment->source?->value === 'voice_call' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10.2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v4.2"/><path d="M12 14v6"/><path d="M8 20h8"/><path d="M2 10h20v3a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-3Z"/></svg>',
                                $appointment->source?->value === 'smart_link' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
                                default => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
                            };
                            $viewUrl = \App\Filament\Resources\Appointments\AppointmentResource::getUrl('view', ['record' => $appointment]);
                            $editUrl = \App\Filament\Resources\Appointments\AppointmentResource::getUrl('edit', ['record' => $appointment]);
                        @endphp

                        <article class="appointment-row" wire:key="appointment-row-{{ $appointment->id }}">
                            <div class="appointment-time">
                                <div class="appointment-time-main">{{ $appointment->scheduled_at?->format('h:i') }}&nbsp;{{ $appointment->scheduled_at?->format('a') === 'am' ? 'a.m.' : 'p.m.' }}</div>
                                <div class="appointment-duration">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                    <span>{{ $appointment->duration_minutes ?? 0 }}m</span>
                                </div>
                            </div>

                            <div class="appointment-avatar-wrap">
                                <div class="appointment-avatar">{{ $initials ?: 'P' }}</div>
                                @if ($platform)
                                    <span class="appointment-channel-dot" title="{{ $appointment->socialComment->platform->label() }}">
                                        @switch($platform)
                                            @case('instagram')
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><rect width="15" height="15" x="4.5" y="4.5" rx="4" /><path stroke-linecap="round" stroke-linejoin="round" d="M15.5 11.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" /><path stroke-linecap="round" d="M16.75 7.75h.01" /></svg>
                                                @break
                                            @case('whatsapp')
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.75 19.25 6 15.6a7 7 0 1 1 2.42 2.35l-3.67 1.3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9.2 8.95c.18-.5.42-.55.74-.55h.43c.22 0 .4.14.49.34l.68 1.52c.08.18.04.39-.1.53l-.47.48c.48.84 1.16 1.52 2 2l.48-.47c.14-.14.35-.18.53-.1l1.52.68c.2.09.34.27.34.49v.43c0 .32-.05.56-.55.74-.4.14-.83.21-1.28.21-2.64 0-5.26-2.62-5.26-5.26 0-.45.07-.88.21-1.28Z" /></svg>
                                                @break
                                            @case('facebook')
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 21v-7h2.35l.35-2.72h-2.7V9.55c0-.79.22-1.33 1.35-1.33h1.44V5.79c-.25-.03-1.1-.1-2.1-.1-2.08 0-3.5 1.27-3.5 3.6v1.99H8.34V14h2.35v7h2.81Z" /></svg>
                                                @break
                                        @endswitch
                                    </span>
                                @endif
                            </div>

                            <div class="appointment-main">
                                <div class="appointment-title-row">
                                    <a class="appointment-patient" href="{{ $viewUrl }}">{{ $patientName }}</a>
                                    <span class="appointment-status {{ $statusClass }}">{{ $this->statusLabel($appointment->status) }}</span>
                                </div>
                                <div class="appointment-line">
                                    <span>{{ $appointment->procedure?->name ?? 'Sin procedimiento' }}</span>
                                    <span class="appointment-dot-separator"></span>
                                    <span>{{ $appointment->doctor?->name ?? 'Sin doctor' }}</span>
                                </div>
                            </div>

                            <div class="appointment-side">
                                <span class="appointment-source {{ $sourceClass }}">
                                    {!! $sourceIcon !!}
                                    <span>{{ $appointment->source?->label() ?? 'Sin origen' }}</span>
                                </span>

                                @if ($appointment->isSynced())
                                    <span class="appointment-sync synced" title="Sincronizada">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                                    </span>
                                @else
                                    <span class="appointment-sync" title="Pendiente de sincronizacion"></span>
                                @endif

                                <div class="appointment-row-actions">

                                    <a class="appointment-row-icon-action" href="{{ $viewUrl }}" title="Ver detalle">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                    </a>
                                    <a class="appointment-row-icon-action" href="{{ $editUrl }}" title="Editar">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16.86 4.49 2.65 2.65M4 20h4.5L19.5 9a1.88 1.88 0 0 0 0-2.65L17.65 4.5a1.88 1.88 0 0 0-2.65 0L4 15.5V20Z"/></svg>
                                    </a>
                                    <button class="appointment-row-icon-action" type="button" title="Reprogramar" wire:click="openRescheduleModal({{ $appointment->id }})" @disabled(! in_array($appointment->status, [\App\Enums\AppointmentStatus::PendingConfirmation, \App\Enums\AppointmentStatus::Scheduled, \App\Enums\AppointmentStatus::Confirmed, \App\Enums\AppointmentStatus::Rescheduled], true))>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 11a8.1 8.1 0 0 0-15.5-2M4 5v4h4M4 13a8.1 8.1 0 0 0 15.5 2M20 19v-4h-4"/></svg>
                                    </button>

                                    <div class="appointment-more" x-data="{ open: false }" @keydown.escape.window="open = false">
                                        <button class="appointment-more-button" type="button" aria-label="Mas acciones" @click="open = ! open">
                                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="19" cy="12" r="1.7"/></svg>
                                        </button>

                                        <div class="appointment-action-menu" x-cloak x-show="open" x-transition @click.outside="open = false">
                                            <div class="appointment-action-heading">Acciones</div>
                                            <a class="appointment-action-item" href="{{ $viewUrl }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                                <span>Ver detalle</span>
                                            </a>
                                            <a class="appointment-action-item" href="{{ $editUrl }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16.86 4.49 2.65 2.65M4 20h4.5L19.5 9a1.88 1.88 0 0 0 0-2.65L17.65 4.5a1.88 1.88 0 0 0-2.65 0L4 15.5V20Z"/></svg>
                                                <span>Editar</span>
                                            </a>
                                            <button class="appointment-action-item" type="button" wire:click="openRescheduleModal({{ $appointment->id }})" @disabled(! in_array($appointment->status, [\App\Enums\AppointmentStatus::PendingConfirmation, \App\Enums\AppointmentStatus::Scheduled, \App\Enums\AppointmentStatus::Confirmed, \App\Enums\AppointmentStatus::Rescheduled], true))>
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 11a8.1 8.1 0 0 0-15.5-2M4 5v4h4M4 13a8.1 8.1 0 0 0 15.5 2M20 19v-4h-4"/></svg>
                                                <span>Reprogramar</span>
                                            </button>

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
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="appointments-empty">
                <div>
                    <img class="appointments-empty-icon dark:hidden" src="{{ asset('images/calendar-white.svg') }}" alt="Sin resultados" />
<img class="appointments-empty-icon hidden dark:block" src="{{ asset('images/calendar-dark.svg') }}" alt="Sin resultados" />
                    <div class="appointments-empty-title">No hay citas con estos filtros</div>
                    <div class="appointments-empty-copy">Prueba a limpiar filtros o crea una nueva cita.</div>
                    <button class="appointments-empty-action" type="button" wire:click="clearFilters">Limpiar filtros</button>
                </div>
            </div>
        @endforelse
    </section>

    <div
        x-cloak
        x-show="$wire.showRescheduleModal"
        x-transition.opacity.duration.200ms
        class="reschedule-overlay"
        @keydown.escape.window="$wire.closeRescheduleModal()"
    >
        <div class="reschedule-card" @click.outside="$wire.closeRescheduleModal()">
            <button class="reschedule-close" type="button" wire:click="closeRescheduleModal" aria-label="Cerrar modal">&times;</button>

            <header class="reschedule-header">
                <h2>Reprogramar cita</h2>
                <p class="reschedule-header-sub">Selecciona la nueva fecha y hora disponibles</p>
            </header>

            @error('newScheduledAt')
                <div class="reschedule-error">{{ $message }}</div>
            @enderror

            <div class="reschedule-field">
                <label class="reschedule-label" for="reschedule-date">Fecha y hora</label>
                <input
                    id="reschedule-date"
                    type="datetime-local"
                    wire:model="newScheduledAt"
                    class="reschedule-input"
                >
            </div>

            <div class="reschedule-field">
                <label class="reschedule-label" for="reschedule-duration">Duracion (minutos)</label>
                <input
                    id="reschedule-duration"
                    type="number"
                    wire:model="newDurationMinutes"
                    min="1"
                    class="reschedule-input"
                >
            </div>

            <footer class="reschedule-footer">
                <button type="button" class="reschedule-btn primary" wire:click="saveReschedule" style="width: 100%; justify-content: center; min-height: 2.25rem;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 11a8.1 8.1 0 0 0-15.5-2M4 5v4h4M4 13a8.1 8.1 0 0 0 15.5 2M20 19v-4h-4"/></svg>
                    Reprogramar
                </button>
            </footer>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
