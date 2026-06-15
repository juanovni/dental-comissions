<x-filament-panels::page>
    @php
        $stats = $this->stats();
        $alerts = $this->alerts();
        $filters = [
            'open' => ['label' => 'Abiertas', 'count' => $stats['open']],
            'resolved' => ['label' => 'Resueltas', 'count' => $stats['resolved']],
            'all' => ['label' => 'Todas', 'count' => $stats['open'] + $stats['resolved']],
        ];
        $statCards = [
            ['label' => 'Abiertas', 'value' => $stats['open'], 'tone' => 'primary', 'hint' => 'Alertas por atender'],
            ['label' => 'Criticas', 'value' => $stats['danger'], 'tone' => 'danger', 'hint' => 'Requieren prioridad'],
            ['label' => 'Advertencias', 'value' => $stats['warning'], 'tone' => 'warning', 'hint' => 'Seguimiento recomendado'],
            ['label' => 'Resueltas', 'value' => $stats['resolved'], 'tone' => 'success', 'hint' => 'Historial cerrado'],
        ];
    @endphp

    <style>
        .lead-alerts {
            --la-bg-soft: #f8fafc;
            --la-border: #e5e7eb;
            --la-ink: #111827;
            --la-muted: #6b7280;
            --la-primary: #1d7afc;
            --la-success: #0f766e;
            --la-warning: #d97706;
            --la-danger: #dc2626;
            color: var(--la-ink);
        }

        .lead-alerts-header {
            align-items: center;
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            margin-bottom: .875rem;
        }

        .lead-alerts-title {
            color: var(--la-ink);
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: -.01em;
            margin: 0;
        }

        .lead-alerts-subtitle {
            color: var(--la-muted);
            font-size: .84rem;
            margin-top: .2rem;
        }

        .lead-alerts-toolbar {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            justify-content: flex-end;
        }

        .lead-alerts-live {
            align-items: center;
            color: var(--la-muted);
            display: inline-flex;
            font-size: .75rem;
            font-weight: 500;
            gap: .45rem;
            white-space: nowrap;
        }

        .lead-alerts-live::before {
            background: #22c55e;
            border-radius: 999px;
            content: '';
            height: .45rem;
            width: .45rem;
        }

        .lead-alerts-stats {
            display: grid;
            gap: .75rem;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-bottom: .875rem;
        }

        .lead-alerts-stat {
            background: #ffffff;
            border: 1px solid var(--la-border);
            border-radius: .75rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            display: grid;
            gap: .45rem;
            padding: 1rem;
        }

        .lead-alerts-stat-top {
            align-items: center;
            display: flex;
            gap: .75rem;
            justify-content: space-between;
        }

        .lead-alerts-stat-value {
            color: var(--la-ink);
            font-size: 1.35rem;
            font-weight: 600;
            line-height: 1;
        }

        .lead-alerts-stat-label {
            color: #4b5563;
            font-size: .78rem;
            font-weight: 500;
        }

        .lead-alerts-stat-hint {
            color: var(--la-muted);
            font-size: .75rem;
        }

        .lead-alerts-dot {
            border-radius: .45rem;
            height: .55rem;
            width: .55rem;
        }

        .lead-alerts-dot.primary { background: var(--la-primary); }
        .lead-alerts-dot.success { background: var(--la-success); }
        .lead-alerts-dot.warning { background: var(--la-warning); }
        .lead-alerts-dot.danger { background: var(--la-danger); }

        .lead-alerts-filters {
            align-items: center;
            border-bottom: 1px solid var(--la-border);
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: .875rem;
        }

        .lead-alerts-filter {
            align-items: center;
            background: transparent;
            border: 0;
            border-bottom: 2px solid transparent;
            color: #4b5563;
            display: inline-flex;
            font-size: .8rem;
            font-weight: 500;
            gap: .4rem;
            margin-bottom: -1px;
            padding: .65rem .05rem .75rem;
        }

        .lead-alerts-filter.active {
            border-bottom-color: var(--la-primary);
            color: var(--la-primary);
        }

        .lead-alerts-filter-count {
            align-items: center;
            background: #f3f4f6;
            border-radius: .375rem;
            color: #64748b;
            display: inline-flex;
            font-size: .7rem;
            font-weight: 500;
            min-width: 1.35rem;
            padding: .15rem .4rem;
        }

        .lead-alerts-filter.active .lead-alerts-filter-count {
            background: #eff6ff;
            color: var(--la-primary);
        }

        .lead-alerts-grid {
            display: grid;
            gap: .75rem;
        }

        .lead-alerts-card {
            background: #ffffff;
            border: 1px solid var(--la-border);
            border-left: 3px solid #94a3b8;
            border-radius: .75rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            display: grid;
            gap: .85rem;
            padding: 1rem;
        }

        .lead-alerts-card.danger {
            border-left-color: var(--la-danger);
        }

        .lead-alerts-card.warning {
            border-left-color: var(--la-warning);
        }

        .lead-alerts-card.info {
            border-left-color: var(--la-primary);
        }

        .lead-alerts-card-head {
            align-items: start;
            display: flex;
            gap: 1rem;
            justify-content: space-between;
        }

        .lead-alerts-card-title {
            color: var(--la-ink);
            font-size: .95rem;
            font-weight: 600;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .lead-alerts-card-meta {
            color: var(--la-muted);
            font-size: .78rem;
            font-weight: 400;
            margin-top: .25rem;
        }

        .lead-alerts-card-message {
            background: var(--la-bg-soft);
            border: 1px solid #eef2f7;
            border-radius: .625rem;
            color: #111827;
            font-size: .92rem;
            font-weight: 500;
            line-height: 1.5;
            padding: .8rem .9rem;
        }

        .lead-alerts-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
        }

        .lead-alerts-resolve {
            background: var(--la-success);
            color: #ffffff;
        }

        .lead-alerts-empty {
            align-items: center;
            background: #ffffff;
            border: 1px dashed #cbd5e1;
            border-radius: .875rem;
            color: var(--la-muted);
            display: grid;
            gap: .35rem;
            justify-items: center;
            padding: 2rem;
            text-align: center;
        }

        .lead-alerts-empty strong {
            color: var(--la-ink);
            font-size: .95rem;
            font-weight: 600;
        }

        .lead-alerts-loading {
            color: var(--la-muted);
            font-size: .75rem;
            font-weight: 500;
        }

        .dark .lead-alerts {
            --la-bg-soft: rgba(15, 23, 42, .72);
            --la-border: rgba(148, 163, 184, .16);
            --la-ink: #e5e7eb;
            --la-muted: #94a3b8;
        }

        .dark .lead-alerts-stat,
        .dark .lead-alerts-card,
        .dark .lead-alerts-empty {
            background: rgba(15, 23, 42, .74);
            border-color: var(--la-border);
        }

        .dark .lead-alerts-stat-label,
        .dark .lead-alerts-card-message {
            color: #cbd5e1;
        }

        .dark .lead-alerts-card-message {
            background: rgba(2, 6, 23, .36);
            border-color: rgba(148, 163, 184, .12);
        }

        .dark .lead-alerts-filter {
            color: #cbd5e1;
        }

        .dark .lead-alerts-filter-count {
            background: rgba(15, 23, 42, .86);
            color: #cbd5e1;
        }

        .dark .lead-alerts-filter.active .lead-alerts-filter-count {
            background: rgba(29, 122, 252, .18);
            color: #93c5fd;
        }

        @media (max-width: 1080px) {
            .lead-alerts-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .lead-alerts-header,
            .lead-alerts-card-head {
                display: grid;
            }

            .lead-alerts-toolbar {
                justify-content: flex-start;
            }
        }

        @media (max-width: 520px) {
            .lead-alerts-stats {
                grid-template-columns: minmax(0, 1fr);
            }

            .lead-alerts-actions .mc-btn,
            .lead-alerts-toolbar .mc-btn {
                width: 100%;
            }
        }
    </style>

    <section class="lead-alerts" wire:poll.20s>
        <div class="lead-alerts-header">
            <div>
                <div class="lead-alerts-subtitle">Monitorea oportunidades calientes, seguimientos vencidos y eventos que requieren accion.</div>
            </div>

            <div class="lead-alerts-toolbar">
                <span class="lead-alerts-live">
                    Actualizacion automatica
                    <span class="lead-alerts-loading" wire:loading>Sincronizando...</span>
                </span>
                <button class="mc-btn mc-btn-primary" type="button" wire:click="runChecks" wire:loading.attr="disabled">Revisar ahora</button>
            </div>
        </div>

        <div class="lead-alerts-stats">
            @foreach ($statCards as $card)
                <section class="lead-alerts-stat">
                    <div class="lead-alerts-stat-top">
                        <span class="lead-alerts-stat-value">{{ $card['value'] }}</span>
                        <span class="lead-alerts-dot {{ $card['tone'] }}" aria-hidden="true"></span>
                    </div>
                    <div>
                        <div class="lead-alerts-stat-label">{{ $card['label'] }}</div>
                        <div class="lead-alerts-stat-hint">{{ $card['hint'] }}</div>
                    </div>
                </section>
            @endforeach
        </div>

        <div class="lead-alerts-filters">
            @foreach ($filters as $key => $item)
                <button @class(['lead-alerts-filter', 'active' => $filter === $key]) type="button" wire:click="setFilter('{{ $key }}')">
                    <span>{{ $item['label'] }}</span>
                    <strong class="lead-alerts-filter-count">{{ $item['count'] }}</strong>
                </button>
            @endforeach
        </div>

        <div class="lead-alerts-grid">
            @forelse ($alerts as $alert)
                @php
                    $lead = $alert->socialComment;
                    $patient = $lead?->socialIdentity?->patient ?: $lead?->convertedPatient;
                    $leadName = $lead?->author_username ? '@'.$lead->author_username : ($lead?->author_name ?: 'Lead social');
                    $severityLabel = match ($alert->severity) {
                        'danger' => 'Critica',
                        'warning' => 'Advertencia',
                        default => 'Informativa',
                    };
                    $severityClass = match ($alert->severity) {
                        'danger' => 'mc-badge-danger',
                        'warning' => 'mc-badge-warning',
                        default => '',
                    };
                @endphp

                <article @class(['lead-alerts-card', $alert->severity])>
                    <div class="lead-alerts-card-head">
                        <div>
                            <div class="lead-alerts-card-title">{{ $alert->title }}</div>
                            <div class="lead-alerts-card-meta">
                                {{ $leadName }} · Score {{ $lead?->interest_score ?? 0 }} · {{ $patient?->full_name ?: 'Sin ficha vinculada' }} · {{ $alert->created_at?->diffForHumans() }}
                            </div>
                        </div>

                        <span @class(['mc-badge', $severityClass])>{{ $severityLabel }}</span>
                    </div>

                    <div class="lead-alerts-card-message">{{ $alert->message }}</div>

                    <div class="lead-alerts-actions">
                        @if (! $alert->resolved_at)
                            <button class="mc-btn lead-alerts-resolve" type="button" wire:click="resolveAlert({{ $alert->id }})" wire:loading.attr="disabled">Resolver</button>
                        @else
                            <span class="mc-badge mc-badge-success">Resuelta {{ $alert->resolved_at->diffForHumans() }}</span>
                        @endif

                        @if ($lead)
                            <a class="mc-btn mc-btn-primary" href="{{ $this->detailUrl($alert) }}">Abrir lead</a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="lead-alerts-empty">
                    <strong>Sin alertas</strong>
                    <span>No hay alertas para este filtro.</span>
                </div>
            @endforelse
        </div>

        <div class="mt-5">{{ $alerts->links() }}</div>
    </section>
</x-filament-panels::page>
