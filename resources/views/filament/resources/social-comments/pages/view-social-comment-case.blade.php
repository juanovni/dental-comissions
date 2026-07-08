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
    $conversionStatusLabel = $record->conversion_status?->label() ?? 'Sin conversion';
    $identity = $record->socialIdentity;
    $patient = $identity?->patient ?: $record->convertedPatient;
    $lastActivity = $patient
        ? \App\Models\ActivityRecord::query()
            ->with(['doctor', 'procedure'])
            ->where('patient_id', $patient->id)
            ->latest('activity_date')
            ->latest('id')
            ->first()
        : null;
    $activityCount = $patient
        ? \App\Models\ActivityRecord::query()->where('patient_id', $patient->id)->count()
        : 0;
    $patientRevenue = $patient
        ? \App\Models\ActivityRecord::query()->where('patient_id', $patient->id)->sum('internal_rate_snapshot')
        : 0;
    $socialHistoryCount = $identity
        ? \App\Models\SocialComment::query()->where('social_identity_id', $identity->id)->count()
        : 0;
    $previousSocialCount = max(0, $socialHistoryCount - 1);
    $patientUrl = $patient ? \App\Filament\Resources\Patients\PatientResource::getUrl('edit', ['record' => $patient]) : null;
    $publishedAt = $record->published_at?->translatedFormat('M. d, Y H:i') ?? 'Sin fecha';
    $processedAt = $record->processed_at?->translatedFormat('M. d, Y H:i') ?? 'Pendiente';
    $lastActivityDate = $lastActivity?->activity_date?->diffForHumans() ?? 'Sin citas registradas';
    $lastDoctor = $lastActivity?->doctor?->name ?? 'Sin doctor registrado';
    $lastProcedure = $lastActivity?->procedure?->name ?? 'Sin procedimiento registrado';
    $socialPost = $record->socialPost;
    $postCaption = $socialPost?->caption;
    $postMediaUrl = $socialPost?->media_url;
    $postPermalink = $socialPost?->permalink;
    $postAuthor = $record->socialAccount?->account_name ?: $platformLabel;
@endphp

