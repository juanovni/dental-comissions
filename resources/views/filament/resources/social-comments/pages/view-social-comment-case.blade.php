@php
    /** @var \App\Models\SocialComment $record */
    $record = $getRecord();

    $platform = $record->platform?->value ?? 'facebook';
    $platformLabel = $record->platform?->label() ?? 'Red social';
    $risk = $record->reputation_risk?->value ?? 'low';
    $riskLabel = $record->reputation_risk?->label() ?? 'Bajo';
    $priority = $record->priority?->value ?? 'low';
    $priorityLabel = $record->priority?->label() ?? 'Sin prioridad';
    $status = $record->status?->value ?? 'new';
    $statusLabel = $record->status?->label() ?? 'Nuevo';
    $sentiment = $record->sentiment?->value ?? 'neutral';
    $sentimentLabel = $record->sentiment?->label() ?? 'Sin analizar';
    $classificationLabel = $record->classification?->label() ?? 'Sin clasificar';
    $suggestedActionLabel = $record->suggested_action?->label() ?? 'Sin accion';
    $responseChannelLabel = $record->response_channel?->label() ?? 'Sin canal';
    $publishedAt = $record->published_at?->translatedFormat('M. d, Y H:i') ?? 'Sin fecha';
    $processedAt = $record->processed_at?->translatedFormat('M. d, Y H:i') ?? 'Pendiente';
    $postCaption = $record->socialPost?->caption;
@endphp

