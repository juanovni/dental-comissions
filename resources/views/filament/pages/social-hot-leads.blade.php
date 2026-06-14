<x-filament-panels::page>
    @php
        $stats = $this->stats();
        $leads = $this->leads();
        $operations = app(\App\Services\SocialLeadOperationsService::class);
        $statCards = [
            ['label' => 'En cola', 'value' => $stats['total'], 'tone' => 'primary', 'hint' => 'Leads por gestionar'],
            ['label' => 'Vencidos', 'value' => $stats['overdue'], 'tone' => 'danger', 'hint' => 'Sin contacto a tiempo'],
            ['label' => 'Seguimientos', 'value' => $stats['follow_up_due'], 'tone' => 'warning', 'hint' => 'Requieren accion hoy'],
            ['label' => 'Ficha pendiente', 'value' => $stats['pending_patient'], 'tone' => 'success', 'hint' => 'Listos para vincular'],
        ];
    @endphp

    <style>
        .hot-leads {
            --hl-bg-soft: #f8fafc;
            --hl-border: #e5e7eb;
            --hl-ink: #111827;
            --hl-muted: #6b7280;
            --hl-primary: #1d7afc;
            --hl-success: #0f766e;
            --hl-warning: #d97706;
            --hl-danger: #dc2626;
            color: var(--hl-ink);
        }

        .hot-leads-header {
            align-items: center;
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: .875rem;
        }

        .hot-leads-title {
            color: var(--hl-ink);
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: -.01em;
            margin: 0;
        }

        .hot-leads-subtitle {
            color: var(--hl-muted);
            font-size: .84rem;
            margin-top: .2rem;
        }

        .hot-live {
            align-items: center;
            color: var(--hl-muted);
            display: inline-flex;
            font-size: .75rem;
            font-weight: 500;
            gap: .45rem;
            white-space: nowrap;
        }

        .hot-live::before {
            background: #22c55e;
            border-radius: 999px;
            content: '';
            height: .45rem;
            width: .45rem;
        }

        .hot-stats {
            display: grid;
            gap: .75rem;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-bottom: .875rem;
        }

        .hot-stat {
            background: #ffffff;
            border: 1px solid var(--hl-border);
            border-radius: .75rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            display: grid;
            gap: .45rem;
            padding: 1rem;
        }

        .hot-stat-top {
            align-items: center;
            display: flex;
            justify-content: space-between;
            gap: .75rem;
        }

        .hot-stat-value {
            color: var(--hl-ink);
            font-size: 1.35rem;
            font-weight: 600;
            line-height: 1;
        }

        .hot-stat-label {
            color: #4b5563;
            font-size: .78rem;
            font-weight: 500;
        }

        .hot-stat-hint {
            color: var(--hl-muted);
            font-size: .75rem;
        }

        .hot-dot {
            border-radius: .45rem;
            height: .55rem;
            width: .55rem;
        }

        .hot-dot.primary { background: var(--hl-primary); }
        .hot-dot.success { background: var(--hl-success); }
        .hot-dot.warning { background: var(--hl-warning); }
        .hot-dot.danger { background: var(--hl-danger); }

        .hot-grid {
            display: grid;
            gap: .75rem;
        }

        .hot-card {
            background: #ffffff;
            border: 1px solid var(--hl-border);
            border-left: 3px solid var(--hl-primary);
            border-radius: .75rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            display: grid;
            gap: .85rem;
            padding: 1rem;
        }

        .hot-card.is-overdue {
            border-left-color: var(--hl-warning);
        }

        .hot-card-top {
            align-items: start;
            display: flex;
            gap: 1rem;
            justify-content: space-between;
        }

        .hot-lead-main {
            min-width: 0;
        }

        .hot-lead-name {
            color: var(--hl-ink);
            font-size: .95rem;
            font-weight: 600;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .hot-lead-meta {
            color: var(--hl-muted);
            font-size: .78rem;
            font-weight: 400;
            margin-top: .25rem;
        }

        .hot-badges,
        .hot-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
        }

        .hot-badges {
            justify-content: flex-end;
        }

        .hot-badge-score {
            display: grid;
            gap: .15rem;
            min-width: 5.2rem;
        }

        .hot-score-track {
            background: #e5e7eb;
            border-radius: 999px;
            display: block;
            height: .25rem;
            margin-top: .35rem;
            overflow: hidden;
            width: 100%;
        }

        .hot-score-bar {
            background: var(--hl-primary);
            border-radius: inherit;
            height: 100%;
        }

        .hot-message {
            background: var(--hl-bg-soft);
            border: 1px solid #eef2f7;
            border-radius: .625rem;
            color: #111827;
            font-size: .92rem;
            font-weight: 500;
            line-height: 1.5;
            padding: .8rem .9rem;
        }

        .hot-action-danger {
            border-color: #fecaca;
            color: #b91c1c;
        }

        .hot-action-success {
            background: var(--hl-success);
            color: #ffffff;
        }

        .hot-action-warning {
            border-color: #fed7aa;
            color: #b45309;
        }

        .hot-empty {
            align-items: center;
            background: #ffffff;
            border: 1px dashed #cbd5e1;
            border-radius: .875rem;
            color: var(--hl-muted);
            display: grid;
            gap: .35rem;
            justify-items: center;
            padding: 2rem;
            text-align: center;
        }

        .hot-empty strong {
            color: var(--hl-ink);
            font-size: .95rem;
            font-weight: 600;
        }

        [wire\:loading].hot-loading {
            color: var(--hl-muted);
            font-size: .75rem;
            font-weight: 500;
        }

        .dark .hot-leads {
            --hl-bg-soft: rgba(15, 23, 42, .72);
            --hl-border: rgba(148, 163, 184, .16);
            --hl-ink: #e5e7eb;
            --hl-muted: #94a3b8;
        }

        .dark .hot-stat,
        .dark .hot-card,
        .dark .hot-empty {
            background: rgba(15, 23, 42, .74);
            border-color: var(--hl-border);
        }

        .dark .hot-stat-label,
        .dark .hot-message {
            color: #cbd5e1;
        }

        .dark .hot-message {
            background: rgba(2, 6, 23, .36);
            border-color: rgba(148, 163, 184, .12);
        }

        .dark .hot-score-track {
            background: rgba(148, 163, 184, .22);
        }

        @media (max-width: 1080px) {
            .hot-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .hot-leads-header,
            .hot-card-top {
                display: grid;
            }

            .hot-badges {
                justify-content: flex-start;
            }
        }

        @media (max-width: 520px) {
            .hot-stats {
                grid-template-columns: minmax(0, 1fr);
            }

            .hot-actions .mc-btn {
                width: 100%;
            }
        }
    </style>

    <section class="hot-leads" wire:poll.15s>
        <div class="hot-leads-header">
            <div>
                <h2 class="hot-leads-title">Leads calientes</h2>
                <div class="hot-leads-subtitle">Prioriza contactos, seguimientos y fichas pendientes desde una cola operativa.</div>
            </div>

            <div class="hot-live">
                Actualizacion automatica
                <span class="hot-loading" wire:loading>Sincronizando...</span>
            </div>
        </div>

        <div class="hot-stats">
            @foreach ($statCards as $card)
                <section class="hot-stat">
                    <div class="hot-stat-top">
                        <span class="hot-stat-value">{{ $card['value'] }}</span>
                        <span class="hot-dot {{ $card['tone'] }}" aria-hidden="true"></span>
                    </div>
                    <div>
                        <div class="hot-stat-label">{{ $card['label'] }}</div>
                        <div class="hot-stat-hint">{{ $card['hint'] }}</div>
                    </div>
                </section>
            @endforeach
        </div>

        <div class="hot-grid">
            @forelse ($leads as $lead)
                @php
                    $patient = $lead->socialIdentity?->patient ?: $lead->convertedPatient;
                    $isOverdue = $operations->isOverdue($lead);
                    $detailUrl = \App\Filament\Resources\SocialComments\SocialCommentResource::getUrl('view', ['record' => $lead]);
                    $patientUrl = $patient ? \App\Filament\Resources\Patients\PatientResource::getUrl('edit', ['record' => $patient]) : null;
                    $score = min(100, max(0, (int) $lead->interest_score));
                    $leadName = $lead->author_username ? '@' . $lead->author_username : ($lead->author_name ?: 'Lead social');
                @endphp

                <article @class(['hot-card', 'is-overdue' => $isOverdue])>
                    <div class="hot-card-top">
                        <div class="hot-lead-main">
                            <div class="hot-lead-name">{{ $leadName }}</div>
                            <div class="hot-lead-meta">
                                {{ $lead->platform->label() }} · {{ $lead->created_at?->diffForHumans() }} · {{ $lead->suggestedProcedure?->name ?: 'Procedimiento pendiente' }}
                            </div>
                        </div>

                        <div class="hot-badges">
                            <span class="mc-badge hot-badge-score">
                                Score {{ $score }}
                                <span class="hot-score-track" aria-hidden="true">
                                    <span class="hot-score-bar" style="width: {{ $score }}%"></span>
                                </span>
                            </span>
                            @if ($lead->hot_lead_at)
                                <span class="mc-badge mc-badge-warning">Caliente</span>
                            @endif
                            @if ($lead->reheated_at)
                                <span class="mc-badge mc-badge-warning">Recalentado</span>
                            @endif
                            @if ($isOverdue)
                                <span class="mc-badge mc-badge-danger">Vencido</span>
                            @endif
                            @if ($lead->follow_up_at)
                                <span class="mc-badge">Seguimiento {{ $lead->follow_up_at->diffForHumans() }}</span>
                            @endif
                            @if ($lead->conversion_status === \App\Enums\SocialConversionStatus::PendingPatientCreation)
                                <span class="mc-badge mc-badge-success">Ficha pendiente</span>
                            @endif
                        </div>
                    </div>

                    <div class="hot-message">{{ $lead->comment_text }}</div>

                    <div class="hot-actions">
                        <button class="mc-btn hot-action-success" type="button" wire:click="markContacted({{ $lead->id }})" wire:loading.attr="disabled">Contactado</button>
                        <button class="mc-btn mc-btn-soft hot-action-warning" type="button" wire:click="scheduleFollowUp({{ $lead->id }})" wire:loading.attr="disabled">Seguimiento</button>
                        <button class="mc-btn mc-btn-soft hot-action-danger" type="button" wire:click="markLost({{ $lead->id }})" wire:confirm="Marcar este lead como perdido?" wire:loading.attr="disabled">Perdido</button>

                        @if ($patientUrl)
                            <a class="mc-btn mc-btn-primary" href="{{ $patientUrl }}">Ver ficha</a>
                        @else
                            <a class="mc-btn mc-btn-primary" href="{{ $detailUrl }}">Crear/Vincular ficha</a>
                        @endif

                        <a class="mc-btn mc-btn-soft" href="{{ $detailUrl }}">Detalle</a>
                    </div>
                </article>
            @empty
                <div class="hot-empty">
                    <strong>Sin leads pendientes</strong>
                    <span>No hay leads calientes pendientes de accion.</span>
                </div>
            @endforelse
        </div>

        <div class="mt-5">{{ $leads->links() }}</div>
    </section>
</x-filament-panels::page>
