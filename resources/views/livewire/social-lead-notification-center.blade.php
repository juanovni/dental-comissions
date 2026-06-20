<div
    class="lead-notification-center"
    x-data="{ open: false }"
    x-on:keydown.escape.window="open = false"
    wire:poll.20s
>
    @php
        $stats = $this->stats;
        $alerts = $this->alerts;
        $hasUrgency = $urgentPulse || $stats['danger'] > 0;
        $tabs = [
            'all' => 'Todas',
            'danger' => 'Criticas',
            'warning' => 'Advertencias',
            'info' => 'Info',
        ];
    @endphp

    <style>
        .lead-notification-center {
            --ln-border: #e5e7eb;
            --ln-ink: #111827;
            --ln-muted: #6b7280;
            --ln-soft: #f8fafc;
            --ln-primary: #1d7afc;
            --ln-danger: #dc2626;
            --ln-warning: #d97706;
            --ln-success: #0f766e;
            align-items: center;
            display: inline-flex;
        }

        .ln-bell {
            align-items: center;
            background: #ffffff;
            border: 1px solid var(--ln-border);
            border-radius: .625rem;
            color: #4b5563;
            display: inline-flex;
            height: 2.25rem;
            justify-content: center;
            position: relative;
            transition: background-color .14s ease, border-color .14s ease, color .14s ease;
            width: 2.25rem;
        }

        .ln-bell:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            color: var(--ln-ink);
        }

        .ln-bell svg {
            height: 1.1rem;
            width: 1.1rem;
        }

        .ln-count {
            align-items: center;
            background: var(--ln-danger);
            border: 2px solid #ffffff;
            border-radius: 999px;
            color: #ffffff;
            display: inline-flex;
            font-size: .62rem;
            font-weight: 500;
            height: 1rem;
            justify-content: center;
            min-width: 1rem;
            padding: 0 .25rem;
            position: absolute;
            right: -.25rem;
            top: -.25rem;
        }

        .ln-urgent-dot {
            background: var(--ln-danger);
            border: 2px solid #ffffff;
            border-radius: 999px;
            bottom: .22rem;
            height: .52rem;
            position: absolute;
            right: .26rem;
            width: .52rem;
        }

        .ln-overlay {
            background: rgba(15, 23, 42, .26);
            inset: 0;
            position: fixed;
            z-index: 49;
        }

        .ln-panel {
            background: #ffffff;
            border: 1px solid var(--ln-border);
            border-radius: .875rem;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .08);
            color: var(--ln-ink);
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 2rem);
            overflow: hidden;
            position: fixed;
            right: 1rem;
            top: 1rem;
            width: min(30rem, calc(100vw - 2rem));
            z-index: 50;
        }

        .ln-header {
            align-items: center;
            border-bottom: 1px solid var(--ln-border);
            display: flex;
            gap: .75rem;
            justify-content: space-between;
            padding: .95rem 1rem;
        }

        .ln-title {
            color: var(--ln-ink);
            font-size: .98rem;
            font-weight: 600;
            line-height: 1.2;
        }

        .ln-subtitle {
            color: var(--ln-muted);
            font-size: .75rem;
            margin-top: .15rem;
        }

        .ln-close {
            align-items: center;
            background: transparent;
            border: 1px solid transparent;
            border-radius: .45rem;
            color: var(--ln-muted);
            display: inline-flex;
            height: 2rem;
            justify-content: center;
            width: 2rem;
        }

        .ln-close:hover {
            background: #f9fafb;
            border-color: var(--ln-border);
            color: var(--ln-ink);
        }

        .ln-tabs {
            align-items: center;
            border-bottom: 1px solid var(--ln-border);
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            padding: 0 1rem;
        }

        .ln-tab {
            align-items: center;
            background: transparent;
            border: 0;
            border-bottom: 2px solid transparent;
            color: #4b5563;
            display: inline-flex;
            font-size: .8rem;
            font-weight: 500;
            gap: .35rem;
            margin-bottom: -1px;
            padding: .75rem .02rem;
            white-space: nowrap;
        }

        .ln-tab.active {
            border-bottom-color: var(--ln-primary);
            color: var(--ln-primary);
        }

        .ln-tab-count {
            color: var(--ln-muted);
            font-size: .7rem;
            font-weight: 500;
        }

        .ln-body {
            display: grid;
            gap: .85rem;
            overflow-y: auto;
            padding: 1rem;
        }

        .ln-alert {
            background: #ffffff;
            border: 1px solid var(--ln-border);
            border-radius: .75rem;
            display: grid;
            gap: .7rem;
        }

        .ln-alert-head {
            align-items: start;
            display: flex;
            gap: .75rem;
        }

        .ln-severity-dot {
            border-radius: 999px;
            flex: 0 0 auto;
            height: .55rem;
            margin-top: .35rem;
            width: .55rem;
        }

        .ln-severity-dot.danger { background: #dc2626; }
        .ln-severity-dot.warning { background: #d97706; }
        .ln-severity-dot.info { background: #1d7afc; }

        .ln-alert-title {
            color: var(--ln-ink);
            font-size: .86rem;
            font-weight: 600;
            line-height: 1.35;
        }

        .ln-alert-meta {
            color: var(--ln-muted);
            font-size: .72rem;
            margin-top: .16rem;
        }

        .ln-message {
            background: var(--ln-soft);
            border: 1px solid #eef2f7;
            border-radius: .625rem;
            color: #111827;
            font-size: .8rem;
            line-height: 1.45;
            padding: .65rem .7rem;
        }

        .ln-actions,
        .ln-footer {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
        }

        .ln-footer {
            border-top: 1px solid var(--ln-border);
            padding: .9rem 1rem;
        }

        .ln-btn {
            align-items: center;
            background: #ffffff;
            border: 1px solid var(--ln-border);
            border-radius: .45rem;
            color: #111827;
            display: inline-flex;
            font-size: .76rem;
            font-weight: 500;
            justify-content: center;
            min-height: 2rem;
            padding: .38rem .65rem;
            text-decoration: none;
            transition: background-color .14s ease, border-color .14s ease, color .14s ease;
        }

        .ln-btn:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            color: #111827;
        }

        .ln-btn-primary {
            background: #000000;
            border-color: #000000;
            color: #ffffff;
        }

        .ln-btn-primary:hover {
            background: #1a1a1a;
            border-color: #1a1a1a;
            color: #ffffff;
        }

        .ln-empty {
            align-items: center;
            border: 1px dashed #cbd5e1;
            border-radius: .75rem;
            color: var(--ln-muted);
            display: grid;
            gap: .25rem;
            justify-items: center;
            padding: 2rem 1rem;
            text-align: center;
        }

        .ln-empty strong {
            color: var(--ln-ink);
            font-size: .88rem;
            font-weight: 600;
        }

        .dark .lead-notification-center {
            --ln-border: rgba(148, 163, 184, .18);
            --ln-ink: #e5e7eb;
            --ln-muted: #94a3b8;
            --ln-soft: rgba(15, 23, 42, .72);
        }

        .dark .ln-bell,
        .dark .ln-panel,
        .dark .ln-alert,
        .dark .ln-btn {
            background: rgba(15, 23, 42, .92);
            border-color: var(--ln-border);
        }

        .dark .ln-message,
        .dark .ln-btn {
            color: #e5e7eb;
        }

        .dark .ln-message {
            border-color: rgba(148, 163, 184, .14);
        }

        .dark .ln-close:hover,
        .dark .ln-bell:hover,
        .dark .ln-btn:hover {
            background: rgba(30, 41, 59, .86);
        }

        @media (max-width: 640px) {
            .ln-panel {
                border-radius: 0;
                max-height: 100vh;
                right: 0;
                top: 0;
                width: 100vw;
            }

            .ln-footer .ln-btn {
                flex: 1 1 0;
            }
        }
    </style>

    <button class="ln-bell" type="button" x-on:click="open = true" aria-label="Abrir notificaciones">
        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.86 17.08a2.25 2.25 0 0 1-5.72 0m10.02-2.14c-.8-.98-1.45-1.48-1.45-5.13A5.72 5.72 0 0 0 12 4.08a5.72 5.72 0 0 0-5.71 5.73c0 3.65-.65 4.15-1.45 5.13-.33.41-.04 1.02.49 1.02h13.34c.53 0 .82-.61.49-1.02Z" />
        </svg>
        @if ($stats['all'] > 0)
            <span class="ln-count">{{ $stats['all'] > 99 ? '99+' : $stats['all'] }}</span>
        @elseif ($hasUrgency)
            <span class="ln-urgent-dot" aria-hidden="true"></span>
        @endif
    </button>

    <template x-teleport="body">
        <div x-show="open" x-cloak>
            <div class="ln-overlay" x-on:click="open = false"></div>
            <aside class="ln-panel" aria-label="Centro de notificaciones">
                <header class="ln-header">
                    <div>
                        <div class="ln-title">Notificaciones</div>
                        <div class="ln-subtitle">{{ $stats['all'] }} alertas no leidas</div>
                    </div>

                    <button class="ln-close" type="button" x-on:click="open = false" aria-label="Cerrar notificaciones">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true" style="height:1rem;width:1rem">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </header>

                <nav class="ln-tabs" aria-label="Filtros de notificaciones">
                    @foreach ($tabs as $key => $label)
                        <button @class(['ln-tab', 'active' => $filter === $key]) type="button" wire:click="setFilter('{{ $key }}')">
                            <span>{{ $label }}</span>
                            <span class="ln-tab-count">{{ $stats[$key] }}</span>
                        </button>
                    @endforeach
                </nav>

                <div class="ln-body">
                    @forelse ($alerts as $alert)
                        @php
                            $lead = $alert->socialComment;
                            $patient = $lead?->socialIdentity?->patient ?: $lead?->convertedPatient;
                            $leadName = $lead?->author_username ? '@'.$lead->author_username : ($lead?->author_name ?: 'Lead social');
                            $severityLabel = match ($alert->severity) {
                                'danger' => 'Critica',
                                'warning' => 'Advertencia',
                                default => 'Info',
                            };
                        @endphp

                        <article class="ln-alert">
                            <div class="ln-alert-head">
                                <span class="ln-severity-dot {{ $alert->severity }}" aria-hidden="true"></span>
                                <div style="min-width:0;flex:1">
                                    <div class="ln-alert-title">{{ $alert->title }} · {{ $severityLabel }}</div>
                                    <div class="ln-alert-meta">
                                        {{ $leadName }} · Score {{ $lead?->interest_score ?? 0 }} · {{ $patient?->full_name ?: 'Sin ficha' }} · {{ $alert->created_at?->diffForHumans() }}
                                    </div>
                                </div>
                            </div>

                            <div class="ln-message">{{ $alert->message }}</div>

                            <div class="ln-actions">
                                <button class="ln-btn" type="button" wire:click="resolveAlert({{ $alert->id }})" wire:loading.attr="disabled">Resolver</button>
                                @if ($lead)
                                    <a class="ln-btn ln-btn-primary" href="{{ $this->leadUrl($alert) }}">Ver Lead</a>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="ln-empty">
                            <strong>Sin alertas abiertas</strong>
                            <span>No hay notificaciones para este filtro.</span>
                        </div>
                    @endforelse
                </div>

                <footer class="ln-footer">
                    <button class="ln-btn" type="button" wire:click="resolveAll" wire:loading.attr="disabled" @disabled($stats['all'] === 0)>Resolver todas</button>
                    <button class="ln-btn ln-btn-primary" type="button" wire:click="runChecks" wire:loading.attr="disabled">Revisar ahora</button>
                </footer>
            </aside>
        </div>
    </template>
</div>