<style>
    .social-case {
        --case-ink: #0f172a;
        --case-muted: #64748b;
        --case-line: rgba(15, 23, 42, .08);
        --case-card: rgba(255, 255, 255, .9);
        --case-soft: #f8fafc;
        background:
            radial-gradient(circle at 0% 0%, rgba(20, 184, 166, .14), transparent 22rem),
            radial-gradient(circle at 95% 12%, rgba(251, 191, 36, .14), transparent 22rem),
            linear-gradient(180deg, rgba(248, 250, 252, .96), rgba(255, 255, 255, .98));
        border: 1px solid var(--case-line);
        border-radius: 2rem;
        box-shadow: 0 28px 90px -72px rgba(15, 23, 42, .95);
        color: var(--case-ink);
        overflow: hidden;
        padding: clamp(1rem, 2vw, 1.5rem);
    }

    .social-case-hero {
        align-items: stretch;
        display: grid;
        gap: 1rem;
        grid-template-columns: minmax(0, 1fr);
        margin-bottom: 1rem;
    }

    @media (min-width: 900px) {
        .social-case-hero {
            grid-template-columns: minmax(0, 1fr) minmax(16rem, .38fr);
        }
    }

    .social-case-intro,
    .social-case-card,
    .social-case-side {
        background: var(--case-card);
        border: 1px solid var(--case-line);
        border-radius: 1.5rem;
        box-shadow: 0 20px 60px -52px rgba(15, 23, 42, .8);
    }

    .social-case-intro {
        display: grid;
        gap: 1rem;
        grid-template-columns: auto minmax(0, 1fr);
        padding: clamp(1rem, 2vw, 1.35rem);
        position: relative;
    }

    .social-case-intro::after {
        background: linear-gradient(180deg, #14b8a6, transparent);
        border-radius: 999px;
        content: '';
        inset: 1rem 1rem auto auto;
        height: 44%;
        opacity: .28;
        position: absolute;
        width: 3px;
    }

    .social-case-icon {
        align-items: center;
        border-radius: 1.1rem;
        color: white;
        display: flex;
        font-size: 1.45rem;
        font-weight: 900;
        height: 3.5rem;
        justify-content: center;
        letter-spacing: -.05em;
        position: relative;
        width: 3.5rem;
    }

    .social-case-icon.facebook {
        background: linear-gradient(145deg, #1877f2, #0755b8);
    }

    .social-case-icon.instagram {
        background: radial-gradient(circle at 30% 100%, #feda75, #fa7e1e 28%, #d62976 56%, #962fbf 78%, #4f5bd5);
    }

    .social-case-icon.instagram::before {
        border: 2px solid rgba(255, 255, 255, .9);
        border-radius: .5rem;
        content: '';
        height: 1.45rem;
        position: absolute;
        width: 1.45rem;
    }

    .social-case-icon.instagram::after {
        background: white;
        border-radius: 999px;
        box-shadow: .46rem -.46rem 0 -.12rem white;
        content: '';
        height: .42rem;
        position: absolute;
        width: .42rem;
    }

    .social-case-kicker {
        color: var(--case-muted);
        font-size: .72rem;
        font-weight: 850;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .social-case-title {
        font-size: clamp(1.35rem, 2.4vw, 2.15rem);
        font-weight: 900;
        letter-spacing: -.055em;
        line-height: 1.04;
        margin-top: .25rem;
    }

    .social-case-author {
        color: var(--case-muted);
        font-size: .95rem;
        font-weight: 650;
        margin-top: .45rem;
    }

    .social-case-badges {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        margin-top: .9rem;
    }

    .social-case-badge {
        align-items: center;
        background: #f1f5f9;
        border: 1px solid rgba(15, 23, 42, .07);
        border-radius: 999px;
        color: #334155;
        display: inline-flex;
        font-size: .72rem;
        font-weight: 850;
        gap: .35rem;
        letter-spacing: .02em;
        padding: .42rem .64rem;
    }

    .social-case-badge.risk-high,
    .social-case-badge.risk-critical,
    .social-case-badge.status-escalated,
    .social-case-badge.status-marked_as_spam,
    .social-case-badge.sentiment-negative {
        background: #fef2f2;
        border-color: #fecaca;
        color: #b91c1c;
    }

    .social-case-badge.risk-medium,
    .social-case-badge.priority-high,
    .social-case-badge.priority-critical,
    .social-case-badge.status-review_required {
        background: #fffbeb;
        border-color: #fed7aa;
        color: #b45309;
    }

    .social-case-badge.sentiment-positive,
    .social-case-badge.status-classified,
    .social-case-badge.status-responded {
        background: #ecfdf5;
        border-color: #bbf7d0;
        color: #047857;
    }

    .social-case-side {
        padding: 1rem;
    }

    .social-case-score {
        align-items: end;
        display: flex;
        gap: .65rem;
        justify-content: space-between;
    }

    .social-case-score strong {
        display: block;
        font-size: 2.25rem;
        font-weight: 950;
        letter-spacing: -.08em;
        line-height: .9;
    }

    .social-case-score span {
        color: var(--case-muted);
        display: block;
        font-size: .72rem;
        font-weight: 850;
        letter-spacing: .1em;
        margin-top: .35rem;
        text-transform: uppercase;
    }

    .social-case-meter {
        background: #e2e8f0;
        border-radius: 999px;
        height: .5rem;
        margin-top: 1rem;
        overflow: hidden;
    }

    .social-case-meter span {
        background: #14b8a6;
        border-radius: inherit;
        display: block;
        height: 100%;
        width: 24%;
    }

    .social-case-meter.risk-medium span { background: #f59e0b; width: 52%; }
    .social-case-meter.risk-high span { background: #ef4444; width: 78%; }
    .social-case-meter.risk-critical span { background: #991b1b; width: 100%; }

    .social-case-grid {
        display: grid;
        gap: 1rem;
        grid-template-columns: minmax(0, 1fr);
    }

    @media (min-width: 1100px) {
        .social-case-grid {
            grid-template-columns: minmax(0, 1fr) minmax(18rem, .42fr);
        }
    }

    .social-case-main,
    .social-case-aside {
        display: grid;
        gap: 1rem;
    }

    .social-case-card {
        padding: clamp(1rem, 2vw, 1.25rem);
    }

    .social-case-card h3 {
        color: var(--case-ink);
        font-size: .82rem;
        font-weight: 900;
        letter-spacing: .1em;
        margin: 0 0 .85rem;
        text-transform: uppercase;
    }

    .social-case-comment {
        border-left: 4px solid #14b8a6;
        color: #111827;
        font-size: clamp(1.05rem, 1.7vw, 1.35rem);
        font-weight: 750;
        letter-spacing: -.025em;
        line-height: 1.55;
        padding-left: 1rem;
    }

    .social-case-comment.risk-high,
    .social-case-comment.risk-critical {
        border-left-color: #ef4444;
    }

    .social-case-comment.risk-medium {
        border-left-color: #f59e0b;
    }

    .social-case-note {
        background: #f8fafc;
        border: 1px solid rgba(15, 23, 42, .07);
        border-radius: 1rem;
        color: #475569;
        line-height: 1.55;
        padding: .9rem;
    }

    .social-case-facts {
        display: grid;
        gap: .65rem;
    }

    .social-case-fact {
        align-items: start;
        border-bottom: 1px solid rgba(15, 23, 42, .06);
        display: grid;
        gap: .4rem;
        grid-template-columns: minmax(7rem, .42fr) minmax(0, 1fr);
        padding-bottom: .65rem;
    }

    .social-case-fact:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .social-case-fact span {
        color: var(--case-muted);
        font-size: .72rem;
        font-weight: 850;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .social-case-fact strong,
    .social-case-fact p {
        color: var(--case-ink);
        font-size: .9rem;
        font-weight: 750;
        margin: 0;
        overflow-wrap: anywhere;
    }

    .social-case-empty {
        color: var(--case-muted);
        font-style: italic;
    }

    .dark .social-case {
        --case-ink: #e5e7eb;
        --case-muted: #94a3b8;
        --case-line: rgba(148, 163, 184, .14);
        --case-card: rgba(15, 23, 42, .82);
        --case-soft: #020617;
        background:
            radial-gradient(circle at 0% 0%, rgba(20, 184, 166, .14), transparent 24rem),
            radial-gradient(circle at 95% 12%, rgba(245, 158, 11, .1), transparent 22rem),
            linear-gradient(180deg, rgba(2, 6, 23, .98), rgba(15, 23, 42, .98));
    }

    .dark .social-case-comment,
    .dark .social-case-fact strong,
    .dark .social-case-fact p,
    .dark .social-case-card h3 {
        color: var(--case-ink);
    }

    .dark .social-case-note {
        background: rgba(2, 6, 23, .55);
        border-color: rgba(148, 163, 184, .14);
        color: #cbd5e1;
    }
</style>

<div class="social-case">
    <div class="social-case-hero">
        <section class="social-case-intro">
            <div class="social-case-icon {{ $platform }}" aria-hidden="true">
                @if ($platform === 'facebook')
                    f
                @endif
            </div>

            <div>
                <div class="social-case-kicker">Caso de reputacion</div>
                <div class="social-case-title">Comentario de {{ $platformLabel }}</div>
                <div class="social-case-author">
                    {{ $record->author_name ?: 'Autor desconocido' }}
                    @if ($record->author_username)
                        / {{ $record->author_username }}
                    @endif
                </div>

                <div class="social-case-badges">
                    <span class="social-case-badge status-{{ $status }}">{{ $statusLabel }}</span>
                    <span class="social-case-badge risk-{{ $risk }}">Riesgo {{ $riskLabel }}</span>
                    <span class="social-case-badge priority-{{ $priority }}">Prioridad {{ $priorityLabel }}</span>
                    <span class="social-case-badge sentiment-{{ $sentiment }}">{{ $sentimentLabel }}</span>
                </div>
            </div>
        </section>

        <aside class="social-case-side">
            <div class="social-case-score">
                <div>
                    <strong>{{ $riskLabel }}</strong>
                    <span>Nivel de riesgo</span>
                </div>
                <span class="social-case-badge risk-{{ $risk }}">{{ $classificationLabel }}</span>
            </div>
            <div class="social-case-meter risk-{{ $risk }}"><span></span></div>
        </aside>
    </div>

    <div class="social-case-grid">
        <main class="social-case-main">
            <section class="social-case-card">
                <h3>Comentario recibido</h3>
                <div class="social-case-comment risk-{{ $risk }}">
                    {{ $record->comment_text ?: 'Sin texto registrado.' }}
                </div>
            </section>

            <section class="social-case-card">
                <h3>Publicacion relacionada</h3>
                @if ($postCaption)
                    <div class="social-case-note">{{ $postCaption }}</div>
                @else
                    <p class="social-case-empty">No hay texto de publicacion asociado.</p>
                @endif
            </section>

            <section class="social-case-card">
                <h3>Analisis de IA</h3>
                <div class="social-case-facts">
                    <div class="social-case-fact">
                        <span>Motivo</span>
                        <p>{{ $record->ai_reason ?: 'Sin motivo registrado.' }}</p>
                    </div>
                    <div class="social-case-fact">
                        <span>Respuesta sugerida</span>
                        <p>{{ $record->suggested_reply ?: 'Sin respuesta sugerida. Revisar contexto antes de responder.' }}</p>
                    </div>
                    <div class="social-case-fact">
                        <span>Revision humana</span>
                        <strong>{{ $record->requires_human_review ? 'Si requerida' : 'No requerida' }}</strong>
                    </div>
                </div>
            </section>
        </main>

        <aside class="social-case-aside">
            <section class="social-case-card">
                <h3>Evaluacion</h3>
                <div class="social-case-facts">
                    <div class="social-case-fact">
                        <span>Clasificacion</span>
                        <strong>{{ $classificationLabel }}</strong>
                    </div>
                    <div class="social-case-fact">
                        <span>Sentimiento</span>
                        <strong>{{ $sentimentLabel }}</strong>
                    </div>
                    <div class="social-case-fact">
                        <span>Accion</span>
                        <strong>{{ $suggestedActionLabel }}</strong>
                    </div>
                    <div class="social-case-fact">
                        <span>Canal</span>
                        <strong>{{ $responseChannelLabel }}</strong>
                    </div>
                </div>
            </section>

            <section class="social-case-card">
                <h3>Resumen</h3>
                <div class="social-case-facts">
                    <div class="social-case-fact">
                        <span>Cuenta</span>
                        <strong>{{ $record->socialAccount?->account_name ?: 'Sin cuenta' }}</strong>
                    </div>
                    <div class="social-case-fact">
                        <span>Red</span>
                        <strong>{{ $platformLabel }}</strong>
                    </div>
                    <div class="social-case-fact">
                        <span>Publicado</span>
                        <strong>{{ $publishedAt }}</strong>
                    </div>
                    <div class="social-case-fact">
                        <span>Procesado</span>
                        <strong>{{ $processedAt }}</strong>
                    </div>
                    <div class="social-case-fact">
                        <span>ID externo</span>
                        <strong>{{ $record->external_comment_id ?: 'No disponible' }}</strong>
                    </div>
                </div>
            </section>
        </aside>
    </div>
</div>
