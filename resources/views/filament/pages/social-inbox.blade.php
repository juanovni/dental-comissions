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
        .social-inbox-page {
            --inbox-ink: #0f172a;
            --inbox-muted: #64748b;
            --inbox-line: rgba(15, 23, 42, .08);
            --inbox-card: #ffffff;
            color: var(--inbox-ink);
            margin-top: -.25rem;
        }

        .social-inbox-toolbar {
            align-items: center;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-bottom: 1rem;
        }

        @media (min-width: 900px) {
            .social-inbox-toolbar {
                margin-top: -4.05rem;
            }
        }

        @media (max-width: 760px) {
            .social-inbox-toolbar {
                justify-content: stretch;
                margin-bottom: .9rem;
            }
        }

        .smart-search {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: .85rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            color: var(--inbox-ink);
            outline: none;
            padding: .82rem 1rem;
            width: min(100%, 32rem);
        }

        .smart-filters {
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            flex-wrap: wrap;
            gap: 1.35rem;
            margin: 0 0 1rem;
        }

        .smart-filter {
            align-items: center;
            background: transparent;
            border: 0;
            border-bottom: 2px solid transparent;
            border-radius: 0;
            color: #334155;
            display: inline-flex;
            font-size: .84rem;
            font-weight: 700;
            gap: .4rem;
            margin-bottom: -1px;
            padding: .65rem .05rem .8rem;
            transition: border-color .18s ease, color .18s ease;
        }

        .smart-filter:hover {
            color: #0f766e;
        }

        .smart-filter.is-active {
            border-bottom-color: #0f766e;
            color: #0f766e;
        }

        .smart-filter-icon {
            font-size: .9rem;
            line-height: 1;
        }

        .smart-filter-count {
            align-items: center;
            background: #f1f5f9;
            border-radius: 999px;
            color: #64748b;
            display: inline-flex;
            font-size: .72rem;
            font-weight: 800;
            height: 1.25rem;
            justify-content: center;
            min-width: 1.25rem;
            padding: 0 .38rem;
        }

        .smart-filter.is-active .smart-filter-count {
            background: #ccfbf1;
            color: #0f766e;
        }

        @media (max-width: 760px) {
            .smart-filters {
                gap: 1rem;
                overflow-x: auto;
                padding-bottom: .1rem;
            }

            .smart-filter {
                flex: 0 0 auto;
            }
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
            border: 1px solid #e5e7eb;
            border-top: 4px solid #14b8a6;
            border-radius: 1rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 18px 50px -48px rgba(15, 23, 42, .65);
            display: grid;
            gap: .9rem;
            overflow: hidden;
            padding: clamp(1rem, 1.5vw, 1.2rem);
            position: relative;
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }

        .smart-card:hover {
            box-shadow: 0 3px 12px rgba(15, 23, 42, .06), 0 24px 70px -56px rgba(15, 23, 42, .75);
            transform: translateY(-1px);
        }

        .smart-card.intent-crisis {
            border-top-color: #dc2626;
        }

        .smart-card.intent-lead { border-top-color: #2563eb; }
        .smart-card.intent-vip { border-top-color: #16a34a; }
        .smart-card.intent-medical { border-top-color: #f59e0b; }

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
            background: #f1f5f9;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            color: #0f766e;
            display: flex;
            flex: 0 0 auto;
            font-size: .9rem;
            font-weight: 850;
            height: 2.85rem;
            justify-content: center;
            text-transform: uppercase;
            width: 2.85rem;
        }

        .intent-crisis .smart-avatar { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
        .intent-lead .smart-avatar { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
        .intent-vip .smart-avatar { background: #ecfdf5; border-color: #bbf7d0; color: #047857; }
        .intent-medical .smart-avatar { background: #fffbeb; border-color: #fed7aa; color: #b45309; }

        .smart-user {
            color: var(--inbox-ink);
            font-size: 1rem;
            font-weight: 800;
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
            font-weight: 800;
            letter-spacing: .02em;
            padding: .34rem .52rem;
            text-transform: uppercase;
        }

        .smart-badge.danger { background: #fef2f2; color: #b91c1c; }
        .smart-badge.info { background: #ecfeff; color: #0e7490; }
        .smart-badge.warning { background: #fffbeb; color: #b45309; }
        .smart-badge.success { background: #ecfdf5; color: #047857; }
        .smart-badge.neutral { background: #f1f5f9; color: #475569; }

        .smart-message {
            color: #111827;
            font-size: clamp(.98rem, 1.35vw, 1.08rem);
            font-weight: 650;
            letter-spacing: -.015em;
            line-height: 1.5;
            padding: .2rem 0;
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
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: .85rem;
            padding: .85rem;
        }

        .smart-ai-panel {
            background: linear-gradient(180deg, #eef6ff, #ffffff);
            border-color: #bfdbfe;
            box-shadow: inset 0 0 0 1px rgba(59, 130, 246, .04);
        }

        .smart-ai-panel h3 {
            color: #1d4ed8;
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
            align-items: center;
            border: 1px solid transparent;
            border-radius: .55rem;
            display: inline-flex;
            font-size: .78rem;
            font-weight: 750;
            line-height: 1;
            padding: .58rem .78rem;
            transition: .18s ease;
        }

        .smart-action:hover { filter: brightness(.98); transform: translateY(-1px); }
        .smart-action.primary { background: #2563eb; color: white; }
        .smart-action.success { background: #0f766e; color: white; }
        .smart-action.warning { background: #ffffff; border-color: #fed7aa; color: #b45309; }
        .smart-action.danger { background: #ffffff; border-color: #fecaca; color: #b91c1c; }
        .smart-action.muted { background: #ffffff; border-color: #e5e7eb; color: #475569; }

        .smart-empty {
            background: rgba(255, 255, 255, .86);
            border: 1px dashed rgba(15, 23, 42, .15);
            border-radius: 1.6rem;
            color: #475569;
            padding: 2rem;
            text-align: center;
        }
    </style>

    <section class="social-inbox-page" wire:poll.10s>
        <header class="social-inbox-toolbar">
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
                    <span class="smart-filter-icon">{{ $item['icon'] }}</span>
                    <span>{{ $item['label'] }}</span>
                    <strong class="smart-filter-count">{{ $item['count'] }}</strong>
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

                        <section class="smart-panel smart-ai-panel">
                            <h3>Sugerencia IA</h3>
                            <p>{{ $comment->suggested_reply ?: 'Sin respuesta sugerida. Revisar contexto antes de responder.' }}</p>
                            @if ($comment->ai_reason)
                                <p class="smart-muted" style="margin-top:.45rem">Motivo: {{ $comment->ai_reason }}</p>
                            @endif
                        </section>
                    </div>

                    <div class="smart-actions">
                        @if ($isCrisis)
                            <button class="smart-action danger" type="button" wire:click="escalate({{ $comment->id }})">Escalar</button>
                        @endif

                        @if ($isLead || blank($comment->tracking_token))
                            <button class="smart-action success" type="button" wire:click="routeToWhatsapp({{ $comment->id }})">WhatsApp</button>
                        @endif

                        @if ($patientUrl)
                            <a class="smart-action primary" href="{{ $patientUrl }}">Ver ficha</a>
                        @else
                            <a class="smart-action primary" href="{{ $detailUrl }}">Crear ficha</a>
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
