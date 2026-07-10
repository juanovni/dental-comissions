@php
    $comment = $this->comment();
    $events = $this->events();
    $stats = $this->stats();
    $mapper = \App\Services\SocialLinkEventMapper::class;
    $aiData = null;
    if ($this->aiInsight) {
        $aiData = json_decode($this->aiInsight, true);
    }
@endphp

<style>
    .pulse-timeline {
        --pt-ink: #0f172a;
        --pt-muted: #64748b;
        --pt-card: #ffffff;
        --pt-border: #e5e7eb;
        --pt-soft: #f8fafc;
        color: var(--pt-ink);
    }

    .pulse-stats {
        display: grid;
        gap: .6rem;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        margin-bottom: 1rem;
    }

    @media (min-width: 700px) {
        .pulse-stats {
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }
    }

    .pulse-stat {
        background: var(--pt-card);
        border: 1px solid var(--pt-border);
        border-radius: .65rem;
        padding: .65rem;
        text-align: center;
    }

    .pulse-stat-value {
        color: var(--pt-ink);
        font-size: 1.15rem;
        font-weight: 700;
        line-height: 1;
    }

    .pulse-stat-label {
        color: var(--pt-muted);
        font-size: .68rem;
        font-weight: 600;
        margin-top: .2rem;
    }

    .pulse-toolbar {
        align-items: center;
        display: flex;
        gap: .5rem;
        margin-bottom: .85rem;
        flex-wrap: wrap;
    }

    .pulse-btn {
        align-items: center;
        background: var(--pt-card);
        border: 1px solid var(--pt-border);
        border-radius: .5rem;
        color: #334155;
        cursor: pointer;
        display: inline-flex;
        font-size: .76rem;
        font-weight: 600;
        gap: .35rem;
        padding: .4rem .65rem;
        transition: .14s ease;
    }

    .pulse-btn:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .pulse-btn.active {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #1d4ed8;
    }

    .pulse-btn-ai {
        background: #eff6ff;
        border-color: #93c5fd;
        color: #1d4ed8;
    }

    .pulse-btn-ai:hover {
        background: #dbeafe;
    }

    .pulse-btn-ai.is-loading {
        opacity: .6;
        pointer-events: none;
    }

    .pulse-timeline-list {
        display: flex;
        flex-direction: column;
        gap: 0;
        position: relative;
    }

    .pulse-timeline-list::before {
        background: #e5e7eb;
        content: '';
        left: 14px;
        position: absolute;
        top: 0;
        bottom: 0;
        width: 2px;
    }

    .pulse-event {
        display: flex;
        gap: .75rem;
        padding: .55rem 0;
        position: relative;
    }

    .pulse-event-dot {
        align-items: center;
        background: var(--pt-card);
        border: 2px solid #e5e7eb;
        border-radius: 999px;
        display: flex;
        flex: 0 0 auto;
        font-size: .72rem;
        height: 1.6rem;
        justify-content: center;
        width: 1.6rem;
        z-index: 1;
    }

    .pulse-event-dot.color-blue { border-color: #3b82f6; color: #3b82f6; background: #eff6ff; }
    .pulse-event-dot.color-indigo { border-color: #6366f1; color: #6366f1; background: #eef2ff; }
    .pulse-event-dot.color-gray { border-color: #9ca3af; color: #6b7280; background: #f9fafb; }
    .pulse-event-dot.color-orange { border-color: #f97316; color: #f97316; background: #fff7ed; }
    .pulse-event-dot.color-cyan { border-color: #06b6d4; color: #06b6d4; background: #ecfeff; }
    .pulse-event-dot.color-teal { border-color: #14b8a6; color: #14b8a6; background: #f0fdfa; }
    .pulse-event-dot.color-emerald { border-color: #10b981; color: #10b981; background: #ecfdf5; }
    .pulse-event-dot.color-green { border-color: #22c55e; color: #22c55e; background: #f0fdf4; }

    .pulse-event-dot.is-group {
        border-style: dashed;
        background: #f1f5f9;
        border-color: #9ca3af;
        color: #6b7280;
        font-size: .6rem;
        font-weight: 800;
    }

    .pulse-event-body {
        background: var(--pt-card);
        border: 1px solid var(--pt-border);
        border-radius: .6rem;
        flex: 1;
        min-width: 0;
        padding: .6rem .75rem;
    }

    .pulse-event-label {
        color: var(--pt-ink);
        font-size: .82rem;
        font-weight: 600;
        line-height: 1.3;
    }

    .pulse-event-meta {
        color: var(--pt-muted);
        font-size: .7rem;
        margin-top: .2rem;
    }

    .pulse-event-meta strong {
        color: #334155;
        font-weight: 600;
    }

    .pulse-video-bar {
        background: #e5e7eb;
        border-radius: 999px;
        display: block;
        height: .35rem;
        margin-top: .4rem;
        overflow: hidden;
        width: 100%;
    }

    .pulse-video-fill {
        background: #14b8a6;
        border-radius: inherit;
        height: 100%;
        transition: width .3s ease;
    }

    .pulse-video-fill.complete {
        background: #22c55e;
    }

    .pulse-empty {
        align-items: center;
        background: var(--pt-card);
        border: 1px dashed #cbd5e1;
        border-radius: .75rem;
        color: var(--pt-muted);
        display: grid;
        gap: .3rem;
        justify-items: center;
        padding: 2rem;
        text-align: center;
    }

    .pulse-empty strong {
        color: var(--pt-ink);
        font-size: .9rem;
        font-weight: 600;
    }

    .pulse-ai-box {
        background: linear-gradient(180deg, #eef6ff, #ffffff);
        border: 1px solid #bfdbfe;
        border-radius: .75rem;
        margin-top: 1rem;
        padding: 1rem;
    }

    .pulse-ai-box h4 {
        color: #1d4ed8;
        font-size: .82rem;
        font-weight: 700;
        margin: 0 0 .7rem;
    }

    .pulse-ai-box.is-loading {
        opacity: .7;
    }

    .pulse-ai-field {
        margin-bottom: .6rem;
    }

    .pulse-ai-field-label {
        color: #1d4ed8;
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .pulse-ai-field-value {
        color: var(--pt-ink);
        font-size: .84rem;
        line-height: 1.5;
        margin-top: .15rem;
    }

    .pulse-intention {
        display: inline-flex;
        border-radius: 999px;
        font-size: .7rem;
        font-weight: 800;
        padding: .2rem .6rem;
        text-transform: uppercase;
    }

    .pulse-intention.bajo { background: #f1f5f9; color: #475569; }
    .pulse-intention.medio { background: #fffbeb; color: #b45309; }
    .pulse-intention.alto { background: #ecfdf5; color: #047857; }

    .dark .pulse-timeline {
        --pt-card: rgba(15, 23, 42, .86);
        --pt-border: rgba(148, 163, 184, .16);
        --pt-ink: #e5e7eb;
        --pt-muted: #94a3b8;
        --pt-soft: #0f172a;
    }

    .dark .pulse-event-dot {
        background: rgba(15, 23, 42, .9);
    }

    .dark .pulse-event-dot.is-group {
        background: rgba(15, 23, 42, .7);
    }

    .dark .pulse-ai-box {
        background: linear-gradient(180deg, rgba(29, 78, 216, .12), rgba(15, 23, 42, .86));
        border-color: rgba(96, 165, 250, .2);
    }

    .dark .pulse-ai-box h4 {
        color: #93c5fd;
    }

    .dark .pulse-ai-field-label {
        color: #93c5fd;
    }

    .dark .pulse-ai-field-value {
        color: #e2e8f0;
    }

    .dark .pulse-btn {
        background: rgba(15, 23, 42, .86);
        border-color: rgba(148, 163, 184, .16);
        color: #cbd5e1;
    }

    .dark .pulse-btn:hover {
        background: rgba(30, 41, 59, .9);
    }

    .dark .pulse-btn.active {
        background: rgba(29, 78, 216, .2);
        border-color: rgba(96, 165, 250, .32);
        color: #93c5fd;
    }

    .dark .pulse-btn-ai {
        background: rgba(29, 78, 216, .18);
        border-color: rgba(96, 165, 250, .24);
        color: #93c5fd;
    }
</style>

<div class="pulse-timeline">
    <div class="pulse-stats">
        <div class="pulse-stat">
            <div class="pulse-stat-value">{{ $stats['total'] }}</div>
            <div class="pulse-stat-label">Eventos</div>
        </div>
        <div class="pulse-stat">
            <div class="pulse-stat-value">{{ $stats['sessions'] }}</div>
            <div class="pulse-stat-label">Sesiones</div>
        </div>
        <div class="pulse-stat">
            <div class="pulse-stat-value">{{ $stats['video_progress'] }}%</div>
            <div class="pulse-stat-label">Video</div>
        </div>
        <div class="pulse-stat">
            <div class="pulse-stat-value">{{ $stats['duration'] }}s</div>
            <div class="pulse-stat-label">Permanencia</div>
        </div>
        <div class="pulse-stat">
            <div class="pulse-stat-value">{{ $stats['revisits'] }}</div>
            <div class="pulse-stat-label">Visitas</div>
        </div>
        <div class="pulse-stat">
            <div class="pulse-stat-value">{{ $stats['whatsapp_clicks'] }}</div>
            <div class="pulse-stat-label">WA Clics</div>
        </div>
    </div>

    <div class="pulse-toolbar">
        <button class="pulse-btn {{ $this->sortOrder === 'newest' ? 'active' : '' }}" wire:click="toggleSort">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width:.78rem;height:.78rem"><path d="M3 7.5 7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5"/></svg>
            @if ($this->sortOrder === 'newest')
                <span>Más reciente</span>
            @else
                <span>Más antiguo</span>
            @endif
        </button>

        <button class="pulse-btn {{ $this->showAllPings ? 'active' : '' }}" wire:click="togglePings">
            @if ($this->showAllPings)
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width:.78rem;height:.78rem"><path d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                <span>Ocultar pings</span>
            @else
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width:.78rem;height:.78rem"><path d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                <span>Ver todos los pings</span>
            @endif
        </button>

        <button
            class="pulse-btn pulse-btn-ai {{ $this->analyzing ? 'is-loading' : '' }}"
            wire:click="$dispatchTo('customer-pulse-timeline', 'analyze-customer-pulse')"
            wire:loading.attr="disabled"
        >
            @if ($this->analyzing)
                <svg class="animate-spin" style="width:.85rem;height:.85rem" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                <span>Analizando...</span>
            @else
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width:.85rem;height:.85rem"><path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 1-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 1 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 1 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 1-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 1-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 1 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 1 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 1-2.455 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 1-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 1 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 1 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 1-1.423 1.423Z"/></svg>
                <span>Analizar comportamiento</span>
            @endif
        </button>
    </div>

    @if ($aiData)
        <div class="pulse-ai-box" wire:loading.class="is-loading">
            <h4>Insights del comportamiento</h4>

            <div class="pulse-ai-field">
                <div class="pulse-ai-field-label">Resumen</div>
                <div class="pulse-ai-field-value">{{ $aiData['resumen'] ?? 'Sin resumen' }}</div>
            </div>

            <div class="pulse-ai-field">
                <div class="pulse-ai-field-label">Nivel de intencion</div>
                <span class="pulse-intention {{ $aiData['intencion'] ?? 'bajo' }}">{{ ucfirst($aiData['intencion'] ?? 'bajo') }}</span>
            </div>

            <div class="pulse-ai-field">
                <div class="pulse-ai-field-label">Objecion probable</div>
                <div class="pulse-ai-field-value">{{ $aiData['objecion_probable'] ?? 'Sin objecion detectada' }}</div>
            </div>

            <div class="pulse-ai-field">
                <div class="pulse-ai-field-label">Proxima accion recomendada</div>
                <div class="pulse-ai-field-value">{{ $aiData['proxima_accion'] ?? 'Sin recomendacion' }}</div>
            </div>
        </div>
    @endif

    @if ($events->isNotEmpty())
        <div class="pulse-timeline-list">
            @foreach ($events as $event)
                @php
                    $isGroup = $event->is_group ?? false;
                    if ($isGroup) {
                        $eventType = 'engagement_ping';
                        $label = "Permanecio en la landing ({$event->metadata['count']}x)";
                        $color = 'gray';
                        $icon = 'clock';
                    } else {
                        $mapped = $mapper::get($event->event_type);
                        $eventType = $event->event_type;
                        $label = $mapped['label'];
                        $color = $mapped['color'];
                        $icon = $mapped['icon'];
                    }
                    $videoProgress = $mapper::progress($eventType);
                    $timeAgo = $event->created_at?->diffForHumans();
                    $duration = $event->duration_seconds;
                    $meta = $event->metadata;
                @endphp

                <div class="pulse-event">
                    <div class="pulse-event-dot color-{{ $color }} {{ $isGroup ? 'is-group' : '' }}">
                        @if ($icon === 'eye')
                            <svg style="width:.85rem;height:.85rem" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        @elseif ($icon === 'arrow-path')
                            <svg style="width:.85rem;height:.85rem" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/></svg>
                        @elseif ($icon === 'clock')
                            <svg style="width:.85rem;height:.85rem" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @elseif ($icon === 'fire')
                            <svg style="width:.85rem;height:.85rem" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z"/></svg>
                        @elseif ($icon === 'play')
                            <svg style="width:.85rem;height:.85rem" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        @elseif ($icon === 'check-circle')
                            <svg style="width:.85rem;height:.85rem" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @elseif ($icon === 'chat-bubble-left')
                            <svg style="width:.85rem;height:.85rem" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 01-.923 1.785A5.969 5.969 0 006 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337z"/></svg>
                        @else
                            <svg style="width:.85rem;height:.85rem" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                        @endif
                    </div>

                    <div class="pulse-event-body">
                        <div class="pulse-event-label">{{ $label }}</div>
                        <div class="pulse-event-meta">
                            <strong>{{ $timeAgo }}</strong>
                            @if ($duration)
                                · {{ $duration }}s de permanencia
                            @endif
                            @if ($meta['visibility'] ?? null)
                                · {{ $meta['visibility'] }}
                            @endif
                        </div>

                        @if ($videoProgress !== null)
                            <div class="pulse-video-bar">
                                <div
                                    class="pulse-video-fill {{ $videoProgress >= 100 ? 'complete' : '' }}"
                                    style="width: {{ $videoProgress }}%"
                                ></div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="pulse-empty">
            <strong>Sin eventos de comportamiento</strong>
            <span>Este lead aun no ha interactuado con el Smart Link.</span>
        </div>
    @endif
</div>
