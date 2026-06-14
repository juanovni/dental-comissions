<x-filament-panels::page>
    @php
        $stats = $this->stats();
        $alerts = $this->alerts();
        $filters = [
            'open' => ['label' => 'Abiertas', 'count' => $stats['open']],
            'resolved' => ['label' => 'Resueltas', 'count' => $stats['resolved']],
            'all' => ['label' => 'Todas', 'count' => $stats['open'] + $stats['resolved']],
        ];
    @endphp

    <style>
        .lead-alerts { color: #17201d; }
        .alert-top { align-items: center; display: flex; flex-wrap: wrap; gap: .75rem; justify-content: space-between; margin-bottom: 1rem; }
        .alert-stats { display: flex; flex-wrap: wrap; gap: .6rem; }
        .alert-pill { background: #fffaf1; border: 1px solid rgba(18,60,53,.12); border-radius: 999px; font-size: .78rem; font-weight: 900; padding: .55rem .8rem; }
        .alert-filter { background: #fff; border: 1px solid #e5e7eb; border-radius: .65rem; font-size: .78rem; font-weight: 850; padding: .55rem .75rem; }
        .alert-filter.active { background: #123c35; border-color: #123c35; color: #fff; }
        .alert-run { background: #2563eb; border: 0; border-radius: .65rem; color: #fff; font-size: .78rem; font-weight: 850; padding: .6rem .8rem; }
        .alert-grid { display: grid; gap: .75rem; }
        .alert-card { background: #fff; border: 1px solid #e5e7eb; border-left: 5px solid #64748b; border-radius: 1rem; padding: 1rem; }
        .alert-card.danger { border-left-color: #dc2626; background: linear-gradient(90deg, #fef2f2, #fff 34%); }
        .alert-card.warning { border-left-color: #f59e0b; background: linear-gradient(90deg, #fffbeb, #fff 34%); }
        .alert-head { align-items: start; display: flex; gap: 1rem; justify-content: space-between; }
        .alert-title { font-size: 1rem; font-weight: 950; }
        .alert-meta { color: #66736f; font-size: .78rem; font-weight: 750; margin-top: .25rem; }
        .alert-message { color: #111827; font-size: .92rem; line-height: 1.5; margin-top: .7rem; }
        .alert-actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .85rem; }
        .alert-action { border: 1px solid transparent; border-radius: .55rem; font-size: .78rem; font-weight: 850; padding: .55rem .75rem; }
        .alert-action.good { background: #0f766e; color: #fff; }
        .alert-action.primary { background: #2563eb; color: #fff; text-decoration: none; }
        .alert-empty { background: #fff; border: 1px dashed #cbd5e1; border-radius: 1.2rem; color: #64748b; padding: 2rem; text-align: center; }
    </style>

    <section class="lead-alerts" wire:poll.20s>
        <div class="alert-top">
            <div class="alert-stats">
                <span class="alert-pill">Abiertas: {{ $stats['open'] }}</span>
                <span class="alert-pill">Criticas: {{ $stats['danger'] }}</span>
                <span class="alert-pill">Advertencias: {{ $stats['warning'] }}</span>
            </div>
            <div class="alert-stats">
                @foreach ($filters as $key => $item)
                    <button @class(['alert-filter', 'active' => $filter === $key]) type="button" wire:click="setFilter('{{ $key }}')">
                        {{ $item['label'] }} {{ $item['count'] }}
                    </button>
                @endforeach
                <button class="alert-run" type="button" wire:click="runChecks">Revisar ahora</button>
            </div>
        </div>

        <div class="alert-grid">
            @forelse ($alerts as $alert)
                @php
                    $lead = $alert->socialComment;
                    $patient = $lead?->socialIdentity?->patient ?: $lead?->convertedPatient;
                @endphp
                <article @class(['alert-card', $alert->severity])>
                    <div class="alert-head">
                        <div>
                            <div class="alert-title">{{ $alert->title }}</div>
                            <div class="alert-meta">
                                {{ $lead?->author_username ? '@'.$lead->author_username : ($lead?->author_name ?: 'Lead social') }} ·
                                Score {{ $lead?->interest_score ?? 0 }} ·
                                {{ $patient?->full_name ?: 'Sin ficha vinculada' }} ·
                                {{ $alert->created_at?->diffForHumans() }}
                            </div>
                        </div>
                        <span class="alert-pill">{{ strtoupper($alert->severity) }}</span>
                    </div>
                    <div class="alert-message">{{ $alert->message }}</div>
                    <div class="alert-actions">
                        @if (! $alert->resolved_at)
                            <button class="alert-action good" type="button" wire:click="resolveAlert({{ $alert->id }})">Resolver</button>
                        @endif
                        @if ($lead)
                            <a class="alert-action primary" href="{{ $this->detailUrl($alert) }}">Abrir lead</a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="alert-empty">No hay alertas para este filtro.</div>
            @endforelse
        </div>

        <div class="mt-5">{{ $alerts->links() }}</div>
    </section>
</x-filament-panels::page>
