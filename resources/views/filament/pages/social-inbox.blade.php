<x-filament-panels::page>
    @php
        $stats = $this->stats();
        $comments = $this->comments();
        $filters = [
            'review' => ['label' => 'Revision', 'count' => $stats['review']],
            'high_risk' => ['label' => 'Riesgo alto', 'count' => $stats['high_risk']],
            'leads' => ['label' => 'Leads', 'count' => $stats['leads']],
            'complaints' => ['label' => 'Quejas', 'count' => $stats['complaints']],
            'spam' => ['label' => 'Spam', 'count' => $stats['spam']],
            'facebook' => ['label' => 'Facebook', 'count' => null],
            'instagram' => ['label' => 'Instagram', 'count' => null],
            'all' => ['label' => 'Todos', 'count' => null],
        ];
    @endphp

    <style>
        .reputation-shell {
            background:
                radial-gradient(circle at 8% 0%, rgba(20, 184, 166, .12), transparent 30rem),
                linear-gradient(180deg, rgba(248, 250, 252, .94), rgba(255, 255, 255, .98));
            border: 1px solid rgba(15, 23, 42, .06);
            border-radius: 2rem;
            padding: clamp(1rem, 2vw, 1.5rem);
        }

        .reputation-hero {
            margin-bottom: 1.25rem;
        }

        .reputation-search {
            background: rgba(255, 255, 255, .78);
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 999px;
            box-shadow: 0 22px 70px -54px rgba(15, 23, 42, .8);
            color: rgb(15, 23, 42);
            outline: none;
            padding: .95rem 1.15rem;
            width: 100%;
        }

        .reputation-stats {
            display: grid;
            gap: .75rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-block: 1rem 1.25rem;
        }

        @media (min-width: 768px) {
            .reputation-stats {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }

        .reputation-stat {
            background: rgba(255, 255, 255, .86);
            border: 1px solid rgba(15, 23, 42, .07);
            border-radius: 1.35rem;
            padding: 1rem;
        }

        .reputation-stat strong {
            color: rgb(15, 23, 42);
            display: block;
            font-size: 1.8rem;
            letter-spacing: -.06em;
            line-height: 1;
        }

        .reputation-stat span {
            color: rgb(100, 116, 139);
            display: block;
            font-size: .72rem;
            font-weight: 750;
            letter-spacing: .08em;
            margin-top: .45rem;
            text-transform: uppercase;
        }

        .reputation-filters {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            margin-bottom: 1rem;
        }

        .reputation-filter {
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 999px;
            color: rgb(51, 65, 85);
            font-size: .82rem;
            font-weight: 750;
            padding: .62rem .85rem;
            transition: all .18s ease;
        }

        .reputation-filter:hover,
        .reputation-filter.is-active {
            background: rgb(15, 23, 42);
            border-color: rgb(15, 23, 42);
            color: white;
            transform: translateY(-1px);
        }

        .comment-grid {
            display: grid;
            gap: .85rem;
        }

        .comment-card {
            background: rgba(255, 255, 255, .94);
            border: 1px solid rgba(15, 23, 42, .07);
            border-radius: 1.6rem;
            box-shadow: 0 24px 90px -70px rgba(15, 23, 42, .95);
            display: grid;
            gap: 1rem;
            grid-template-columns: minmax(0, 1fr);
            overflow: hidden;
            padding: .9rem;
            position: relative;
        }

        @media (min-width: 1024px) {
            .comment-card {
                grid-template-columns: minmax(0, 1fr) minmax(14rem, .42fr);
                padding: 1.25rem;
            }
        }

        .comment-card::before {
            background: rgb(20, 184, 166);
            content: '';
            inset: 0 auto 0 0;
            position: absolute;
            width: 4px;
        }

        .comment-card.risk-high::before,
        .comment-card.risk-critical::before {
            background: rgb(239, 68, 68);
        }

        .comment-card.risk-medium::before {
            background: rgb(245, 158, 11);
        }

        .comment-meta {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
        }

        .comment-chip {
            background: rgb(248, 250, 252);
            border: 1px solid rgba(15, 23, 42, .07);
            border-radius: 999px;
            color: rgb(51, 65, 85);
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .06em;
            padding: .36rem .55rem;
            text-transform: uppercase;
        }

        .comment-chip.danger {
            background: rgb(254, 242, 242);
            border-color: rgb(254, 202, 202);
            color: rgb(185, 28, 28);
        }

        .comment-chip.success {
            background: rgb(240, 253, 250);
            border-color: rgb(153, 246, 228);
            color: rgb(15, 118, 110);
        }

        .comment-author {
            color: rgb(15, 23, 42);
            font-size: .85rem;
            font-weight: 800;
            margin-top: .65rem;
        }

        .comment-text {
            color: rgb(15, 23, 42);
            font-size: clamp(.88rem, 1.4vw, 1.02rem);
            font-weight: 600;
            letter-spacing: -.02em;
            line-height: 1.4;
            margin-top: .4rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .comment-context {
            color: rgb(100, 116, 139);
            font-size: .78rem;
            line-height: 1.45;
            margin-top: .6rem;
        }

        .reply-draft {
            background: linear-gradient(180deg, rgb(248, 250, 252), rgb(255, 255, 255));
            border: 1px solid rgba(15, 23, 42, .07);
            border-radius: 1.1rem;
            color: rgb(51, 65, 85);
            font-size: .88rem;
            line-height: 1.55;
            padding: .9rem;
        }

        .reply-draft span {
            color: rgb(13, 148, 136);
            display: block;
            font-size: .68rem;
            font-weight: 850;
            letter-spacing: .12em;
            margin-bottom: .45rem;
            text-transform: uppercase;
        }

        .comment-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            margin-top: .8rem;
        }

        .comment-action {
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 800;
            padding: .55rem .75rem;
            transition: all .18s ease;
        }

        .comment-action:hover {
            transform: translateY(-1px);
        }

        .comment-action.primary {
            background: rgb(15, 23, 42);
            color: white;
        }

        .comment-action.warning {
            background: rgb(255, 251, 235);
            color: rgb(180, 83, 9);
        }

        .comment-action.muted {
            background: rgb(241, 245, 249);
            color: rgb(71, 85, 105);
        }

        .comment-action.danger {
            background: rgb(254, 242, 242);
            color: rgb(185, 28, 28);
        }

        .empty-state {
            background: rgba(255, 255, 255, .86);
            border: 1px dashed rgba(15, 23, 42, .15);
            border-radius: 1.6rem;
            color: rgb(71, 85, 105);
            padding: 2rem;
            text-align: center;
        }
    </style>

    <section class="reputation-shell">
        <div class="reputation-hero">
            <input
                class="reputation-search"
                type="search"
                wire:model.live.debounce.350ms="search"
                placeholder="Buscar por comentario, autor o usuario"
            />
        </div>

        <div class="reputation-stats">
            <button class="reputation-stat" type="button" wire:click="setFilter('review')">
                <strong>{{ $stats['review'] }}</strong>
                <span>Revision</span>
            </button>
            <button class="reputation-stat" type="button" wire:click="setFilter('high_risk')">
                <strong>{{ $stats['high_risk'] }}</strong>
                <span>Riesgo alto</span>
            </button>
            <button class="reputation-stat" type="button" wire:click="setFilter('leads')">
                <strong>{{ $stats['leads'] }}</strong>
                <span>Leads</span>
            </button>
            <button class="reputation-stat" type="button" wire:click="setFilter('complaints')">
                <strong>{{ $stats['complaints'] }}</strong>
                <span>Quejas</span>
            </button>
            <button class="reputation-stat" type="button" wire:click="setFilter('spam')">
                <strong>{{ $stats['spam'] }}</strong>
                <span>Spam</span>
            </button>
        </div>

        <div class="reputation-filters">
            @foreach ($filters as $key => $item)
                <button
                    type="button"
                    wire:click="setFilter('{{ $key }}')"
                    @class(['reputation-filter', 'is-active' => $filter === $key])
                >
                    {{ $item['label'] }}
                    @if (! is_null($item['count']))
                        · {{ $item['count'] }}
                    @endif
                </button>
            @endforeach
        </div>

        <div class="comment-grid">
            @forelse ($comments as $comment)
                @php
                    $risk = $comment->reputation_risk?->value ?? 'low';
                    $isLead = in_array($comment->classification, [
                        \App\Enums\SocialCommentClassification::SalesLead,
                        \App\Enums\SocialCommentClassification::CommercialQuestion,
                    ], true);
                @endphp

                <article class="comment-card risk-{{ $risk }}">
                    <div>
                        <div class="comment-meta">
                            <span class="comment-chip">{{ $comment->platform->label() }}</span>
                            <span @class(['comment-chip', 'danger' => in_array($risk, ['high', 'critical'], true), 'success' => $isLead])>
                                {{ $comment->classification?->label() ?? 'Sin clasificar' }}
                            </span>
                            <span @class(['comment-chip', 'danger' => in_array($risk, ['high', 'critical'], true)])>
                                Riesgo {{ $comment->reputation_risk?->label() ?? 'bajo' }}
                            </span>
                            @if ($comment->requires_human_review)
                                <span class="comment-chip danger">Revision humana</span>
                            @endif
                        </div>

                        <div class="comment-author">
                            {{ $comment->author_name ?: 'Autor desconocido' }}
                            @if ($comment->author_username)
                                <span class="text-sm font-medium text-slate-400">/{{ $comment->author_username }}</span>
                            @endif
                        </div>

                        <p class="comment-text">{{ $comment->comment_text }}</p>

                        @if ($comment->socialPost?->caption)
                            <p class="comment-context">Publicacion: {{ \Illuminate\Support\Str::limit($comment->socialPost->caption, 170) }}</p>
                        @endif
                    </div>

                    <aside>
                        <div class="reply-draft">
                            <span>Respuesta sugerida</span>
                            {{ $comment->suggested_reply ?: 'Sin respuesta sugerida. Revisar contexto antes de responder.' }}
                        </div>

                        @if ($comment->ai_reason)
                            <p class="comment-context">Motivo: {{ $comment->ai_reason }}</p>
                        @endif

                        <div class="comment-actions">
                            <button class="comment-action primary" type="button" wire:click="markReviewed({{ $comment->id }})">
                                Revisado
                            </button>
                            <button class="comment-action warning" type="button" wire:click="escalate({{ $comment->id }})">
                                Escalar
                            </button>
                            <button class="comment-action muted" type="button" wire:click="ignore({{ $comment->id }})">
                                Ignorar
                            </button>
                            <button class="comment-action danger" type="button" wire:click="markSpam({{ $comment->id }})">
                                Spam
                            </button>
                            <a class="comment-action muted" href="{{ route('filament.admin.resources.social-comments.view', ['record' => $comment]) }}">
                                Detalle
                            </a>
                        </div>
                    </aside>
                </article>
            @empty
                <div class="empty-state">
                    No hay comentarios para este filtro. Cambia el segmento o sincroniza nuevos comentarios sociales.
                </div>
            @endforelse
        </div>

        <div class="mt-5">
            {{ $comments->links() }}
        </div>
    </section>
</x-filament-panels::page>
