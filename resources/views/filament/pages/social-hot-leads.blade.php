<x-filament-panels::page>
    @php
        $stats = $this->stats();
        $leads = $this->leads();
        $operations = app(\App\Services\SocialLeadOperationsService::class);
    @endphp

    <style>
        .hot-leads {
            --ink: #17201d;
            --muted: #66736f;
            --line: rgba(18, 60, 53, .12);
            --deep: #123c35;
            --hot: #f97316;
            color: var(--ink);
        }

        .hot-stats {
            display: grid;
            gap: .8rem;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-bottom: 1rem;
        }

        .hot-stat {
            background: #fffaf1;
            border: 1px solid var(--line);
            border-radius: 1rem;
            padding: 1rem;
        }

        .hot-stat strong { display: block; font-size: 1.65rem; line-height: 1; }
        .hot-stat span { color: var(--muted); font-size: .78rem; font-weight: 800; text-transform: uppercase; }

        .hot-grid { display: grid; gap: .85rem; }

        .hot-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-left: 5px solid var(--hot);
            border-radius: 1rem;
            box-shadow: 0 18px 55px -50px rgba(15, 23, 42, .8);
            display: grid;
            gap: .85rem;
            padding: 1rem;
        }

        .hot-card.is-overdue {
            background: linear-gradient(90deg, #fff7ed, #ffffff 38%);
            border-color: #fed7aa;
        }

        .hot-top {
            align-items: start;
            display: flex;
            gap: 1rem;
            justify-content: space-between;
        }

        .hot-title { font-size: 1rem; font-weight: 900; }
        .hot-meta { color: var(--muted); font-size: .78rem; font-weight: 700; margin-top: .2rem; }
        .hot-message { color: #111827; font-size: .95rem; font-weight: 650; line-height: 1.48; }

        .hot-badges, .hot-actions { display: flex; flex-wrap: wrap; gap: .45rem; }
        .hot-badge {
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 900;
            padding: .35rem .55rem;
            text-transform: uppercase;
        }
        .hot-badge.hot { background: #fff7ed; color: #c2410c; }
        .hot-badge.warn { background: #fffbeb; color: #b45309; }
        .hot-badge.good { background: #ecfdf5; color: #047857; }
        .hot-badge.neutral { background: #f1f5f9; color: #475569; }

        .hot-action {
            border: 1px solid transparent;
            border-radius: .55rem;
            font-size: .78rem;
            font-weight: 800;
            padding: .58rem .78rem;
        }
        .hot-action.good { background: #0f766e; color: #fff; }
        .hot-action.warn { background: #fff; border-color: #fed7aa; color: #b45309; }
        .hot-action.danger { background: #fff; border-color: #fecaca; color: #b91c1c; }
        .hot-action.primary { background: #2563eb; color: #fff; text-decoration: none; }

        .hot-empty {
            background: #fff;
            border: 1px dashed #cbd5e1;
            border-radius: 1.25rem;
            color: var(--muted);
            padding: 2rem;
            text-align: center;
        }

        @media (max-width: 860px) {
            .hot-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .hot-top { display: grid; }
        }
    </style>

    <section class="hot-leads" wire:poll.15s>
        <div class="hot-stats">
            <div class="hot-stat"><strong>{{ $stats['total'] }}</strong><span>En cola</span></div>
            <div class="hot-stat"><strong>{{ $stats['overdue'] }}</strong><span>Vencidos</span></div>
            <div class="hot-stat"><strong>{{ $stats['follow_up_due'] }}</strong><span>Seguimientos</span></div>
            <div class="hot-stat"><strong>{{ $stats['pending_patient'] }}</strong><span>Ficha pendiente</span></div>
        </div>

        <div class="hot-grid">
            @forelse ($leads as $lead)
                @php
                    $patient = $lead->socialIdentity?->patient ?: $lead->convertedPatient;
                    $isOverdue = $operations->isOverdue($lead);
                    $detailUrl = \App\Filament\Resources\SocialComments\SocialCommentResource::getUrl('view', ['record' => $lead]);
                    $patientUrl = $patient ? \App\Filament\Resources\Patients\PatientResource::getUrl('edit', ['record' => $patient]) : null;
                @endphp

                <article @class(['hot-card', 'is-overdue' => $isOverdue])>
                    <div class="hot-top">
                        <div>
                            <div class="hot-title">{{ $lead->author_username ? '@' . $lead->author_username : ($lead->author_name ?: 'Lead social') }}</div>
                            <div class="hot-meta">{{ $lead->platform->label() }} · {{ $lead->created_at?->diffForHumans() }} · {{ $lead->suggestedProcedure?->name ?: 'Procedimiento pendiente' }}</div>
                        </div>
                        <div class="hot-badges">
                            <span class="hot-badge hot">Score {{ $lead->interest_score }}</span>
                            @if ($lead->hot_lead_at)<span class="hot-badge hot">🔥 Caliente</span>@endif
                            @if ($lead->reheated_at)<span class="hot-badge warn">Recalentado</span>@endif
                            @if ($isOverdue)<span class="hot-badge warn">Vencido</span>@endif
                            @if ($lead->follow_up_at)<span class="hot-badge neutral">Seguimiento {{ $lead->follow_up_at->diffForHumans() }}</span>@endif
                            @if ($lead->conversion_status === \App\Enums\SocialConversionStatus::PendingPatientCreation)<span class="hot-badge good">Ficha pendiente</span>@endif
                        </div>
                    </div>

                    <div class="hot-message">"{{ $lead->comment_text }}"</div>

                    <div class="hot-actions">
                        <button class="hot-action good" type="button" wire:click="markContacted({{ $lead->id }})">Contactado</button>
                        <button class="hot-action warn" type="button" wire:click="scheduleFollowUp({{ $lead->id }})">Seguimiento</button>
                        <button class="hot-action danger" type="button" wire:click="markLost({{ $lead->id }})">Perdido</button>
                        @if ($patientUrl)
                            <a class="hot-action primary" href="{{ $patientUrl }}">Ver ficha</a>
                        @else
                            <a class="hot-action primary" href="{{ $detailUrl }}">Crear/Vincular ficha</a>
                        @endif
                        <a class="hot-action primary" href="{{ $detailUrl }}">Detalle</a>
                    </div>
                </article>
            @empty
                <div class="hot-empty">No hay leads calientes pendientes de accion.</div>
            @endforelse
        </div>

        <div class="mt-5">{{ $leads->links() }}</div>
    </section>
</x-filament-panels::page>
