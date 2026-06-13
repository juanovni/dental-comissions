<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ $csrfToken }}">
    <title>{{ $content['eyebrow'] ?? 'Valoracion dental' }} | CRM Dental</title>
    <style>
        :root {
            --ink: #17201d;
            --muted: #66736f;
            --cream: #f7f1e7;
            --paper: #fffaf1;
            --mint: #9fd8c2;
            --deep: #123c35;
            --gold: #c89149;
            --line: rgba(18, 60, 53, .14);
        }

        * { box-sizing: border-box; }

        body {
            background:
                radial-gradient(circle at 12% 12%, rgba(159, 216, 194, .5), transparent 26rem),
                radial-gradient(circle at 88% 10%, rgba(200, 145, 73, .24), transparent 24rem),
                linear-gradient(135deg, #fbf7ef, #eef6ef 54%, #f7f1e7);
            color: var(--ink);
            font-family: "Iowan Old Style", "Palatino Linotype", Georgia, serif;
            margin: 0;
            min-height: 100vh;
        }

        .shell {
            margin: 0 auto;
            max-width: 1180px;
            padding: clamp(1rem, 3vw, 2.5rem);
        }

        .nav {
            align-items: center;
            display: flex;
            justify-content: space-between;
            margin-bottom: clamp(1.5rem, 5vw, 4rem);
        }

        .brand {
            align-items: center;
            display: inline-flex;
            gap: .7rem;
            font-family: ui-sans-serif, system-ui, sans-serif;
            font-size: .78rem;
            font-weight: 850;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .brand-mark {
            background: var(--deep);
            border-radius: 999px;
            box-shadow: 0 14px 34px rgba(18, 60, 53, .24);
            height: 2.25rem;
            position: relative;
            width: 2.25rem;
        }

        .brand-mark::after {
            background: var(--mint);
            border-radius: 999px;
            content: '';
            height: .62rem;
            inset: .48rem auto auto .82rem;
            position: absolute;
            width: .62rem;
        }

        .token {
            background: rgba(255, 250, 241, .72);
            border: 1px solid var(--line);
            border-radius: 999px;
            color: var(--deep);
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: .78rem;
            font-weight: 800;
            padding: .55rem .78rem;
        }

        .hero {
            display: grid;
            gap: clamp(1.5rem, 5vw, 4rem);
            grid-template-columns: minmax(0, 1.04fr) minmax(18rem, .96fr);
            align-items: center;
        }

        .eyebrow {
            color: var(--gold);
            font-family: ui-sans-serif, system-ui, sans-serif;
            font-size: .78rem;
            font-weight: 900;
            letter-spacing: .16em;
            margin: 0 0 1rem;
            text-transform: uppercase;
        }

        h1 {
            font-size: clamp(2.55rem, 7vw, 6.7rem);
            letter-spacing: -.07em;
            line-height: .88;
            margin: 0;
            max-width: 10ch;
        }

        .subtitle {
            color: var(--muted);
            font-family: ui-sans-serif, system-ui, sans-serif;
            font-size: clamp(1rem, 1.5vw, 1.18rem);
            line-height: 1.7;
            margin: 1.35rem 0 0;
            max-width: 42rem;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            margin-top: 1.6rem;
        }

        .cta {
            align-items: center;
            background: var(--deep);
            border: 1px solid var(--deep);
            border-radius: 999px;
            box-shadow: 0 18px 40px rgba(18, 60, 53, .24);
            color: #fff;
            display: inline-flex;
            font-family: ui-sans-serif, system-ui, sans-serif;
            font-size: .92rem;
            font-weight: 850;
            gap: .55rem;
            padding: .9rem 1.15rem;
            text-decoration: none;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .cta:hover { box-shadow: 0 22px 48px rgba(18, 60, 53, .3); transform: translateY(-2px); }

        .ghost {
            background: rgba(255, 250, 241, .7);
            color: var(--deep);
        }

        .visual-card {
            aspect-ratio: 4 / 5;
            background:
                linear-gradient(145deg, rgba(255, 255, 255, .62), rgba(255, 250, 241, .86)),
                repeating-linear-gradient(45deg, rgba(18, 60, 53, .055) 0 1px, transparent 1px 14px);
            border: 1px solid rgba(18, 60, 53, .16);
            border-radius: 2.2rem;
            box-shadow: 0 30px 80px rgba(18, 60, 53, .18);
            display: grid;
            overflow: hidden;
            padding: 1.1rem;
            position: relative;
        }

        .visual-card::before {
            background: radial-gradient(circle, rgba(159, 216, 194, .75), transparent 66%);
            content: '';
            height: 18rem;
            position: absolute;
            right: -7rem;
            top: -6rem;
            width: 18rem;
        }

        .video-box {
            align-items: center;
            background: linear-gradient(160deg, #183f38, #97cbb8);
            border-radius: 1.55rem;
            color: white;
            display: grid;
            min-height: 16rem;
            overflow: hidden;
            place-items: center;
            position: relative;
            text-align: center;
        }

        .video-box iframe,
        .video-box video {
            border: 0;
            height: 100%;
            inset: 0;
            position: absolute;
            width: 100%;
        }

        .play-orb {
            align-items: center;
            backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, .18);
            border: 1px solid rgba(255, 255, 255, .36);
            border-radius: 999px;
            display: flex;
            font-family: ui-sans-serif, system-ui, sans-serif;
            font-size: .78rem;
            font-weight: 900;
            height: 8.5rem;
            justify-content: center;
            letter-spacing: .1em;
            text-transform: uppercase;
            width: 8.5rem;
        }

        .visual-label {
            align-self: end;
            color: var(--deep);
            display: grid;
            font-family: ui-sans-serif, system-ui, sans-serif;
            gap: .45rem;
            margin-top: 1rem;
        }

        .visual-label strong { font-size: 1.1rem; }
        .visual-label span { color: var(--muted); font-size: .86rem; line-height: 1.55; }

        .proof {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: clamp(2rem, 5vw, 4rem);
        }

        .proof-card {
            background: rgba(255, 250, 241, .7);
            border: 1px solid var(--line);
            border-radius: 1.2rem;
            color: var(--deep);
            font-family: ui-sans-serif, system-ui, sans-serif;
            padding: 1rem;
        }

        .proof-card strong { display: block; font-size: .95rem; margin-bottom: .4rem; }
        .proof-card span { color: var(--muted); font-size: .84rem; line-height: 1.55; }

        @media (max-width: 840px) {
            .hero { grid-template-columns: 1fr; }
            .visual-card { aspect-ratio: auto; }
            .proof { grid-template-columns: 1fr; }
            h1 { max-width: none; }
        }
    </style>
</head>
<body>
    <main class="shell" data-track-url="{{ $trackUrl }}" data-threshold="{{ $durationThreshold }}" data-ping="{{ $pingSeconds }}">
        <nav class="nav" aria-label="Identidad de landing">
            <div class="brand"><span class="brand-mark"></span><span>Clinica Dental</span></div>
            <div class="token">{{ $trackingToken }}</div>
        </nav>

        <section class="hero">
            <div>
                <p class="eyebrow">{{ $content['eyebrow'] ?? 'Valoracion dental personalizada' }}</p>
                <h1>{{ $content['title'] ?? 'Tu sonrisa merece un plan claro.' }}</h1>
                <p class="subtitle">{{ $content['subtitle'] ?? 'Continua por WhatsApp para recibir orientacion personalizada.' }}</p>

                <div class="actions">
                    @if ($whatsappLink)
                        <a class="cta" href="{{ $whatsappLink }}">Continuar por WhatsApp</a>
                    @endif
                    <a class="cta ghost" href="#resultados">Ver resultados visuales</a>
                </div>
            </div>

            <aside class="visual-card" id="resultados">
                <div class="video-box">
                    @if (filled($content['video_url'] ?? null))
                        <iframe src="{{ $content['video_url'] }}" title="Video informativo dental" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    @else
                        <div class="play-orb">Preview</div>
                    @endif
                </div>
                <div class="visual-label">
                    <strong>{{ $content['visual_label'] ?? 'Diagnostico integral' }}</strong>
                    <span>Esta pagina esta conectada a tu solicitud original. El equipo puede ver tu interes y darte seguimiento con mejor contexto.</span>
                </div>
            </aside>
        </section>

        <section class="proof" aria-label="Beneficios de seguimiento">
            <div class="proof-card"><strong>Contexto claro</strong><span>Tu codigo mantiene unida la conversacion de redes y WhatsApp.</span></div>
            <div class="proof-card"><strong>Atencion guiada</strong><span>La secretaria sabe que tema dental te interesa antes de responder.</span></div>
            <div class="proof-card"><strong>Sin presion</strong><span>Revisa la informacion y continua cuando estes listo.</span></div>
        </section>
    </main>

    <script>
        (() => {
            const root = document.querySelector('[data-track-url]');
            const trackUrl = root.dataset.trackUrl;
            const threshold = Number(root.dataset.threshold || 60);
            const pingSeconds = Number(root.dataset.ping || 15);
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
                        metadata,
                    }),
                    keepalive: true,
                }).catch(() => {});
            };

            const alreadyVisited = localStorage.getItem(visitKey) === '1';
            send(alreadyVisited ? 'revisit' : 'view', 0, { source: 'landing_load' });
            localStorage.setItem(visitKey, '1');

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
