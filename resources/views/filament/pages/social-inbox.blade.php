<x-filament-panels::page>
    @php
        $stats = $this->stats();
        $comments = $this->comments();
        $filters = [
            'leads' => ['label' => 'Leads', 'icon' => '🔥', 'count' => $stats['leads']],
            'crisis' => ['label' => 'Crisis', 'icon' => '🚨', 'count' => $stats['crisis']],
            'vip' => ['label' => 'Pacientes VIP', 'icon' => '🏥', 'count' => $stats['vip']],
            'medical' => ['label' => 'Atencion Medica', 'icon' => '🩺', 'count' => $stats['medical']],
            'all' => ['label' => 'Ver Todos', 'icon' => '🔍', 'count' => $stats['all']],
        ];
    @endphp

    <style>
        .smart-inbox {
            --inbox-ink: #0f172a;
            --inbox-muted: #64748b;
            --inbox-line: rgba(15, 23, 42, .08);
            --inbox-card: rgba(255, 255, 255, .94);
            background:
                radial-gradient(circle at 0 0, rgba(20, 184, 166, .14), transparent 24rem),
                radial-gradient(circle at 100% 8%, rgba(245, 158, 11, .12), transparent 22rem),
                linear-gradient(180deg, rgba(248, 250, 252, .96), rgba(255, 255, 255, .98));
            border: 1px solid var(--inbox-line);
            border-radius: 2rem;
            color: var(--inbox-ink);
            padding: clamp(1rem, 2vw, 1.5rem);
        }

        .smart-inbox-header {
            align-items: start;
            display: grid;
            gap: 1rem;
            grid-template-columns: minmax(0, 1fr);
            margin-bottom: 1rem;
        }

        @media (min-width: 980px) {
            .smart-inbox-header {
                grid-template-columns: minmax(0, .9fr) minmax(18rem, .42fr);
            }
        }

        .smart-inbox-title {
            font-size: clamp(1.55rem, 3vw, 2.55rem);
            font-weight: 950;
            letter-spacing: -.065em;
            line-height: .95;
        }

        .smart-inbox-subtitle {
            color: var(--inbox-muted);
            font-size: .95rem;
            margin-top: .55rem;
        }

        .smart-search {
            background: rgba(255, 255, 255, .86);
            border: 1px solid var(--inbox-line);
            border-radius: 999px;
            box-shadow: 0 26px 80px -68px rgba(15, 23, 42, .9);
            color: var(--inbox-ink);
            outline: none;
            padding: .95rem 1.1rem;
            width: 100%;
        }

        .smart-filters {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
            margin: 1rem 0 1.25rem;
        }

        .smart-filter {
            align-items: center;
            background: rgba(255, 255, 255, .78);
            border: 1px solid var(--inbox-line);
            border-radius: 999px;
            color: #334155;
            display: inline-flex;
            font-size: .84rem;
            font-weight: 850;
            gap: .45rem;
            padding: .68rem .9rem;
            transition: .18s ease;
        }

        .smart-filter:hover,
        .smart-filter.is-active {
            background: #0f172a;
            border-color: #0f172a;
            color: white;
            transform: translateY(-1px);
        }

        .smart-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: minmax(0, 1fr);
        }

        @media (min-width: 1120px) {
            .smart-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .smart-card {
            background: var(--inbox-card);
            border: 1px solid var(--inbox-line);
            border-left: 6px solid #14b8a6;
            border-radius: 1.55rem;
            box-shadow: 0 26px 90px -74px rgba(15, 23, 42, .95);
            display: grid;
            gap: 1rem;
            overflow: hidden;
            padding: clamp(1rem, 1.5vw, 1.25rem);
            position: relative;
        }

        .smart-card.intent-crisis {
            border-left-color: #dc2626;
            box-shadow: 0 0 0 1px rgba(220, 38, 38, .08), 0 30px 95px -74px rgba(127, 29, 29, .9);
        }

        .smart-card.intent-lead { border-left-color: #0891b2; }
        .smart-card.intent-vip { border-left-color: #16a34a; }
        .smart-card.intent-medical { border-left-color: #f59e0b; }

        .smart-card.intent-crisis.risk-critical::after {
            animation: inboxPulse 1.25s ease-in-out infinite;
            background: radial-gradient(circle, rgba(239, 68, 68, .24), transparent 62%);
            content: '';
            height: 8rem;
            inset: -3rem -3rem auto auto;
            position: absolute;
            width: 8rem;
        }

        @keyframes inboxPulse {
            0%, 100% { opacity: .45; transform: scale(.96); }
            50% { opacity: .95; transform: scale(1.08); }
        }

        .smart-card-top {
            align-items: start;
            display: flex;
            gap: .85rem;
            justify-content: space-between;
        }

        .smart-person {
            display: flex;
            gap: .75rem;
            min-width: 0;
        }

        .smart-avatar {
            align-items: center;
            background: linear-gradient(145deg, #0f766e, #14b8a6);
            border-radius: 1rem;
            color: white;
            display: flex;
            flex: 0 0 auto;
            font-size: .92rem;
            font-weight: 950;
            height: 2.7rem;
            justify-content: center;
            text-transform: uppercase;
            width: 2.7rem;
        }

        .intent-crisis .smart-avatar { background: linear-gradient(145deg, #991b1b, #ef4444); }
        .intent-lead .smart-avatar { background: linear-gradient(145deg, #155e75, #06b6d4); }
        .intent-vip .smart-avatar { background: linear-gradient(145deg, #166534, #22c55e); }
        .intent-medical .smart-avatar { background: linear-gradient(145deg, #92400e, #f59e0b); }

        .smart-user {
            color: var(--inbox-ink);
            font-size: 1rem;
            font-weight: 900;
            line-height: 1.15;
            overflow-wrap: anywhere;
        }

        .smart-source,
        .smart-time {
            color: var(--inbox-muted);
            font-size: .78rem;
            font-weight: 700;
            margin-top: .25rem;
        }

        .smart-badges {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }

        .smart-badge {
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 900;
            letter-spacing: .06em;
            padding: .38rem .55rem;
            text-transform: uppercase;
        }

        .smart-badge.danger { background: #fef2f2; color: #b91c1c; }
        .smart-badge.info { background: #ecfeff; color: #0e7490; }
        .smart-badge.warning { background: #fffbeb; color: #b45309; }
        .smart-badge.success { background: #ecfdf5; color: #047857; }
        .smart-badge.neutral { background: #f1f5f9; color: #475569; }

        .smart-message {
            color: #111827;
            font-size: clamp(1rem, 1.45vw, 1.18rem);
            font-weight: 750;
            letter-spacing: -.025em;
            line-height: 1.42;
        }

        .smart-panels {
            display: grid;
            gap: .75rem;
            grid-template-columns: minmax(0, 1fr);
        }

        @media (min-width: 760px) {
            .smart-panels {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .smart-panel {
            background: linear-gradient(180deg, rgba(248, 250, 252, .9), rgba(255, 255, 255, .96));
            border: 1px solid var(--inbox-line);
            border-radius: 1.05rem;
            padding: .85rem;
        }

        .smart-panel h3 {
            color: #475569;
            font-size: .68rem;
            font-weight: 950;
            letter-spacing: .12em;
            margin: 0 0 .5rem;
            text-transform: uppercase;
        }

        .smart-panel p,
        .smart-panel strong {
            color: #0f172a;
            font-size: .86rem;
            line-height: 1.45;
            margin: 0;
        }

        .smart-muted { color: #64748b !important; }

        .smart-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .smart-action {
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 900;
            padding: .62rem .82rem;
            transition: .18s ease;
        }

        .smart-action:hover { transform: translateY(-1px); }
        .smart-action.primary { background: #0f172a; color: white; }
        .smart-action.success { background: #0f766e; color: white; }
        .smart-action.warning { background: #fffbeb; color: #b45309; }
        .smart-action.danger { background: #fef2f2; color: #b91c1c; }
        .smart-action.muted { background: #f1f5f9; color: #475569; }

        .smart-empty {
            background: rgba(255, 255, 255, .86);
            border: 1px dashed rgba(15, 23, 42, .15);
            border-radius: 1.6rem;
            color: #475569;
            padding: 2rem;
            text-align: center;
        }
    </style>

    <section class="smart-inbox">
        <header class="smart-inbox-header">
            <div>
                <div class="smart-inbox-title">Smart Inbox</div>
                <p class="smart-inbox-subtitle">Comentarios priorizados por intencion, riesgo y contexto clinico.</p>
            </div>

            <input
                class="smart-search"
                type="search"
                wire:model.live.debounce.350ms="search"
                placeholder="Buscar por comentario, autor o usuario"
            />
        </header>

        <div class="smart-filters">
            @foreach ($filters as $key => $item)
                <button
                    type="button"
                    wire:click="setFilter('{{ $key }}')"
                    @class(['smart-filter', 'is-active' => $filter === $key])
                >
                    <span>{{ $item['icon'] }}</span>
                    <span>{{ $item['label'] }}</span>
                    <strong>{{ $item['count'] }}</strong>
                </button>
            @endforeach
        </div>

        <div class="smart-grid">
            @forelse ($comments as $comment)
                @php
                    $risk = $comment->reputation_risk?->value ?? 'low';
                    $classification = $comment->classification;
                    $patient = $comment->socialIdentity?->patient ?: $comment->convertedPatient;
                    $lastActivity = $patient?->activityRecords?->sortByDesc('activity_date')->sortByDesc('id')->first();
                    $isLead = in_array($classification, [
                        \App\Enums\SocialCommentClassification::SalesLead,
                        \App\Enums\SocialCommentClassification::CommercialQuestion,
                    ], true);
                    $isCrisis = in_array($risk, ['high', 'critical'], true) || in_array($classification, [
                        \App\Enums\SocialCommentClassification::Complaint,
                        \App\Enums\SocialCommentClassification::NegativeOpinion,
                        \App\Enums\SocialCommentClassification::LegalSensitive,
                    ], true);
                    $isMedical = $classification === \App\Enums\SocialCommentClassification::MedicalSensitive;
                    $isVip = filled($patient) && ($patient->activityRecords?->count() ?? 0) > 0;
                    $intent = $isCrisis ? 'crisis' : ($isVip ? 'vip' : ($isMedical ? 'medical' : ($isLead ? 'lead' : 'normal')));
                    $intentTitle = match ($intent) {
                        'crisis' => 'RIESGO CRITICO',
                        'vip' => 'PACIENTE VIP',
                        'medical' => 'ATENCION MEDICA',
                        'lead' => 'LEAD COMERCIAL',
                        default => 'COMENTARIO SOCIAL',
                    };
                    $initial = \Illuminate\Support\Str::of($comment->author_name ?: $comment->author_username ?: '?')->substr(0, 1)->upper();
                    $detailUrl = \App\Filament\Resources\SocialComments\SocialCommentResource::getUrl('view', ['record' => $comment]);
                    $patientUrl = $patient ? \App\Filament\Resources\Patients\PatientResource::getUrl('edit', ['record' => $patient]) : null;
                @endphp

                <article class="smart-card intent-{{ $intent }} risk-{{ $risk }}">
                    <div class="smart-card-top">
                        <div class="smart-person">
                            <div class="smart-avatar">{{ $initial }}</div>
                            <div>
                                <div class="smart-user">
                                    {{ $comment->author_username ? '@' . $comment->author_username : ($comment->author_name ?: 'Autor desconocido') }}
                                </div>
                                <div class="smart-source">via {{ $comment->platform->label() }} / {{ $intentTitle }}</div>
                                <div class="smart-time">{{ $comment->created_at?->diffForHumans() }}</div>
                            </div>
                        </div>

                        <div class="smart-badges">
                            <span @class(['smart-badge', 'danger' => $isCrisis, 'info' => $isLead, 'warning' => $isMedical, 'success' => $isVip, 'neutral' => ! $isCrisis && ! $isLead && ! $isMedical && ! $isVip])>
                                {{ $classification?->label() ?? 'Sin clasificar' }}
                            </span>
                            <span @class(['smart-badge', 'danger' => in_array($risk, ['high', 'critical'], true), 'warning' => $risk === 'medium', 'neutral' => $risk === 'low'])>
                                Riesgo {{ $comment->reputation_risk?->label() ?? 'bajo' }}
                            </span>
                            <span class="smart-badge neutral">{{ $comment->sentiment?->label() ?? 'Sin sentimiento' }}</span>
                        </div>
                    </div>

                    <div class="smart-message">"{{ $comment->comment_text }}"</div>

                    <div class="smart-panels">
                        <section class="smart-panel">
                            <h3>Contexto Clinico</h3>
                            @if ($patient)
                                <p><strong>Paciente vinculado:</strong> {{ $patient->full_name }}</p>
                                <p class="smart-muted">{{ $lastActivity ? 'Ultima cita: ' . $lastActivity->activity_date?->format('d/m/Y') : 'Sin cita registrada' }}</p>
                                <p class="smart-muted">{{ $lastActivity?->doctor?->name ?: 'Doctor no registrado' }}</p>
                            @else
                                <p><strong>Nuevo lead:</strong> Sin ficha clinica</p>
                                <p class="smart-muted">Telefono: {{ $comment->socialIdentity?->phone ?: 'pendiente de capturar' }}</p>
                                <p class="smart-muted">Procedimiento: {{ $comment->suggestedProcedure?->name ?: 'sin sugerencia' }}</p>
                            @endif
                        </section>

                        <section class="smart-panel">
                            <h3>Sugerencia IA</h3>
                            <p>{{ $comment->suggested_reply ?: 'Sin respuesta sugerida. Revisar contexto antes de responder.' }}</p>
                            @if ($comment->ai_reason)
                                <p class="smart-muted" style="margin-top:.45rem">Motivo: {{ $comment->ai_reason }}</p>
                            @endif
                        </section>
                    </div>

                    <div class="smart-actions">
                        @if ($isCrisis)
                            <button class="smart-action danger" type="button" wire:click="escalate({{ $comment->id }})">Escalar a Director</button>
                        @endif

                        @if ($isLead || blank($comment->tracking_token))
                            <button class="smart-action success" type="button" wire:click="routeToWhatsapp({{ $comment->id }})">Derivar a WhatsApp</button>
                        @endif

                        @if ($patientUrl)
                            <a class="smart-action primary" href="{{ $patientUrl }}">Ver Ficha</a>
                        @else
                            <a class="smart-action primary" href="{{ $detailUrl }}">Crear Ficha</a>
                        @endif

                        <a class="smart-action muted" href="{{ $detailUrl }}">Detalle</a>
                        <button class="smart-action muted" type="button" wire:click="markReviewed({{ $comment->id }})">Revisado</button>
                        <button class="smart-action warning" type="button" wire:click="ignore({{ $comment->id }})">Ignorar</button>
                        <button class="smart-action danger" type="button" wire:click="markSpam({{ $comment->id }})">Spam</button>
                    </div>
                </article>
            @empty
                <div class="smart-empty">
                    No hay comentarios para este segmento. Cambia el filtro o sincroniza nuevos comentarios sociales.
                </div>
            @endforelse
        </div>

        <div class="mt-5">
            {{ $comments->links() }}
        </div>
    </section>
</x-filament-panels::page>
