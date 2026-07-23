@php
    /** @var \App\Models\SocialComment $record */
    $record = $getRecord();
    $case = app(\App\Services\SocialCommentCasePresenter::class)->build($record);

    $platform = $record->platform?->value ?? 'facebook';
    $platformLabel = $record->platform?->label() ?? 'Red social';
    $risk = $record->reputation_risk?->value ?? 'low';
    $riskLabel = $record->reputation_risk?->label() ?? 'Bajo';
    $status = $record->status?->value ?? 'new';
    $statusLabel = $record->status?->label() ?? 'Nuevo';
    $sentiment = $record->sentiment?->value ?? 'neutral';
    $sentimentLabel = $record->sentiment?->label() ?? 'Sin analizar';
    $classificationLabel = $record->classification?->label() ?? 'Sin clasificar';
    $suggestedActionLabel = $record->suggested_action?->label() ?? 'Sin accion';
    $conversionStatusLabel = $record->conversion_status?->label() ?? 'Sin conversion';
    $identity = $case['identity'];
    $patient = $case['patient'];
    $patientUrl = $case['patient_url'];
    $post = $case['post'];
    $authorHandle = $record->author_username ?: $identity?->username ?: $identity?->display_name ?: $record->author_name ?: 'lead-social';
    $authorLabel = str_starts_with($authorHandle, '@') ? $authorHandle : '@'.$authorHandle;
    $avatarText = str($identity?->display_name ?: $record->author_name ?: $authorHandle)->replace('@', '')->substr(0, 2)->upper();
    $commentDate = $record->published_at ?: $record->created_at;
    $commentDateLabel = $commentDate?->translatedFormat('M. d, Y · H:i') ?? 'Sin fecha';
    $processedAt = $record->processed_at?->translatedFormat('M. d, Y · H:i') ?? 'Pendiente';
    $score = (int) ($record->recent_engagement_score ?: $record->interest_score ?: 0);
    $scoreWidth = min($score, 100);
    $tabs = [
        'resumen' => ['label' => 'Resumen', 'icon' => 'sparkles'],
        'conversacion' => ['label' => 'Conversación', 'icon' => 'chat'],
        'actividad' => ['label' => 'Actividad', 'icon' => 'clock'],
        'contexto-clinico' => ['label' => 'Contexto clínico', 'icon' => 'clinical'],
    ];
    $activeTab = request()->query('tab', 'resumen');
    $activeTab = array_key_exists($activeTab, $tabs) ? $activeTab : 'resumen';
@endphp