<style>
    .social-case {
        --case-accent: #1d7afc;
        --case-card: #ffffff;
        --case-ink: #1f2937;
        --case-line: #e5e7eb;
        --case-muted: #6b7280;
        --case-soft: #f9fafb;
        background: var(--case-soft);
        border: 1px solid var(--case-line);
        border-radius: .875rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        color: var(--case-ink);
        overflow: hidden;
        padding: .875rem;
    }

    .fi-page-header .fi-ac .fi-btn,
    .fi-header .fi-ac .fi-btn,
    .fi-header-actions .fi-btn {
        border-radius: .5rem;
        font-size: .75rem;
        font-weight: 500;
        min-height: 2rem;
        padding: .375rem .625rem;
    }

    .fi-page-header .fi-ac .fi-btn .fi-btn-icon,
    .fi-header .fi-ac .fi-btn .fi-btn-icon,
    .fi-header-actions .fi-btn .fi-btn-icon {
        height: .95rem;
        width: .95rem;
    }

    .social-case-hero {
        align-items: stretch;
        display: grid;
        gap: .75rem;
        grid-template-columns: minmax(0, 1fr);
        margin-bottom: .75rem;
    }

    @media (min-width: 900px) {
        .social-case-hero {
            grid-template-columns: minmax(0, 1fr) minmax(14rem, .32fr);
        }
    }

    .social-case-intro,
    .social-case-card,
    .social-case-side {
        background: var(--case-card);
        border: 1px solid var(--case-line);
        border-radius: .75rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
    }

    .social-case-intro {
        display: grid;
        gap: .875rem;
        grid-template-columns: auto minmax(0, 1fr);
        padding: 1rem;
    }

    .social-case-icon {
        align-items: center;
        border-radius: .75rem;
        color: white;
        display: flex;
        font-size: 1rem;
        font-weight: 600;
        height: 2.75rem;
        justify-content: center;
        letter-spacing: -.02em;
        width: 2.75rem;
    }

    .social-case-icon.facebook {
        background: #1877f2;
    }

    .social-case-icon.instagram {
        background: #e1306c;
    }

    .social-case-kicker {
        color: var(--case-muted);
        font-size: .68rem;
        font-weight: 500;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .social-case-title {
        font-size: 1.08rem;
        font-weight: 600;
        letter-spacing: -.015em;
        line-height: 1.25;
        margin-top: .15rem;
    }

    .social-case-author {
        color: var(--case-muted);
        font-size: .82rem;
        font-weight: 400;
        margin-top: .25rem;
    }

    .social-case-badges {
        display: flex;
        flex-wrap: wrap;
        gap: .375rem;
        margin-top: .65rem;
    }

    .social-case-badge {
        align-items: center;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: .375rem;
        color: #334155;
        display: inline-flex;
        font-size: .7rem;
        font-weight: 500;
        gap: .35rem;
        line-height: 1.1;
        padding: .265rem .475rem;
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
        font-size: 1.3rem;
        font-weight: 600;
        letter-spacing: -.025em;
        line-height: 1.05;
    }

    .social-case-score span {
        color: var(--case-muted);
        display: block;
        font-size: .68rem;
        font-weight: 500;
        letter-spacing: .1em;
        margin-top: .3rem;
        text-transform: uppercase;
    }

    .social-case-meter {
        background: #edf2f7;
        border-radius: 999px;
        height: .375rem;
        margin-top: .875rem;
        overflow: hidden;
    }

    .social-case-meter span {
        background: var(--case-accent);
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
        gap: .75rem;
        grid-template-columns: minmax(0, 1fr);
    }

    @media (min-width: 1100px) {
        .social-case-grid {
            grid-template-columns: minmax(0, 1fr) minmax(17rem, .36fr);
        }
    }

    .social-case-main,
    .social-case-aside {
        display: grid;
        gap: .75rem;
    }

    .social-case-card {
        padding: 1rem;
    }

    .social-case-card h3 {
        color: var(--case-ink);
        font-size: .9rem;
        font-weight: 600;
        letter-spacing: -.01em;
        margin: 0 0 .875rem;
    }

    .social-case-comment {
        background: var(--case-soft);
        border-left: 3px solid var(--case-accent);
        border-radius: .5rem;
        color: #1f2937;
        font-size: .94rem;
        font-weight: 400;
        letter-spacing: 0;
        line-height: 1.62;
        padding: .85rem .95rem;
    }

    .social-case-comment.risk-high,
    .social-case-comment.risk-critical {
        border-left-color: #ef4444;
    }

    .social-case-comment.risk-medium {
        border-left-color: #f59e0b;
    }

    .social-case-note {
        background: var(--case-soft);
        border: 1px solid var(--case-line);
        border-radius: .625rem;
        color: #475569;
        font-size: .86rem;
        font-weight: 400;
        line-height: 1.55;
        padding: .8rem;
    }

    .social-case-facts {
        display: grid;
        gap: .55rem;
    }

    .social-case-fact {
        align-items: start;
        border-bottom: 1px solid #f1f5f9;
        display: grid;
        gap: .4rem;
        grid-template-columns: minmax(6.5rem, .35fr) minmax(0, 1fr);
        padding-bottom: .55rem;
    }

    .social-case-fact:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .social-case-fact span {
        color: var(--case-muted);
        font-size: .76rem;
        font-weight: 400;
        letter-spacing: 0;
    }

    .social-case-fact strong,
    .social-case-fact p {
        color: var(--case-ink);
        font-size: .86rem;
        font-weight: 500;
        margin: 0;
        overflow-wrap: anywhere;
    }

    .social-case-fact p {
        font-weight: 400;
    }

    .social-case-empty {
        color: var(--case-muted);
        font-size: .84rem;
        font-style: italic;
    }

    .social-case-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
    }

    .social-case-original {
        display: grid;
        gap: .75rem;
    }

    .social-case-post-media {
        align-items: center;
        background: linear-gradient(135deg, #f8fafc, #eef2f7);
        border: 1px solid var(--case-line);
        border-radius: .625rem;
        display: flex;
        justify-content: center;
        min-height: 11rem;
        overflow: hidden;
        position: relative;
    }

    .social-case-post-media img {
        display: block;
        height: 100%;
        inset: 0;
        object-fit: cover;
        position: absolute;
        width: 100%;
    }

    .social-case-post-placeholder {
        align-items: center;
        color: var(--case-muted);
        display: grid;
        font-size: .8rem;
        gap: .35rem;
        justify-items: center;
        padding: 1rem;
        text-align: center;
    }

    .social-case-post-placeholder strong {
        align-items: center;
        background: #ffffff;
        border: 1px solid var(--case-line);
        border-radius: .75rem;
        color: var(--case-accent);
        display: inline-flex;
        font-size: .82rem;
        font-weight: 600;
        height: 2.5rem;
        justify-content: center;
        width: 2.5rem;
    }

    .social-case-post-meta {
        color: var(--case-muted);
        font-size: .76rem;
        font-weight: 400;
        line-height: 1.45;
    }

    .social-case-post-caption {
        background: var(--case-soft);
        border: 1px solid var(--case-line);
        border-radius: .625rem;
        color: var(--case-ink);
        font-size: .84rem;
        font-weight: 400;
        line-height: 1.55;
        padding: .75rem;
    }

    .social-case-post-comment {
        border-left: 3px solid var(--case-accent);
        color: var(--case-ink);
        font-size: .84rem;
        line-height: 1.55;
        padding: .1rem 0 .1rem .7rem;
    }

    .social-case-post-comment span {
        color: var(--case-muted);
        display: block;
        font-size: .72rem;
        font-weight: 500;
        margin-bottom: .25rem;
    }

    .social-case-action {
        align-items: center;
        border-radius: .5rem;
        display: inline-flex;
        font-size: .75rem;
        font-weight: 500;
        justify-content: center;
        letter-spacing: 0;
        padding: .425rem .625rem;
        text-decoration: none;
    }

    .social-case-action.primary {
        background: var(--case-accent);
        color: white;
    }

    .social-case-action.soft {
        background: var(--case-soft);
        border: 1px solid var(--case-line);
        color: #334155;
    }

    .dark .social-case {
        --case-ink: #e5e7eb;
        --case-muted: #94a3b8;
        --case-line: rgba(148, 163, 184, .16);
        --case-card: rgba(15, 23, 42, .74);
        --case-soft: #020617;
        background: #0f172a;
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

    .dark .social-case-post-media,
    .dark .social-case-post-caption {
        background: rgba(2, 6, 23, .55);
        border-color: rgba(148, 163, 184, .14);
    }

    .dark .social-case-post-placeholder strong {
        background: rgba(15, 23, 42, .86);
        border-color: rgba(148, 163, 184, .18);
        color: #93c5fd;
    }

    .dark .social-case-badge,
    .dark .social-case-action.soft {
        background: rgba(15, 23, 42, .86);
        border-color: rgba(148, 163, 184, .18);
        color: #cbd5e1;
    }

    .dark .social-case-badge.risk-high,
    .dark .social-case-badge.risk-critical,
    .dark .social-case-badge.status-escalated,
    .dark .social-case-badge.status-marked_as_spam,
    .dark .social-case-badge.sentiment-negative {
        background: rgba(127, 29, 29, .28);
        border-color: rgba(248, 113, 113, .28);
        color: #fca5a5;
    }

    .dark .social-case-badge.risk-medium,
    .dark .social-case-badge.priority-high,
    .dark .social-case-badge.priority-critical,
    .dark .social-case-badge.status-review_required {
        background: rgba(120, 53, 15, .28);
        border-color: rgba(251, 191, 36, .24);
        color: #fcd34d;
    }

    .dark .social-case-badge.sentiment-positive,
    .dark .social-case-badge.status-classified,
    .dark .social-case-badge.status-responded {
        background: rgba(6, 78, 59, .28);
        border-color: rgba(52, 211, 153, .24);
        color: #86efac;
    }

    .dark .social-case-fact {
        border-bottom-color: rgba(148, 163, 184, .12);
    }

    .dark .social-case-action.soft {
        background: rgba(15, 23, 42, .9);
        border-color: rgba(148, 163, 184, .16);
        color: #cbd5e1;
    }
</style>

<div class="social-case">
    <div class="social-case-hero">
        <section class="social-case-intro">
            <div class="social-case-icon {{ $platform }}" aria-hidden="true">
                @if ($platform === 'facebook')
                    f
                @elseif ($platform === 'instagram')
                    ig
                @else
                    rs
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
            <section class="social-case-card" style="padding:0;overflow:visible;background:transparent;border:0;box-shadow:none">
                <h3>Pulso del Cliente - Timeline</h3>
                <livewire:customer-pulse-timeline :commentId="$record->id" />
            </section>

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

            <section class="social-case-card">
                <h3>Contexto Clinico 360</h3>
                <div class="social-case-facts">
                    <div class="social-case-fact">
                        <span>Paciente</span>
                        <strong>{{ $patient?->full_name ?: 'Lead sin ficha clinica' }}</strong>
                    </div>
                    <div class="social-case-fact">
                        <span>Ultima cita</span>
                        <p>{{ $lastActivityDate }} @if ($lastActivity) / {{ $lastProcedure }} @endif</p>
                    </div>
                    <div class="social-case-fact">
                        <span>Doctor</span>
                        <strong>{{ $lastDoctor }}</strong>
                    </div>
                    <div class="social-case-fact">
                        <span>Saldo</span>
                        <strong>No disponible en este modulo</strong>
                    </div>
                    <div class="social-case-fact">
                        <span>Valor historico</span>
                        <strong>${{ number_format((float) $patientRevenue, 2) }} / {{ $activityCount }} actividades</strong>
                    </div>
                </div>
            </section>

            <section class="social-case-card">
                <h3>Historial Social</h3>
                <div class="social-case-facts">
                    <div class="social-case-fact">
                        <span>Interacciones</span>
                        <strong>{{ $socialHistoryCount }} comentario(s) vinculados</strong>
                    </div>
                    <div class="social-case-fact">
                        <span>Previos</span>
                        <p>{{ $previousSocialCount }} comentario(s) antes de este caso.</p>
                    </div>
                    <div class="social-case-fact">
                        <span>Origen</span>
                        <p>{{ $record->socialPost?->campaign_name ?: 'Sin campana asignada' }}</p>
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
                <h3>CRM Social</h3>
                <div class="social-case-facts">
                    <div class="social-case-fact">
                        <span>Conversion</span>
                        <strong>{{ $conversionStatusLabel }}</strong>
                    </div>
                    <div class="social-case-fact">
                        <span>Token</span>
                        <strong>{{ $record->tracking_token ?: 'Sin token' }}</strong>
                    </div>
                    <div class="social-case-fact">
                        <span>Identidad</span>
                        <strong>{{ $identity?->display_name ?: $identity?->username ?: 'Sin identidad' }}</strong>
                    </div>
                    <div class="social-case-fact">
                        <span>Paciente</span>
                        <strong>{{ $patient?->full_name ?: 'Sin ficha vinculada' }}</strong>
                    </div>
                    <div class="social-case-fact">
                        <span>Procedimiento</span>
                        <strong>{{ $record->suggestedProcedure?->name ?: 'Sin sugerencia' }}</strong>
                    </div>
                </div>
            </section>

            <section class="social-case-card">
                <h3>Publicacion original</h3>
                <div class="social-case-original">
                    <div class="social-case-post-media">
                        @if ($postMediaUrl)
                            <img src="{{ $postMediaUrl }}" alt="Imagen de la publicacion relacionada">
                        @else
                            <div class="social-case-post-placeholder">
                                <strong>{{ $platform === 'instagram' ? 'ig' : ($platform === 'facebook' ? 'f' : 'rs') }}</strong>
                                <span>Sin imagen de publicacion</span>
                            </div>
                        @endif
                    </div>

                    <div class="social-case-post-meta">
                        {{ $postAuthor }} · {{ $platformLabel }} · {{ $socialPost?->published_at?->diffForHumans() ?? 'Fecha no disponible' }}
                    </div>

                    @if ($postCaption)
                        <div class="social-case-post-caption">{{ $postCaption }}</div>
                    @else
                        <p class="social-case-empty">No hay texto de publicacion asociado.</p>
                    @endif

                    <div class="social-case-post-comment">
                        <span>Comentario recibido</span>
                        {{ $record->comment_text ?: 'Sin texto registrado.' }}
                    </div>

                    <div class="social-case-actions">
                        @if ($postPermalink)
                            <a class="social-case-action primary" href="{{ $postPermalink }}" target="_blank" rel="noopener noreferrer">Abrir publicacion</a>
                        @else
                            <span class="social-case-action soft">Link no disponible</span>
                        @endif
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
