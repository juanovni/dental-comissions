<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ $csrfToken }}">
    <meta name="description" content="{{ $content['subtitle'] ?? 'Valoracion dental personalizada, clara y sin presion.' }}">
    <meta property="og:title" content="{{ $content['title'] ?? 'Tu nueva sonrisa, planificada a medida.' }}">
    <meta property="og:description" content="{{ $preview['text'] }}">
    <title>{{ $content['eyebrow'] ?? 'Valoracion dental' }} | Clinica Dental</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @php
        $videoUrl = (string) ($content['video_url'] ?? '');
        $isVideoFile = filled($videoUrl) && \Illuminate\Support\Str::of($videoUrl)->lower()->endsWith(['.mp4', '.webm', '.ogg']);
    @endphp
    <style>
        :root {
            --sp-bg: #f4f8fb;
            --sp-surface: #ffffff;
            --sp-ink: #081126;
            --sp-muted: #63718a;
            --sp-border: #e6edf5;
            --sp-teal: #009b8f;
            --sp-teal-dark: #00856f;
            --sp-teal-soft: #e9fbf7;
            --sp-blue-soft: #eef6ff;
            --sp-dark: #071126;
            --sp-shadow: 0 22px 60px rgba(15, 23, 42, .08);
            font-family: Outfit, ui-sans-serif, system-ui, sans-serif;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }

        body {
            background: var(--sp-bg);
            color: var(--sp-ink);
            margin: 0;
            min-height: 100vh;
        }

        a { color: inherit; }

        .sp-shell {
            margin: 0 auto;
            max-width: 1180px;
            padding: 0 clamp(1rem, 3vw, 2rem);
        }

        .sp-nav {
            align-items: center;
            background: rgba(255, 255, 255, .9);
            border-bottom: 1px solid var(--sp-border);
            display: flex;
            justify-content: space-between;
            min-height: 4.25rem;
        }

        .sp-brand,
        .sp-token {
            align-items: center;
            display: inline-flex;
            gap: .55rem;
        }

        .sp-brand-mark {
            align-items: center;
            background: var(--sp-teal);
            border-radius: .65rem;
            color: #ffffff;
            display: inline-flex;
            font-size: .8rem;
            font-weight: 800;
            height: 2rem;
            justify-content: center;
            width: 2rem;
        }

        .sp-brand strong {
            color: var(--sp-ink);
            font-size: .96rem;
            font-weight: 700;
        }

        .sp-token {
            background: #f8fbfd;
            border: 1px solid var(--sp-border);
            border-radius: 999px;
            color: #50617a;
            font-size: .72rem;
            font-weight: 700;
            padding: .32rem .62rem;
        }

        .sp-token::before {
            background: var(--sp-teal);
            border-radius: 999px;
            content: '';
            height: .4rem;
            width: .4rem;
        }

        .sp-hero-wrap {
            background:
                radial-gradient(circle at 50% 18%, rgba(0, 155, 143, .07), transparent 23rem),
                linear-gradient(180deg, #f7fbff, #f4f8fb);
            border-bottom: 1px solid #eef3f8;
        }

        .sp-hero {
            align-items: center;
            display: grid;
            gap: clamp(2rem, 5vw, 5rem);
            grid-template-columns: minmax(0, .98fr) minmax(21rem, .88fr);
            min-height: 38rem;
            padding: clamp(2.5rem, 7vw, 6rem) 0 clamp(2rem, 5vw, 4rem);
        }

        .sp-eyebrow {
            color: var(--sp-teal);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .12em;
            margin: 0 0 .95rem;
            text-transform: uppercase;
        }

        .sp-title {
            color: var(--sp-ink);
            font-size: clamp(2.8rem, 6vw, 4.9rem);
            font-weight: 800;
            letter-spacing: -.06em;
            line-height: 1.02;
            margin: 0;
            max-width: 10.8ch;
        }

        .sp-title span {
            color: var(--sp-teal);
            display: block;
        }

        .sp-typewriter {
            border-right: .08em solid currentColor;
            display: inline;
            padding-right: .04em;
            animation: sp-caret .72s step-end infinite;
        }

        .sp-typewriter.is-complete {
            animation: none;
            border-right: 0;
            padding-right: 0;
        }

        @keyframes sp-caret {
            50% { border-color: transparent; }
        }

        .sp-subtitle {
            color: var(--sp-muted);
            font-size: 1rem;
            line-height: 1.7;
            margin: 1.25rem 0 0;
            max-width: 38rem;
        }

        .sp-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .8rem;
            margin-top: 1.4rem;
        }

        .sp-btn {
            align-items: center;
            border-radius: .8rem;
            display: inline-flex;
            font-size: .88rem;
            font-weight: 800;
            gap: .48rem;
            justify-content: center;
            min-height: 3rem;
            padding: .8rem 1.15rem;
            text-decoration: none;
            transition: transform .16s ease, box-shadow .16s ease;
        }

        .sp-btn svg {
            flex: 0 0 auto;
            height: 1.05rem;
            width: 1.05rem;
        }

        .sp-btn:hover { transform: translateY(-1px); }

        .sp-btn-whatsapp {
            background: #079455;
            box-shadow: 0 14px 28px rgba(7, 148, 85, .2);
            color: #ffffff;
        }

        .sp-btn-soft {
            background: #ffffff;
            border: 1px solid var(--sp-border);
            color: var(--sp-ink);
        }

        .sp-sticky-whatsapp {
            bottom: 1.25rem;
            position: fixed;
            right: 1.25rem;
            z-index: 40;
        }

        .sp-plan-card {
            background: var(--sp-surface);
            border: 1px solid var(--sp-border);
            border-radius: 1.45rem;
            box-shadow: var(--sp-shadow);
            padding: 1.65rem;
            position: relative;
        }

        .sp-phase {
            align-items: center;
            background: #ffffff;
            border: 4px solid #e0f5f2;
            border-radius: 999px;
            color: var(--sp-teal-dark);
            display: flex;
            font-size: .72rem;
            font-weight: 900;
            height: 4.4rem;
            justify-content: center;
            position: absolute;
            right: 1.35rem;
            top: 1.35rem;
            width: 4.4rem;
        }

        .sp-card-label {
            color: #8797b1;
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .sp-procedure {
            color: var(--sp-ink);
            font-size: 1.35rem;
            font-weight: 800;
            margin: .28rem 5rem 1rem 0;
        }

        .sp-facts {
            display: grid;
            gap: .75rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-bottom: 1rem;
        }

        .sp-fact {
            background: #f6f9fc;
            border-radius: .8rem;
            padding: .9rem;
        }

        .sp-fact span {
            color: #8a98af;
            display: block;
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .08em;
            margin-bottom: .35rem;
            text-transform: uppercase;
        }

        .sp-fact strong {
            color: var(--sp-ink);
            font-size: .9rem;
            font-weight: 800;
        }

        .sp-checks {
            display: grid;
            gap: .52rem;
            margin: 1rem 0;
        }

        .sp-check {
            align-items: center;
            color: #42516a;
            display: flex;
            font-size: .84rem;
            gap: .5rem;
        }

        .sp-check::before {
            align-items: center;
            background: #d9fbf4;
            border-radius: 999px;
            color: var(--sp-teal);
            content: '✓';
            display: inline-flex;
            flex: 0 0 auto;
            font-size: .68rem;
            font-weight: 900;
            height: 1rem;
            justify-content: center;
            width: 1rem;
        }

        .sp-media {
            background:
                linear-gradient(180deg, rgba(207, 243, 252, .88), rgba(255, 255, 255, .4)),
                #eef8fb;
            border: 1px solid #cdebf3;
            border-radius: 1rem;
            min-height: 12.6rem;
            overflow: hidden;
            position: relative;
        }

        .sp-media iframe,
        .sp-media img,
        .sp-media video {
            border: 0;
            height: 100%;
            inset: 0;
            object-fit: cover;
            position: absolute;
            width: 100%;
        }

        .sp-media img {
            display: block;
        }

        .sp-aligner {
            border: 3px solid rgba(8, 17, 38, .13);
            border-top-color: rgba(8, 17, 38, .24);
            border-radius: 44% 48% 36% 40%;
            height: 4.6rem;
            left: 50%;
            position: absolute;
            top: 48%;
            transform: translate(-50%, -50%) rotate(-8deg);
            width: 12.5rem;
        }

        .sp-aligner::before,
        .sp-aligner::after {
            background: rgba(255, 255, 255, .45);
            border-radius: 999px;
            content: '';
            height: 1rem;
            position: absolute;
            top: 1.2rem;
            width: 2.2rem;
        }

        .sp-aligner::before { left: 2rem; }
        .sp-aligner::after { right: 2rem; }

        .sp-media-caption {
            background: rgba(255, 255, 255, .82);
            border-radius: 999px;
            bottom: .7rem;
            color: #718199;
            font-size: .65rem;
            font-weight: 800;
            left: .7rem;
            letter-spacing: .08em;
            padding: .3rem .55rem;
            position: absolute;
            text-transform: uppercase;
        }

        .sp-video-progress {
            background: rgba(8, 17, 38, .12);
            border-radius: 999px;
            bottom: .85rem;
            height: .42rem;
            left: .85rem;
            overflow: hidden;
            position: absolute;
            right: .85rem;
            z-index: 2;
        }

        .sp-video-progress span {
            background: linear-gradient(90deg, #079455, #0fbc9f);
            display: block;
            height: 100%;
            transition: width .2s ease;
            width: 0%;
        }

        .sp-benefits {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            padding-bottom: clamp(2rem, 5vw, 4rem);
        }

        .sp-benefit {
            align-items: center;
            background: #ffffff;
            border: 1px solid var(--sp-border);
            border-radius: 1rem;
            display: flex;
            gap: .95rem;
            padding: 1rem;
        }

        .sp-benefit-icon {
            align-items: center;
            background: #f0fbfa;
            border-radius: .85rem;
            color: var(--sp-teal);
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 1rem;
            height: 2.45rem;
            justify-content: center;
            line-height: 1;
            text-align: center;
            width: 2.45rem;
        }

        .sp-benefit strong {
            color: var(--sp-ink);
            display: block;
            font-size: .9rem;
            font-weight: 800;
        }

        .sp-benefit span {
            color: var(--sp-muted);
            display: grid;
            font-size: 1rem;
            line-height: 1.45;
            margin-top: .12rem;
        }

        .sp-benefit-text {
            color: var(--sp-muted);
            display: grid;
            font-size: 0.875rem !important;
            line-height: 1.45;
            margin-top: .12rem;
        }

        .sp-section {
            background: #ffffff;
            padding: clamp(3rem, 7vw, 5.5rem) 0;
        }

        .sp-section.alt {
            background: var(--sp-bg);
        }

        .sp-section-heading {
            margin: 0 auto clamp(2.2rem, 4vw, 3rem);
            max-width: 42rem;
            text-align: center;
        }

        .sp-section-kicker {
            color: var(--sp-teal);
            font-size: .7rem;
            font-weight: 900;
            letter-spacing: .12em;
            margin-bottom: .55rem;
            text-transform: uppercase;
        }

        .sp-section-heading h2 {
            color: var(--sp-ink);
            font-size: clamp(1.7rem, 3vw, 2.25rem);
            font-weight: 800;
            letter-spacing: -.035em;
            line-height: 1.1;
            margin: 0;
        }

        .sp-section-heading p {
            color: var(--sp-muted);
            font-size: .95rem;
            line-height: 1.65;
            margin: .85rem 0 0;
        }

        .sp-steps {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            text-align: center;
        }

        .sp-step-number {
            align-items: center;
            background: #ffffff;
            border: 1px solid var(--sp-border);
            border-radius: .85rem;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .05);
            color: var(--sp-teal);
            display: inline-flex;
            font-size: 1.1rem;
            font-weight: 900;
            height: 3.2rem;
            justify-content: center;
            margin-bottom: 1rem;
            width: 3.2rem;
        }

        .sp-step strong {
            color: var(--sp-ink);
            display: block;
            font-size: 1rem;
            font-weight: 800;
        }

        .sp-step p {
            color: var(--sp-muted);
            font-size: .84rem;
            line-height: 1.65;
            margin: .75rem auto 0;
            max-width: 18rem;
        }

        .sp-results {
            display: grid;
            gap: 1.25rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .sp-result-card {
            border-radius: 1.2rem;
            min-height: 24rem;
            overflow: hidden;
            position: relative;
        }

        .sp-result-card img,
        .sp-result-card video {
            display: block;
            height: 100%;
            inset: 0;
            object-fit: cover;
            position: absolute;
            width: 100%;
        }

        .sp-result-card.before {
            background:
                radial-gradient(circle at 20% 12%, rgba(255, 255, 255, .78), transparent 8rem),
                repeating-linear-gradient(90deg, rgba(255,255,255,.18) 0 2.4rem, transparent 2.4rem 4.9rem),
                linear-gradient(135deg, #c96f5b, #f1d4a8 52%, #048b97);
        }

        .sp-result-card.after {
            background:
                radial-gradient(circle at 52% 38%, rgba(255, 255, 255, .95), transparent 6.5rem),
                radial-gradient(circle at 52% 56%, rgba(255, 255, 255, .85), transparent 8rem),
                linear-gradient(135deg, #76ead9, #0fbc9f 58%, #099072);
        }

        .sp-result-label {
            background: rgba(255, 255, 255, .82);
            border-radius: 999px;
            color: #334155;
            font-size: .72rem;
            font-weight: 900;
            left: 1rem;
            padding: .4rem .7rem;
            position: absolute;
            top: 1rem;
        }

        .sp-cta-band {
            align-items: center;
            background:
                radial-gradient(circle at 16% 0%, rgba(255, 255, 255, .08), transparent 10rem),
                var(--sp-dark);
            border-radius: 1.35rem;
            color: #ffffff;
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            margin: clamp(2rem, 5vw, 3.5rem) 0 0;
            overflow: hidden;
            padding: clamp(1.25rem, 3vw, 2rem);
        }

        .sp-cta-band strong {
            display: block;
            font-size: clamp(1.3rem, 2.2vw, 1.8rem);
            font-weight: 900;
            letter-spacing: -.03em;
        }

        .sp-cta-band span {
            color: #9aa8bf;
            display: block;
            font-size: .9rem;
            line-height: 1.6;
            margin-top: .35rem;
        }

        @media (max-width: 980px) {
            .sp-hero {
                grid-template-columns: minmax(0, 1fr);
                min-height: auto;
            }

            .sp-title { max-width: 12ch; }
        }

        @media (max-width: 760px) {
            .sp-nav,
            .sp-cta-band {
                align-items: stretch;
                display: grid;
            }

            .sp-token,
            .sp-btn,
            .sp-cta-band .sp-btn {
                justify-content: center;
                width: 100%;
            }

            .sp-sticky-whatsapp {
                bottom: .8rem;
                left: .9rem;
                right: .9rem;
            }

            .sp-facts,
            .sp-benefits,
            .sp-steps,
            .sp-results {
                grid-template-columns: minmax(0, 1fr);
            }

            .sp-result-card { min-height: 17rem; }
            .sp-media { min-height: 14.5rem; }
        }

        @media (max-width: 440px) {
            .sp-shell { padding: 0 .9rem; }
            .sp-title { font-size: 2.45rem; }
            .sp-plan-card { padding: 1rem; }
            .sp-phase { height: 3.7rem; width: 3.7rem; }
            .sp-procedure { margin-right: 4rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            .sp-typewriter {
                animation: none;
                border-right: 0;
            }
        }
    </style>
</head>
<body>
    <header class="sp-nav">
        <div class="sp-shell" style="align-items:center;display:flex;justify-content:space-between;width:100%;gap:1rem;">
            <div class="sp-brand">
                <span class="sp-brand-mark">DC</span>
                <strong>VitalSmile</strong>
            </div>

            <div class="sp-token">ID: {{ $trackingToken }}</div>
        </div>
    </header>

    <main
        data-track-url="{{ $trackUrl }}"
        data-threshold="{{ $durationThreshold }}"
        data-ping="{{ $pingSeconds }}"
        data-attribution='@json($attribution)'
    >
        <section class="sp-hero-wrap">
            <div class="sp-shell">
                <div class="sp-hero">
                    <div>
                        <p class="sp-eyebrow">{{ $content['eyebrow'] ?? 'Plan dental personalizado' }}</p>
                        <h1 class="sp-title">
                            @if (filled($leadName))
                                Hola {{ $leadName }},
                                <span class="sp-typewriter" data-typewriter-text="tu plan dental esta listo">tu plan dental esta listo</span>
                            @else
                                {{ $hero['title_static'] }}
                                @if (filled($hero['title_typed']))
                                    <span class="sp-typewriter" data-typewriter-text="{{ $hero['title_typed'] }}">{{ $hero['title_typed'] }}</span>
                                @endif
                            @endif
                        </h1>
                        <p class="sp-subtitle">{{ $hero['subtitle'] }}</p>

                        <div class="sp-actions">
                            @if ($whatsappLink)
                                <a class="sp-btn sp-btn-whatsapp" href="{{ $whatsappLink }}" data-whatsapp-link>
                                    <svg viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                                        <path fill="currentColor" d="M16.04 3.2A12.73 12.73 0 0 0 3.3 15.92c0 2.24.59 4.42 1.7 6.34L3.2 28.8l6.72-1.76a12.72 12.72 0 0 0 6.12 1.56h.01A12.73 12.73 0 0 0 28.8 15.88 12.74 12.74 0 0 0 16.04 3.2Zm0 23.24h-.01c-1.87 0-3.7-.5-5.3-1.45l-.38-.22-3.99 1.04 1.06-3.88-.25-.4a10.49 10.49 0 0 1-1.61-5.6c0-5.78 4.7-10.48 10.49-10.48a10.48 10.48 0 0 1 10.5 10.44c0 5.79-4.71 10.5-10.5 10.5Zm5.75-7.85c-.31-.16-1.86-.92-2.15-1.02-.29-.1-.5-.16-.71.16-.21.31-.81 1.02-1 1.23-.18.21-.37.23-.68.08-.31-.16-1.32-.49-2.52-1.55-.93-.83-1.56-1.86-1.74-2.17-.18-.31-.02-.48.14-.64.14-.14.31-.37.47-.55.16-.18.21-.31.31-.52.1-.21.05-.39-.03-.55-.08-.16-.71-1.71-.97-2.34-.26-.61-.52-.53-.71-.54h-.6c-.21 0-.55.08-.84.39-.29.31-1.1 1.08-1.1 2.62 0 1.55 1.13 3.04 1.29 3.25.16.21 2.22 3.39 5.38 4.75.75.32 1.34.52 1.8.66.76.24 1.45.2 1.99.12.61-.09 1.86-.76 2.12-1.5.26-.73.26-1.36.18-1.5-.08-.13-.29-.21-.6-.37Z" />
                                    </svg>
                                    Confirmar por WhatsApp
                                </a>
                            @endif
                            <a class="sp-btn sp-btn-soft" href="#visita">Ver primera visita</a>
                        </div>
                    </div>

                    <aside class="sp-plan-card" aria-label="Resumen del plan dental">
                        <div class="sp-phase">FASE 1</div>
                        <div class="sp-card-label">Procedimiento</div>
                        <div class="sp-procedure">{{ $preview['procedure'] }}</div>

                        <div class="sp-facts">
                            <div class="sp-fact">
                                <span>Duracion est.</span>
                                <strong>{{ $preview['duration'] }}</strong>
                            </div>
                            <div class="sp-fact">
                                <span>Complejidad</span>
                                <strong>{{ $preview['complexity'] }}</strong>
                            </div>
                        </div>

                        <div class="sp-checks">
                            @foreach ($preview['steps'] as $step)
                                <div class="sp-check">{{ $step['label'] }}</div>
                            @endforeach
                        </div>

                        <div class="sp-media">
                            @if (filled($videoUrl))
                                @if ($isVideoFile)
                                    <video src="{{ $videoUrl }}" controls playsinline preload="metadata"></video>
                                @else
                                    <iframe src="{{ $videoUrl }}" title="Video informativo dental" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                @endif
                            @elseif (filled($hero['visual_image_url']))
                                <img src="{{ $hero['visual_image_url'] }}" alt="{{ $hero['visual_label'] }}" loading="lazy">
                            @else
                                <div class="sp-aligner" aria-hidden="true"></div>
                            @endif
                            <span class="sp-media-caption">{{ $hero['visual_label'] }}</span>
                            <div class="sp-video-progress" aria-hidden="true"><span data-video-progress></span></div>
                        </div>
                    </aside>
                </div>

                <div class="sp-benefits" aria-label="Beneficios de la valoracion">
                    <article class="sp-benefit">
                        <span class="sp-benefit-icon" aria-hidden="true">✨</span>
                        <div><strong>Tecnologia Laser</strong><span class="sp-benefit-text">Precision sin dolor.</span></div>
                    </article>
                    <article class="sp-benefit">
                        <span class="sp-benefit-icon" aria-hidden="true">🏆</span>
                        <div><strong>Especialistas</strong><span class="sp-benefit-text">Certificacion internacional.</span></div>
                    </article>
                    <article class="sp-benefit">
                        <span class="sp-benefit-icon" aria-hidden="true">💳</span>
                        <div><strong>Financiacion</strong><span class="sp-benefit-text">Hasta 24 cuotas sin interes.</span></div>
                    </article>
                </div>
            </div>
        </section>

        <section class="sp-section" id="visita">
            <div class="sp-shell">
                <div class="sp-section-heading">
                    <h2>¿Que pasara en tu primera visita?</h2>
                    <p>Queremos que te sientas comodo desde el primer segundo.</p>
                </div>

                <div class="sp-steps">
                    @foreach ($preview['steps'] as $index => $step)
                        <article class="sp-step">
                            <span class="sp-step-number">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            <strong>{{ $step['label'] }}</strong>
                            <p>{{ $step['text'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="sp-section alt">
            <div class="sp-shell">
                <div class="sp-section-heading">
                    <div class="sp-section-kicker">Resultados visuales</div>
                    <h2>Tu sonrisa, transformada.</h2>
                    <p>Placeholder visual para mostrar el tipo de cambio que buscamos explicar en tu valoracion. Luego puedes reemplazarlo por casos reales o video.</p>
                </div>

                <div class="sp-results">
                    <div class="sp-result-card before">
                        @if (filled($hero['before_video_url']))
                            <video src="{{ $hero['before_video_url'] }}" controls playsinline preload="metadata"></video>
                        @elseif (filled($hero['before_image_url']))
                            <img src="{{ $hero['before_image_url'] }}" alt="Antes de la valoracion" loading="lazy">
                        @endif
                        <span class="sp-result-label">Antes</span>
                    </div>
                    <div class="sp-result-card after">
                        @if (filled($hero['after_video_url']))
                            <video src="{{ $hero['after_video_url'] }}" controls playsinline preload="metadata"></video>
                        @elseif (filled($hero['after_image_url']))
                            <img src="{{ $hero['after_image_url'] }}" alt="Despues de la valoracion" loading="lazy">
                        @endif
                        <span class="sp-result-label">Despues</span>
                    </div>
                </div>

                <section class="sp-cta-band" aria-label="CTA final">
                    <div>
                        <strong>¿Listo para dar el primer paso?</strong>
                        <span>Agenda tu valoracion y conserva tu codigo {{ $trackingToken }} para mantener el contexto de tu solicitud.</span>
                    </div>
                    @if ($whatsappLink)
                        <a class="sp-btn sp-btn-whatsapp" href="{{ $whatsappLink }}" data-whatsapp-link>
                            <svg viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                                <path fill="currentColor" d="M16.04 3.2A12.73 12.73 0 0 0 3.3 15.92c0 2.24.59 4.42 1.7 6.34L3.2 28.8l6.72-1.76a12.72 12.72 0 0 0 6.12 1.56h.01A12.73 12.73 0 0 0 28.8 15.88 12.74 12.74 0 0 0 16.04 3.2Zm0 23.24h-.01c-1.87 0-3.7-.5-5.3-1.45l-.38-.22-3.99 1.04 1.06-3.88-.25-.4a10.49 10.49 0 0 1-1.61-5.6c0-5.78 4.7-10.48 10.49-10.48a10.48 10.48 0 0 1 10.5 10.44c0 5.79-4.71 10.5-10.5 10.5Zm5.75-7.85c-.31-.16-1.86-.92-2.15-1.02-.29-.1-.5-.16-.71.16-.21.31-.81 1.02-1 1.23-.18.21-.37.23-.68.08-.31-.16-1.32-.49-2.52-1.55-.93-.83-1.56-1.86-1.74-2.17-.18-.31-.02-.48.14-.64.14-.14.31-.37.47-.55.16-.18.21-.31.31-.52.1-.21.05-.39-.03-.55-.08-.16-.71-1.71-.97-2.34-.26-.61-.52-.53-.71-.54h-.6c-.21 0-.55.08-.84.39-.29.31-1.1 1.08-1.1 2.62 0 1.55 1.13 3.04 1.29 3.25.16.21 2.22 3.39 5.38 4.75.75.32 1.34.52 1.8.66.76.24 1.45.2 1.99.12.61-.09 1.86-.76 2.12-1.5.26-.73.26-1.36.18-1.5-.08-.13-.29-.21-.6-.37Z" />
                            </svg>
                            Hablar con un asesor
                        </a>
                    @endif
                </section>
            </div>
        </section>
    </main>

    @if ($whatsappLink)
        <a class="sp-btn sp-btn-whatsapp sp-sticky-whatsapp" href="{{ $whatsappLink }}" data-whatsapp-link>
            <svg viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                <path fill="currentColor" d="M16.04 3.2A12.73 12.73 0 0 0 3.3 15.92c0 2.24.59 4.42 1.7 6.34L3.2 28.8l6.72-1.76a12.72 12.72 0 0 0 6.12 1.56h.01A12.73 12.73 0 0 0 28.8 15.88 12.74 12.74 0 0 0 16.04 3.2Zm0 23.24h-.01c-1.87 0-3.7-.5-5.3-1.45l-.38-.22-3.99 1.04 1.06-3.88-.25-.4a10.49 10.49 0 0 1-1.61-5.6c0-5.78 4.7-10.48 10.49-10.48a10.48 10.48 0 0 1 10.5 10.44c0 5.79-4.71 10.5-10.5 10.5Zm5.75-7.85c-.31-.16-1.86-.92-2.15-1.02-.29-.1-.5-.16-.71.16-.21.31-.81 1.02-1 1.23-.18.21-.37.23-.68.08-.31-.16-1.32-.49-2.52-1.55-.93-.83-1.56-1.86-1.74-2.17-.18-.31-.02-.48.14-.64.14-.14.31-.37.47-.55.16-.18.21-.31.31-.52.1-.21.05-.39-.03-.55-.08-.16-.71-1.71-.97-2.34-.26-.61-.52-.53-.71-.54h-.6c-.21 0-.55.08-.84.39-.29.31-1.1 1.08-1.1 2.62 0 1.55 1.13 3.04 1.29 3.25.16.21 2.22 3.39 5.38 4.75.75.32 1.34.52 1.8.66.76.24 1.45.2 1.99.12.61-.09 1.86-.76 2.12-1.5.26-.73.26-1.36.18-1.5-.08-.13-.29-.21-.6-.37Z" />
            </svg>
            Continuar por WhatsApp
        </a>
    @endif

    <script>
        (() => {
            const typewriter = document.querySelector('[data-typewriter-text]');

            if (typewriter && ! window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                const text = typewriter.dataset.typewriterText || typewriter.textContent;
                let index = 0;

                typewriter.textContent = '';

                const typeNextCharacter = () => {
                    index += 1;
                    typewriter.textContent = text.slice(0, index);

                    if (index < text.length) {
                        setTimeout(typeNextCharacter, 70);

                        return;
                    }

                    typewriter.classList.add('is-complete');
                };

                setTimeout(typeNextCharacter, 350);
            }

            const root = document.querySelector('[data-track-url]');
            const trackUrl = root.dataset.trackUrl;
            const threshold = Number(root.dataset.threshold || 60);
            const pingSeconds = Number(root.dataset.ping || 15);
            const attribution = JSON.parse(root.dataset.attribution || '{}');
            const storageKey = `social-smart-link-session:${trackUrl}`;
            const visitKey = `social-smart-link-visited:${trackUrl}`;
            const thresholdKey = `social-smart-link-threshold:${trackUrl}`;
            const sessionId = sessionStorage.getItem(storageKey) || crypto.randomUUID();
            const startedAt = Date.now();

            sessionStorage.setItem(storageKey, sessionId);

            const send = (eventType, durationSeconds = null, metadata = {}) => {
                fetch(trackUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        event_type: eventType,
                        session_id: sessionId,
                        duration_seconds: durationSeconds,
                        metadata: {
                            ...attribution,
                            ...metadata,
                        },
                    }),
                    keepalive: true,
                }).catch(() => {});
            };

            const alreadyVisited = localStorage.getItem(visitKey) === '1';
            send(alreadyVisited ? 'revisit' : 'view', 0, { source: 'landing_load' });
            localStorage.setItem(visitKey, '1');

            document.querySelectorAll('[data-whatsapp-link]').forEach((link) => {
                link.addEventListener('click', () => {
                    const duration = Math.round((Date.now() - startedAt) / 1000);
                    send('whatsapp_click', duration, { source: 'whatsapp_cta' });
                });
            });

            document.querySelectorAll('.sp-btn:not([data-whatsapp-link])').forEach((button) => {
                button.addEventListener('click', () => {
                    const duration = Math.round((Date.now() - startedAt) / 1000);
                    send('button_click', duration, { label: button.textContent.trim(), source: 'smart_link_button' });
                });
            });

            document.querySelectorAll('video').forEach((video) => {
                const reached = new Set();
                let lastVideoSecondsSent = 0;
                const progressBar = video.closest('.sp-media, .sp-result-card')?.querySelector('[data-video-progress]');

                const mark = (eventType, progress = null) => {
                    if (reached.has(eventType)) {
                        return;
                    }

                    reached.add(eventType);
                    send(eventType, Math.round((Date.now() - startedAt) / 1000), {
                        source: 'video',
                        progress,
                        duration: Number.isFinite(video.duration) ? Math.round(video.duration) : null,
                    });
                };

                const markPlaySeconds = () => {
                    const seconds = Math.round(video.currentTime || 0);

                    if (seconds < 10 || seconds - lastVideoSecondsSent < 15) {
                        return;
                    }

                    lastVideoSecondsSent = seconds;
                    send('video_play_seconds', seconds, {
                        source: 'video',
                        duration: Number.isFinite(video.duration) ? Math.round(video.duration) : null,
                    });
                };

                video.addEventListener('play', () => mark('video_start', 0));
                video.addEventListener('timeupdate', () => {
                    if (! Number.isFinite(video.duration) || video.duration <= 0) {
                        return;
                    }

                    const progress = Math.min(100, Math.round((video.currentTime / video.duration) * 100));

                    if (progressBar) {
                        progressBar.style.width = `${progress}%`;
                    }

                    if (progress >= 25) mark('video_25', progress);
                    if (progress >= 50) mark('video_50', progress);
                    if (progress >= 75) mark('video_75', progress);
                    markPlaySeconds();
                });
                video.addEventListener('ended', () => {
                    if (progressBar) {
                        progressBar.style.width = '100%';
                    }

                    mark('video_complete', 100);
                    lastVideoSecondsSent = 0;
                    markPlaySeconds();
                });
            });

            document.querySelectorAll('iframe').forEach((iframe) => {
                iframe.addEventListener('pointerenter', () => {
                    send('video_start', Math.round((Date.now() - startedAt) / 1000), { source: 'iframe_preview' });
                }, { once: true });
            });

            setInterval(() => {
                const duration = Math.round((Date.now() - startedAt) / 1000);
                send('engagement_ping', duration, { visibility: document.visibilityState });

                if (duration >= threshold && localStorage.getItem(thresholdKey) !== '1') {
                    localStorage.setItem(thresholdKey, '1');
                    send('duration_threshold', duration, { threshold_seconds: threshold });
                }
            }, Math.max(5, pingSeconds) * 1000);
        })();
    </script>
</body>
</html>