<style>
    .social-comment-case-page .fi-header {
        display: none !important;
    }

    .social-case {
        --case-accent: #009f8b;
        --case-card: #ffffff;
        --case-ink: #0f172a;
        --case-line: #e5e7eb;
        --case-muted: #64748b;
        --case-soft: #f8fafc;
        color: var(--case-ink);
    }

    .social-case-shell {
        display: grid;
        gap: 1rem;
    }

    .social-case-header {
        align-items: center;
        display: flex;
        gap: .85rem;
        justify-content: space-between;
        min-width: 0;
    }

    .social-case-identity {
        align-items: center;
        display: flex;
        gap: .75rem;
        min-width: 0;
    }

    .social-case-avatar-wrap {
        position: relative;
    }

    .social-case-avatar {
        align-items: center;
        background: #eef8f8;
        border: 1px solid #d9eeee;
        border-radius: 999px;
        color: #0f172a;
        display: inline-flex;
        font-size: .82rem;
        font-weight: 600;
        height: 2.9rem;
        justify-content: center;
        width: 2.9rem;
    }

    .social-case-platform-dot {
        align-items: center;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        bottom: -.05rem;
        color: #db2777;
        display: inline-flex;
        font-size: .58rem;
        font-weight: 700;
        height: 1.05rem;
        justify-content: center;
        position: absolute;
        right: -.1rem;
        width: 1.05rem;
    }

    .social-case-title-line {
        align-items: baseline;
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        min-width: 0;
    }

    .social-case-handle {
        color: #111827;
        font-size: 1.02rem;
        font-weight: 700;
    }

    .social-case-meta {
        color: var(--case-muted);
        font-size: .78rem;
        font-weight: 500;
    }

    .social-case-badges,
    .social-case-actions {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
        margin-top: .35rem;
    }

    .social-case-actions {
        justify-content: flex-end;
        margin-top: 0;
    }

    .social-case-badge {
        align-items: center;
        background: #f1f5f9;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        color: #334155;
        display: inline-flex;
        font-size: .72rem;
        font-weight: 600;
        gap: .25rem;
        line-height: 1;
        min-height: 1.45rem;
        padding: .25rem .55rem;
        white-space: nowrap;
    }

    .social-case-badge.success {
        background: #e8fbf6;
        border-color: #bcebe2;
        color: #00856f;
    }

    .social-case-badge.info {
        background: #ecfeff;
        border-color: #cffafe;
        color: #0e7490;
    }

    .social-case-badge.hot {
        background: #fff7ed;
        border-color: #ffedd5;
        color: #c2410c;
    }

    .social-case-badge.neutral {
        background: #f1f5f9;
        border-color: #e2e8f0;
        color: #475569;
    }

    .social-case-badge.warning {
        background: #fff7ed;
        border-color: #fed7aa;
        color: #c2410c;
    }

    .social-case-action {
        align-items: center;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: .5rem;
        color: #111827;
        cursor: pointer;
        display: inline-flex;
        font-size: .78rem;
        font-weight: 600;
        gap: .35rem;
        min-height: 2.2rem;
        padding: .4rem .7rem;
        text-decoration: none;
    }

    .social-case-action.primary {
        background: oklch(55% .12 185);
        border-color: oklch(55% .12 185);
        color: #ffffff;
    }

    .social-case-action svg,
    .social-case-card-title svg,
    .social-case-tab svg {
        height: .95rem;
        width: .95rem;
    }

    .social-case-dropdown {
        position: relative;
    }

    .social-case-dropdown summary {
        list-style: none;
    }

    .social-case-dropdown summary::-webkit-details-marker {
        display: none;
    }

    .social-case-dropdown-menu {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: .75rem;
        box-shadow: 0 18px 35px rgba(15, 23, 42, .14);
        display: grid;
        min-width: 13rem;
        padding: .4rem;
        position: absolute;
        right: 0;
        top: calc(100% + .4rem);
        z-index: 20;
    }

    .social-case-dropdown-item {
        background: transparent;
        border: 0;
        border-radius: .55rem;
        color: #1f2937;
        cursor: pointer;
        font-size: .8rem;
        font-weight: 500;
        padding: .55rem .65rem;
        text-align: left;
    }

    .social-case-dropdown-item:hover {
        background: #f3f4f6;
    }

    .social-case-tabs {
        background: #ffffff;
        border-bottom: 1px solid var(--case-line);
        display: flex;
        gap: 2rem;
        margin: 0 -1rem;
        overflow-x: auto;
        padding: 0 1rem;
        position: sticky;
        top: 3rem;
        z-index: 40;
    }

    .social-case-tab {
        align-items: center;
        color: #64748b;
        display: inline-flex;
        flex: 0 0 auto;
        font-size: .86rem;
        font-weight: 600;
        gap: .35rem;
        padding: .85rem 0 .9rem;
        text-decoration: none;
    }

    .social-case-tab.is-active {
        box-shadow: inset 0 -2px 0 var(--case-accent);
        color: #111827;
    }

    .social-case-grid {
        align-items: start;
        display: grid;
        gap: 1rem;
        grid-template-columns: minmax(0, 1fr);
    }

    @media (min-width: 1100px) {
        .social-case-grid {
            grid-template-columns: minmax(0, 1fr) 25rem;
        }

        .social-case-aside {
            position: sticky;
            top: 1rem;
        }
    }

    .social-case-main,
    .social-case-aside {
        display: grid;
        gap: .85rem;
    }

    .social-case-tab-panel {
        display: grid;
        gap: .85rem;
    }

    [x-cloak] {
        display: none !important;
    }

    .social-case-card {
        background: var(--case-card);
        border: 1px solid var(--case-line);
        border-radius: .75rem;
        overflow: hidden;
    }

    .social-case-card-title {
        align-items: center;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        gap: .45rem;
        justify-content: space-between;
        padding: .75rem 1rem;
    }

    .social-case-card-title-main {
        align-items: center;
        color: #111827;
        display: inline-flex;
        font-size: .875rem;
        font-weight: 500;
        gap: .45rem;
    }

    .social-case-card-body {
        display: grid;
        gap: .75rem;
        padding: .85rem;
    }

    .social-case-comment {
        border-left: 2px solid #2dd4bf;
        color: #020617;
        font-size: .98rem;
        line-height: 1.55;
        padding-left: .75rem;
    }

    .social-case-note {
        background: #f0fdfa;
        border: 1px solid #ccfbf1;
        border-radius: .65rem;
        color: #0f172a;
        font-size: .92rem;
        line-height: 1.5;
        padding: .75rem;
    }

    .social-case-muted {
        color: #64748b;
        font-size: .78rem;
        font-weight: 500;
    }

    .social-case-facts {
        display: grid;
        gap: 0 .85rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .social-case-fact {
        align-items: center;
        border-bottom: 1px solid #eef2f7;
        display: grid;
        gap: .35rem .85rem;
        grid-template-columns: 1fr 1fr;
        padding: .55rem 0;
    }

    .social-case-fact span {
        color: #64748b;
        font-size: .78rem;
        font-weight: 500;
    }

    .social-case-fact strong,
    .social-case-fact p {
        color: #0f172a;
        font-size: .82rem;
        font-weight: 600;
        margin: 0;
        text-align: right;
    }

    .social-case-metrics {
        display: grid;
        gap: .55rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    @media (min-width: 760px) {
        .social-case-metrics {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    .social-case-pulse-metrics {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    @media (min-width: 760px) {
        .social-case-pulse-metrics {
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }
    }

    .social-case-pulse-metrics .social-case-metric {
        text-align: center;
    }

    .social-case-metric {
        border: 1px solid #e5e7eb;
        border-radius: .65rem;
        padding: .7rem;
    }

    .social-case-metric strong {
        color: #020617;
        display: block;
        font-size: .98rem;
        font-weight: 700;
    }

    .social-case-metric span {
        color: #475569;
        display: block;
        font-size: .72rem;
        font-weight: 500;
        margin-top: .15rem;
        text-transform: uppercase;
    }

    .social-case-alert {
        align-items: center;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: .75rem;
        display: flex;
        gap: .65rem;
        padding: .85rem;
    }

    .social-case-alert strong {
        color: #111827;
        display: block;
        font-size: .9rem;
        font-weight: 700;
    }

    .social-case-timeline {
        display: grid;
        gap: .8rem;
    }

    .social-case-timeline-item {
        align-items: start;
        display: grid;
        gap: .65rem;
        grid-template-columns: 2rem minmax(0, 1fr);
    }

    .social-case-activity-icon {
        align-items: center;
        background: #eef2ff;
        border-radius: 999px;
        color: #4f46e5;
        display: inline-flex;
        height: 2rem;
        justify-content: center;
        width: 2rem;
    }

    .social-case-activity-icon.green {
        background: #dcfce7;
        color: #15803d;
    }

    .social-case-activity-icon.orange {
        background: #ffedd5;
        color: #c2410c;
    }

    .social-case-activity-icon svg {
        height: 1rem;
        width: 1rem;
    }

    .social-case-conversation-head {
        align-items: start;
        display: flex;
        justify-content: space-between;
        gap: .75rem;
    }

    .social-case-conversation-summary {
        background: #ffffff;
        border: 1px solid #eef2f7;
        border-radius: .75rem;
        overflow: hidden;
    }

    .social-case-conversation-route {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: .55rem;
        justify-content: space-between;
        padding: .65rem .75rem;
    }

    .social-case-conversation-route-main {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
    }

    .social-case-conversation-summary-date,
    .social-case-conversation-route-arrow {
        color: #64748b;
        font-size: .78rem;
    }

    .social-case-conversation-metrics {
        border-top: 1px solid #eef2f7;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .social-case-conversation-metric {
        padding: .65rem .75rem;
    }

    .social-case-conversation-metric:not(:last-child) {
        border-right: 1px solid #eef2f7;
    }

    .social-case-conversation-metric span {
        color: #64748b;
        display: block;
        font-size: .72rem;
    }

    .social-case-conversation-metric strong {
        color: #0f172a;
        display: block;
        font-size: .94rem;
        line-height: 1.25;
        margin-top: .1rem;
    }

    .social-case-conversation-timeline {
        position: relative;
    }

    .social-case-conversation-timeline .social-case-timeline-item {
        padding-bottom: 1rem;
        position: relative;
    }

    .social-case-conversation-timeline .social-case-timeline-item:not(:last-child)::before {
        background: #e5e7eb;
        bottom: -.1rem;
        content: '';
        left: 1rem;
        position: absolute;
        top: 2rem;
        width: 1px;
    }

    .social-case-conversation-timeline .social-case-activity-icon {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        color: #64748b;
        position: relative;
        z-index: 1;
    }

    .social-case-conversation-timeline .social-case-activity-icon svg {
        color: #000000;
        height: .95rem;
        width: .95rem;
    }

    .social-case-activity-title {
        align-items: center;
        color: #0f172a;
        display: flex;
        font-size: .84rem;
        font-weight: 500;
        gap: .4rem;
        line-height: 1.35;
    }

    .social-case-conversation-kind,
    .social-case-activity-meta,
    .social-case-activity-note {
        color: #64748b;
        font-size: .78rem;
        font-weight: 500;
        margin: .15rem 0 0;
    }

    .social-case-conversation-bubble {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: .65rem;
        color: #0f172a;
        font-size: .9rem;
        line-height: 1.45;
        margin-top: .35rem;
        padding: .7rem;
    }

    .social-case-meter {
        background: #eef2f7;
        border-radius: 999px;
        height: .42rem;
        overflow: hidden;
    }

    .social-case-meter span {
        background: #d99a00;
        border-radius: inherit;
        display: block;
        height: 100%;
    }

    .social-case-post-preview {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: .65rem;
        color: #0f172a;
        font-size: .84rem;
        line-height: 1.45;
        padding: .75rem;
    }

    @media (max-width: 760px) {
        .social-case-header,
        .social-case-actions {
            align-items: stretch;
            flex-direction: column;
        }

        .social-case-action {
            justify-content: center;
            width: 100%;
        }

        .social-case-facts {
            grid-template-columns: 1fr;
        }
    }

    .dark .social-case {
        --case-card: rgba(15, 23, 42, .86);
        --case-ink: #e2e8f0;
        --case-line: rgba(148, 163, 184, .18);
        --case-muted: #94a3b8;
        --case-soft: #0f172a;
    }

    .dark .social-case-tabs {
        background: #0f172a;
    }

    .dark .social-case-card-title {
        border-bottom-color: #374151;
    }

    .dark .social-case-conversation-summary,
    .dark .social-case-conversation-metrics,
    .dark .social-case-conversation-metric:not(:last-child),
    .dark .social-case-conversation-timeline .social-case-activity-icon {
        border-color: rgba(148, 163, 184, .18);
    }

    .dark .social-case-conversation-summary,
    .dark .social-case-conversation-timeline .social-case-activity-icon {
        background: rgba(15, 23, 42, .86);
    }

    .dark .social-case-conversation-timeline .social-case-timeline-item:not(:last-child)::before {
        background: rgba(148, 163, 184, .18);
    }

    .dark .social-case-conversation-metric strong {
        color: #e2e8f0;
    }

    .dark .social-case-conversation-metric span,
    .dark .social-case-conversation-summary-date,
    .dark .social-case-conversation-route-arrow {
        color: #94a3b8;
    }

    .dark .social-case-card-title-main {
        color: #ffffff;
    }

    .dark .social-case-handle,
    .dark .social-case-fact strong,
    .dark .social-case-fact p,
    .dark .social-case-comment,
    .dark .social-case-activity-title,
    .dark .social-case-conversation-bubble,
    .dark .social-case-metric strong,
    .dark .social-case-alert strong,
    .dark .social-case-post-preview {
        color: #e2e8f0;
    }
</style>

<div class="social-case">
    <div
        class="social-case-shell"
        x-data="{
            tab: @js($activeTab),
            setTab(value) {
                this.tab = value;
                const url = new URL(window.location.href);
                url.searchParams.set('tab', value);
                window.history.pushState({}, '', url);
            },
        }"
        x-init="window.addEventListener('popstate', () => { tab = new URL(window.location.href).searchParams.get('tab') || 'resumen' })"
    >
        <header class="social-case-header">
            <div class="social-case-identity">
                <div class="social-case-avatar-wrap">
                    <span class="social-case-avatar">{{ $avatarText }}</span>
                    <span class="social-case-platform-dot">{{ $platform === 'instagram' ? 'ig' : ($platform === 'facebook' ? 'f' : 'wa') }}</span>
                </div>

                <div style="min-width:0">
                    <div class="social-case-title-line">
                        <span class="social-case-handle">{{ $authorLabel }}</span>
                        <span class="social-case-meta">{{ $platformLabel }} · Comentario en publicación · {{ $commentDateLabel }}</span>
                    </div>
                    <div class="social-case-badges">
                        @if ($record->is_lead)
                            <span class="social-case-badge success">Lead comercial</span>
                        @endif
                        <span class="social-case-badge success">{{ $statusLabel }}</span>
                        <span class="social-case-badge">Riesgo {{ $riskLabel }}</span>
                        <span class="social-case-badge">{{ $sentimentLabel }}</span>
                    </div>
                </div>
            </div>

            <div class="social-case-actions">
                @if ($patientUrl)
                    <a class="social-case-action primary" href="{{ $patientUrl }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        Ver ficha
                    </a>
                @else
                    <button class="social-case-action primary" type="button" wire:click="mountAction('link_existing_patient')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3"/><path d="M12.75 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z"/><path d="M3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109"/></svg>
                        Vincular ficha
                    </button>
                @endif
                <button class="social-case-action" type="button" wire:click="mountAction('create_appointment')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 2v4m8-4v4M3.5 9.5h17M5 5.5h14a1.5 1.5 0 0 1 1.5 1.5v12A1.5 1.5 0 0 1 19 20.5H5A1.5 1.5 0 0 1 3.5 19V7A1.5 1.5 0 0 1 5 5.5Z"/></svg>
                    Crear cita
                </button>
                <details class="social-case-dropdown">
                    <summary class="social-case-action" aria-label="Acciones del caso">•••</summary>
                    <div class="social-case-dropdown-menu">
                        <button class="social-case-dropdown-item" type="button" wire:click="mountAction('route_to_whatsapp')">Derivar a WhatsApp</button>
                        <button class="social-case-dropdown-item" type="button" wire:click="mountAction('auto_reply')">Reintentar auto-reply</button>
                        <button class="social-case-dropdown-item" type="button" wire:click="mountAction('escalate')">Escalar a un humano</button>
                        <button class="social-case-dropdown-item" type="button" wire:click="mountAction('mark_as_spam')">Marcar como spam</button>
                        <button class="social-case-dropdown-item" type="button" wire:click="mountAction('ignore')">Ignorar caso</button>
                    </div>
                </details>
            </div>
        </header>

        <nav class="social-case-tabs" aria-label="Secciones del caso social">
            @foreach ($tabs as $tabKey => $tab)
                <a class="social-case-tab" href="{{ request()->fullUrlWithQuery(['tab' => $tabKey]) }}" :class="{ 'is-active': tab === '{{ $tabKey }}' }" @click.prevent="setTab('{{ $tabKey }}')">
                    @switch($tab['icon'])
                        @case('chat')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z"/></svg>
                            @break
                        @case('clock')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 6v6h4.5"/><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            @break
                        @case('clinical')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 3v4M15 3v4M8 13h8M12 9v8"/><path d="M5 7h14v12H5z"/></svg>
                            @break
                        @default
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 3 1.7 5.1L19 10l-5.3 1.9L12 17l-1.7-5.1L5 10l5.3-1.9L12 3Z"/></svg>
                    @endswitch
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="social-case-grid">
            <main class="social-case-main">
                <div class="social-case-tab-panel" x-show="tab === 'resumen'" x-cloak>
                    <section class="social-case-card">
                        <div class="social-case-card-title"><span class="social-case-card-title-main">Comentario recibido</span></div>
                        <div class="social-case-card-body">
                            <div class="social-case-comment">“{{ $record->comment_text ?: 'Sin texto registrado.' }}”</div>
                            <div class="social-case-muted">{{ $commentDate?->diffForHumans() ?? 'Sin fecha' }} · Comentario en publicación</div>
                        </div>
                    </section>

                    <section class="social-case-card">
                        <div class="social-case-card-title">
                            <span class="social-case-card-title-main">Auto-respuesta enviada</span>
                            @if (filled($record->auto_reply_message))
                                <span class="social-case-badge success">publicada en Meta</span>
                            @endif
                        </div>
                        <div class="social-case-card-body">
                            <div class="social-case-note">{{ $record->auto_reply_error ?: ($record->auto_reply_message ?: 'Sin auto-respuesta registrada.') }}</div>
                            <div class="social-case-actions" style="justify-content:flex-start">
                                <button class="social-case-action" type="button" wire:click="mountAction('auto_reply')">Reintentar</button>
                                <button class="social-case-action" type="button" wire:click="mountAction('route_to_whatsapp')">Derivar a WhatsApp</button>
                            </div>
                        </div>
                    </section>

                    <section class="social-case-card">
                        <div class="social-case-card-title"><span class="social-case-card-title-main">Análisis de IA</span></div>
                        <div class="social-case-card-body">
                            <div class="social-case-facts">
                                <div class="social-case-fact"><span>Motivo</span><p>{{ $record->ai_reason ?: 'Sin motivo registrado.' }}</p></div>
                                <div class="social-case-fact"><span>Acción</span><p>{{ $suggestedActionLabel }}</p></div>
                                <div class="social-case-fact"><span>Sentimiento</span><p>{{ $sentimentLabel }}</p></div>
                                <div class="social-case-fact"><span>Revisión humana</span><p>{{ $record->requires_human_review ? 'Requerida' : 'No requerida' }}</p></div>
                            </div>
                        </div>
                    </section>

                    <section class="social-case-card">
                        <div class="social-case-card-title"><span class="social-case-card-title-main">Pulso del cliente</span></div>
                        <div class="social-case-card-body">
                            <div class="social-case-metrics social-case-pulse-metrics">
                                @foreach ($case['pulse'] as $metric)
                                    <div class="social-case-metric"><strong>{{ $metric['value'] }}</strong><span>{{ $metric['label'] }}</span></div>
                                @endforeach
                            </div>
                        </div>
                    </section>
                </div>

                <div class="social-case-tab-panel" x-show="tab === 'conversacion'" x-cloak>
                    <section class="social-case-card">
                        <div class="social-case-card-title"><span class="social-case-card-title-main">Hilo de conversación</span></div>
                        <div class="social-case-card-body">
                            @php
                                $conversationCollection = collect($case['conversation']);
                                $conversationChannels = $conversationCollection->where('channel', '!=', 'system')->values();
                                $firstConversationChannel = $conversationChannels->first();
                                $lastConversationChannel = $conversationChannels->last();
                            @endphp

                            @if ($conversationCollection->isNotEmpty())
                                <div class="social-case-conversation-summary">
                                    <div class="social-case-conversation-route">
                                        <div class="social-case-conversation-route-main">
                                            @if ($firstConversationChannel)
                                                <span class="social-case-muted">Canales:</span>
                                                <span class="social-case-badge {{ $firstConversationChannel['channel_class'] }}">{{ $firstConversationChannel['channel_label'] }}</span>
                                            @endif
                                            @if ($firstConversationChannel && $lastConversationChannel && $firstConversationChannel['channel'] !== $lastConversationChannel['channel'])
                                                <span class="social-case-conversation-route-arrow">→</span>
                                                <span class="social-case-badge {{ $lastConversationChannel['channel_class'] }}">{{ $lastConversationChannel['channel_label'] }}</span>
                                            @endif
                                        </div>
                                        <span class="social-case-conversation-summary-date">{{ $conversationCollection->first()['short_date'] ?? 'Fecha no registrada' }}</span>
                                    </div>
                                    <div class="social-case-conversation-metrics">
                                        <div class="social-case-conversation-metric"><span>Respuesta prom.</span><strong>{{ $case['conversation_metrics']['response_time'] }}</strong></div>
                                        <div class="social-case-conversation-metric"><span>Mensajes</span><strong>{{ $case['conversation_metrics']['message_count'] }}</strong></div>
                                        <div class="social-case-conversation-metric"><span>Automatización</span><strong>{{ $case['conversation_metrics']['automation_rate'] }}%</strong></div>
                                    </div>
                                </div>
                            @endif

                            <div class="social-case-timeline social-case-conversation-timeline">
                                @forelse ($case['conversation'] as $event)
                                    <div class="social-case-timeline-item">
                                        <span class="social-case-activity-icon {{ $event['color'] }}">
                                            @switch($event['platform'])
                                                @case('facebook')
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 21v-7h2.35l.35-2.72h-2.7V9.55c0-.79.22-1.33 1.35-1.33h1.44V5.79c-.25-.03-1.1-.1-2.1-.1-2.08 0-3.5 1.27-3.5 3.6v1.99H8.34V14h2.35v7h2.81Z" /></svg>
                                                    @break
                                                @case('instagram')
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><rect width="15" height="15" x="4.5" y="4.5" rx="4" /><path stroke-linecap="round" stroke-linejoin="round" d="M15.5 11.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" /><path stroke-linecap="round" d="M16.75 7.75h.01" /></svg>
                                                    @break
                                                @case('whatsapp')
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.75 19.25 6 15.6a7 7 0 1 1 2.42 2.35l-3.67 1.3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9.2 8.95c.18-.5.42-.55.74-.55h.43c.22 0 .4.14.49.34l.68 1.52c.08.18.04.39-.1.53l-.47.48c.48.84 1.16 1.52 2 2l.48-.47c.14-.14.35-.18.53-.1l1.52.68c.2.09.34.27.34.49v.43c0 .32-.05.56-.55.74-.4.14-.83.21-1.28.21-2.64 0-5.26-2.62-5.26-5.26 0-.45.07-.88.21-1.28Z" /></svg>
                                                    @break
                                                @case('action')
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 8V4H8"></path><rect width="16" height="12" x="4" y="8" rx="2"></rect><path d="M2 14h2"></path><path d="M20 14h2"></path><path d="M15 13v2"></path><path d="M9 13v2"></path></svg>
                                                    @break
                                                @default
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z"/></svg>
                                            @endswitch
                                        </span>
                                        <div>
                                            <div class="social-case-conversation-head">
                                                <div>
                                                    <div class="social-case-activity-title">
                                                        {{ $event['author'] }}
                                                        @unless ($event['is_automated'])
                                                            <span class="social-case-badge {{ $event['channel_class'] }}">{{ $event['channel_label'] }}</span>
                                                        @endunless
                                                    </div>
                                                    <div class="social-case-conversation-kind">{{ $event['kind_label'] }}</div>
                                                </div>
                                                <span class="social-case-muted">{{ $event['time'] }}</span>
                                            </div>
                                            <div class="social-case-conversation-bubble">{{ $event['message'] }}</div>
                                            @if (filled($event['rule_label']))
                                                <div class="social-case-muted" style="margin-top:.25rem">{{ $event['rule_label'] }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <p class="social-case-muted">Sin conversación registrada.</p>
                                @endforelse
                            </div>
                        </div>
                    </section>
                </div>

                <div class="social-case-tab-panel" x-show="tab === 'actividad'" x-cloak>
                    <section class="social-case-card">
                        <div class="social-case-card-title"><span class="social-case-card-title-main">Actividad</span></div>
                        <div class="social-case-card-body">
                            <div class="social-case-timeline">
                                @forelse ($case['activity'] as $event)
                                    <div class="social-case-timeline-item">
                                        <span class="social-case-activity-icon {{ $event['color'] }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 6v6h4.5"/><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                        </span>
                                        <div>
                                            <div class="social-case-activity-title">{{ $event['label'] }}</div>
                                            <div class="social-case-activity-meta">{{ $event['date'] ?: 'Fecha no registrada' }}{{ $event['duration'] ? ' / '.$event['duration'].'s de actividad' : '' }}</div>
                                            <p class="social-case-activity-note">Evento registrado en el flujo digital del lead.</p>
                                        </div>
                                    </div>
                                @empty
                                    <p class="social-case-muted">Sin eventos de Smart Link todavía.</p>
                                @endforelse
                            </div>
                        </div>
                    </section>
                </div>

                <div class="social-case-tab-panel" x-show="tab === 'contexto-clinico'" x-cloak>
                    <section class="social-case-card">
                        <div class="social-case-card-title">
                            <span class="social-case-card-title-main">Contexto clínico 360</span>
                            @if ($patientUrl)
                                <a class="social-case-action primary" href="{{ $patientUrl }}">Ver ficha</a>
                            @else
                                <button class="social-case-action primary" type="button" wire:click="mountAction('link_existing_patient')">Vincular ficha</button>
                            @endif
                        </div>
                        <div class="social-case-card-body">
                            @unless ($patient)
                                <div class="social-case-alert">
                                    <span class="social-case-badge warning">!</span>
                                    <div><strong>Lead sin ficha clínica</strong><span class="social-case-muted">Aún no está vinculado a un paciente registrado.</span></div>
                                </div>
                            @endunless
                            <div class="social-case-facts">
                                <div class="social-case-fact"><span>Última cita</span><p>{{ $case['clinical']['last_appointment'] }}</p></div>
                                <div class="social-case-fact"><span>Procedimiento</span><p>{{ $case['clinical']['procedure'] }}</p></div>
                                <div class="social-case-fact"><span>Doctor</span><p>{{ $case['clinical']['doctor'] }}</p></div>
                                <div class="social-case-fact"><span>Alertas</span><p>{{ $case['clinical']['alerts'] }}</p></div>
                            </div>
                        </div>
                    </section>

                    <section class="social-case-card">
                        <div class="social-case-card-title"><span class="social-case-card-title-main">Historial social</span></div>
                        <div class="social-case-card-body">
                            <div class="social-case-metrics">
                                <div class="social-case-metric"><strong>{{ $case['social_history']['interactions'] }}</strong><span>Interacciones</span></div>
                                <div class="social-case-metric"><strong>{{ $case['social_history']['previous'] }}</strong><span>Previos</span></div>
                                <div class="social-case-metric"><strong>{{ $case['social_history']['origin'] }}</strong><span>Origen</span></div>
                            </div>
                        </div>
                    </section>
                </div>
            </main>

            <aside class="social-case-aside">
                <section class="social-case-card">
                    <div class="social-case-card-title"><span class="social-case-card-title-main">Nivel de riesgo</span></div>
                    <div class="social-case-card-body">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem">
                            <strong style="font-size:1.15rem">{{ $riskLabel }}</strong>
                            <span class="social-case-muted">Score {{ $score }}/100</span>
                        </div>
                        <div class="social-case-meter"><span style="width: {{ $scoreWidth }}%"></span></div>
                        <div class="social-case-muted">Termómetro de interés · {{ strtolower($classificationLabel) }}</div>
                    </div>
                </section>

                <section class="social-case-card">
                    <div class="social-case-card-title"><span class="social-case-card-title-main">CRM social</span></div>
                    <div class="social-case-card-body">
                        <div class="social-case-facts" style="grid-template-columns:1fr">
                            <div class="social-case-fact"><span>Cuenta</span><p>{{ $record->socialAccount?->account_name ?: 'Sin cuenta' }}</p></div>
                            <div class="social-case-fact"><span>Identidad</span><p>{{ $identity?->display_name ?: $identity?->username ?: 'Sin identidad' }}</p></div>
                            <div class="social-case-fact"><span>Paciente</span><p>{{ $patient?->full_name ?: 'Sin ficha vinculada' }}</p></div>
                            <div class="social-case-fact"><span>Procedimiento</span><p>{{ $record->suggestedProcedure?->name ?: 'Sin sugerencia' }}</p></div>
                            <div class="social-case-fact"><span>Token</span><p>{{ $record->tracking_token ?: 'Sin token' }}</p></div>
                        </div>
                    </div>
                </section>

                <section class="social-case-card">
                    <div class="social-case-card-title"><span class="social-case-card-title-main">Publicación original</span></div>
                    <div class="social-case-card-body">
                        <div class="social-case-post-preview">{{ $post?->caption ?: 'No hay texto de publicación asociado.' }}</div>
                        <div class="social-case-muted">{{ $record->socialAccount?->account_name ?: $platformLabel }} · {{ $platformLabel }} · {{ $post?->published_at?->diffForHumans() ?? 'Fecha no disponible' }}</div>
                        @if ($post?->permalink)
                            <a class="social-case-action" href="{{ $post->permalink }}" target="_blank" rel="noopener noreferrer">Abrir publicación</a>
                        @else
                            <span class="social-case-muted">Link no disponible</span>
                        @endif
                    </div>
                </section>

                <section class="social-case-card">
                    <div class="social-case-card-title"><span class="social-case-card-title-main">Datos técnicos</span></div>
                    <div class="social-case-card-body">
                        <div class="social-case-facts" style="grid-template-columns:1fr">
                            <div class="social-case-fact"><span>Procesado</span><p>{{ $processedAt }}</p></div>
                            <div class="social-case-fact"><span>ID externo</span><p>{{ $record->external_comment_id ?: 'No disponible' }}</p></div>
                            <div class="social-case-fact"><span>Canal</span><p>{{ $record->is_hidden ? 'Oculto' : 'Público' }}</p></div>
                            <div class="social-case-fact"><span>Conversión</span><p>{{ $conversionStatusLabel }}</p></div>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</div>
