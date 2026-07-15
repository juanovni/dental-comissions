<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ $csrfToken }}">
    <meta name="description" content="{{ $content['subtitle'] ?? 'Valoración dental personalizada, clara y sin presión.' }}">
    <meta property="og:title" content="{{ $content['title'] ?? 'Tu nueva sonrisa, planificada a medida.' }}">
    <meta property="og:description" content="{{ $preview['text'] }}">
    <title>{{ $content['eyebrow'] ?? 'Valoración dental' }} | Clínica Dental</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @php
        $videoUrl = (string) ($content['video_url'] ?? '');
        $isVideoFile = filled($videoUrl) && \Illuminate\Support\Str::of($videoUrl)->lower()->endsWith(['.mp4', '.webm', '.ogg']);
        $preparedDate = optional($comment->created_at)->locale('es')->translatedFormat('d M') ?: now()->locale('es')->translatedFormat('d M');
        $hasBeforeAfterImages = filled($hero['before_image_url']) && filled($hero['after_image_url']) && blank($hero['before_video_url']) && blank($hero['after_video_url']);
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
            max-width: 1300px;
            padding: 0 clamp(1rem, 3vw, 2rem);
        }

        .sp-nav {
            align-items: center;
            background: rgba(255, 255, 255, .9);
            backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--sp-border);
            display: flex;
            justify-content: space-between;
            min-height: 4.25rem;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 50;
        }

        main { padding-top: 4.25rem; }

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
            background-image: linear-gradient(rgba(8,17,38,.05) 1px, transparent 1px), linear-gradient(90deg, rgba(8,17,38,.05) 1px, transparent 1px);
            background-size: 48px 48px;
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

        .sp-subtitle {
            color: var(--sp-muted);
            font-size: 1rem;
            line-height: 1.7;
            margin: 1.25rem 0 0;
            max-width: 38rem;
        }

        .sp-subtitle strong { color: #000000; }

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
            align-items: center;
            animation: sp-pulse-ring 2.4s ease-out infinite;
            background: #25d366;
            border-radius: 999px;
            bottom: 1.5rem;
            box-shadow: 0 20px 44px rgba(37, 211, 102, .34);
            color: #ffffff;
            display: grid;
            height: 3.5rem;
            justify-content: center;
            position: fixed;
            right: 1.5rem;
            text-decoration: none;
            transition: transform .18s ease, box-shadow .18s ease;
            width: 3.5rem;
            z-index: 50;
        }

        .sp-sticky-whatsapp:hover {
            box-shadow: 0 24px 52px rgba(37, 211, 102, .42);
            transform: scale(1.1);
        }

        .sp-sticky-whatsapp svg {
            height: 1.55rem;
            width: 1.55rem;
        }

        @keyframes sp-pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(37, 211, 102, .42), 0 20px 44px rgba(37, 211, 102, .34); }
            72% { box-shadow: 0 0 0 18px rgba(37, 211, 102, 0), 0 20px 44px rgba(37, 211, 102, .34); }
            100% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0), 0 20px 44px rgba(37, 211, 102, .34); }
        }

        .sp-plan-card {
            background: var(--sp-surface);
            border: 1px solid var(--sp-border);
            border-radius: 1.45rem;
            box-shadow: var(--sp-shadow);
            padding: 1.65rem;
            position: relative;
        }

        .animate-float { animation: 6s ease-in-out infinite sp-float; }

        @keyframes sp-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }

        .sp-phase {
            align-items: center;
            background: #ffffff;
            border: 4px solid #e0f5f2;
            border-radius: 999px;
            color: var(--sp-teal-dark);
            display: flex;
            font-size: .68rem;
            font-weight: 900;
            height: 3.8rem;
            justify-content: center;
            position: absolute;
            right: 1.35rem;
            top: 1.35rem;
            width: 3.8rem;
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
            margin: .28rem 5rem 2rem 0;
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

        .sp-check-svg {
            color: var(--sp-teal);
            flex: 0 0 auto;
            height: 1rem;
            width: 1rem;
        }

        .sp-media {
            background:
                linear-gradient(180deg, rgba(207, 243, 252, .88), rgba(255, 255, 255, .4)),
                #eef8fb;
            border: 1px solid #cdebf3;
            border-radius: 1rem;
            min-height: 14.5rem;
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



        .sp-benefits {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            padding-bottom: clamp(2rem, 5vw, 4rem);
        }

        .sp-benefit {
            align-items: center;
            background:
                linear-gradient(135deg, rgba(255, 255, 255, .98), rgba(255, 255, 255, .9)),
                radial-gradient(circle at 0% 0%, rgba(0, 155, 143, .09), transparent 14rem);
            border: 1px solid rgba(230, 237, 245, .95);
            border-radius: 1.45rem;
            box-shadow: 0 20px 44px rgba(15, 23, 42, .06);
            display: flex;
            gap: 1.25rem;
            min-height: 8.9rem;
            padding: 1.45rem 1.6rem;
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }

        .sp-benefit.is-featured {
            border-color: rgba(0, 155, 143, .14);
        }

        .sp-benefit:hover {
            border-color: rgba(0, 155, 143, .32);
            box-shadow: 0 24px 54px rgba(15, 23, 42, .1);
            transform: translateY(-4px);
        }

        .sp-benefit:hover .sp-benefit-icon {
            background: var(--sp-teal);
            color: #ffffff;
            transform: rotate(-4deg) scale(1.06);
        }

        .sp-benefit-icon {
            align-items: center;
            background: linear-gradient(135deg, var(--sp-teal), #21b5a4);
            border-radius: 999px;
            box-shadow: 0 16px 30px rgba(0, 155, 143, .22);
            color: #ffffff;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 1rem;
            height: 3.85rem;
            justify-content: center;
            line-height: 1;
            text-align: center;
            width: 3.85rem;
            transition: box-shadow .18s ease, transform .18s ease;
        }

        .sp-benefit-icon svg { height: 1.45rem; width: 1.45rem; }

        .sp-benefit strong {
            color: var(--sp-ink);
            display: block;
            font-size: 1.18rem;
            font-weight: 600;
            letter-spacing: -.015em;
        }

        .sp-benefit span {
            color: #ffffff;
            display: grid;
            font-size: 1rem;
            line-height: 1.45;
            margin-top: .12rem;
        }

        .sp-benefit .sp-benefit-text {
            color: #374151;
            display: grid;
            font-size: 0.875rem !important;
            line-height: 1.45;
            margin-top: .12rem;
        }

        .sp-benefit-badge {
            align-items: center;
            color: var(--sp-teal-dark);
            display: inline-flex;
            font-size: .6rem;
            font-weight: 600;
            gap: .28rem;
            letter-spacing: .1em;
            margin-top: .7rem;
            text-transform: uppercase;
        }

        .sp-benefit-badge svg {
            height: .7rem;
            width: .7rem;
        }

        .text-primary { color: var(--sp-teal); }

        .sp-section {
            background: #ffffff;
            padding: clamp(3rem, 7vw, 5.5rem) 0;
        }

        .sp-section.is-reveal-ready {
            filter: blur(8px);
            opacity: 0;
            transform: translateY(2.25rem);
            transition: opacity .7s ease, transform .7s cubic-bezier(.2, .8, .2, 1), filter .7s ease;
        }

        .sp-section.is-visible {
            filter: blur(0);
            opacity: 1;
            transform: translateY(0);
        }

        @media (prefers-reduced-motion: reduce) {
            .sp-section.is-reveal-ready {
                filter: none;
                opacity: 1;
                transform: none;
                transition: none;
            }
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

        .sp-chip {
            align-items: center;
            background: var(--sp-teal-soft);
            border-radius: 999px;
            color: var(--sp-teal);
            display: inline-flex;
            font-size: .82rem;
            font-weight: 700;
            gap: .4rem;
            margin-bottom: .85rem;
            padding: .3rem .85rem;
        }

        .sp-chip svg {
            height: .85rem;
            width: .85rem;
        }

        .sp-section-heading h2 {
            color: var(--sp-ink);
            font-size: clamp(2rem, 4vw, 3rem);
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

        .sp-metrics {
            background: linear-gradient(135deg, var(--sp-teal), #15bfa8);
            border-radius: 1.35rem;
            box-shadow: 0 24px 48px rgba(0, 155, 143, .18);
            color: #ffffff;
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin: clamp(1.4rem, 4vw, 2.2rem) 0 clamp(2.2rem, 5vw, 3.6rem);
            padding: clamp(1.4rem, 4vw, 2.1rem);
            text-align: center;
        }

        .sp-metric strong {
            display: block;
            font-size: clamp(1.75rem, 4vw, 2.4rem);
            font-weight: 900;
            letter-spacing: -.04em;
            line-height: 1;
        }

        .sp-metric span {
            color: rgba(255, 255, 255, .82);
            display: block;
            font-size: .76rem;
            font-weight: 700;
            margin-top: .38rem;
        }

        .sp-steps {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            text-align: center;
        }

        .sp-step {
            position: relative;
        }

        .sp-step:not(:last-child)::after {
            background: linear-gradient(90deg, rgba(0, 155, 143, .18), rgba(0, 155, 143, .28), rgba(0, 155, 143, .18));
            content: '';
            height: 1px;
            left: calc(50% + 4rem);
            position: absolute;
            top: 4rem;
            width: calc(100% + 1rem - 8rem);
            z-index: 0;
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

        .sp-step-visual {
            display: grid;
            height: 8rem;
            margin: 0 auto 1.3rem;
            place-items: center;
            position: relative;
            width: 8rem;
        }

        .sp-step-visual::before {
            background: var(--sp-teal-soft);
            border-radius: 999px;
            content: '';
            inset: 0;
            position: absolute;
        }

        .sp-step-icon {
            align-items: center;
            background: var(--sp-surface);
            border: 1px solid rgba(0, 155, 143, .2);
            border-radius: 999px;
            box-shadow: 0 16px 34px rgba(15, 23, 42, .08);
            color: var(--sp-teal);
            display: inline-flex;
            height: 5.8rem;
            justify-content: center;
            position: relative;
            width: 5.8rem;
            z-index: 1;
        }

        .sp-step-icon svg { height: 1.8rem; width: 1.8rem; }

        .sp-step-bubble {
            align-items: center;
            background: var(--sp-ink);
            border-radius: 999px;
            color: #ffffff;
            display: inline-flex;
            font-size: .62rem;
            font-weight: 900;
            height: 1.65rem;
            justify-content: center;
            position: absolute;
            right: .38rem;
            top: .38rem;
            width: 1.65rem;
            z-index: 2;
        }

        .sp-step-number { display: none; }

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
            min-height: 32rem;
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
            background: var(--sp-teal);
            border-radius: 999px;
            color: #ffffff;
            font-size: .72rem;
            font-weight: 900;
            left: 1rem;
            padding: .4rem .7rem;
            position: absolute;
            top: 1rem;
        }

        .sp-compare {
            border-radius: 1.2rem;
            min-height: 28rem;
            overflow: hidden;
            position: relative;
        }

        .sp-compare img {
            display: block;
            height: 100%;
            inset: 0;
            object-fit: cover;
            position: absolute;
            width: 100%;
        }

        .sp-compare-after {
            clip-path: inset(0 0 0 var(--compare-x, 50%));
        }

        .sp-compare-handle {
            background: rgba(255, 255, 255, .92);
            border-radius: 999px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .18);
            color: var(--sp-teal);
            display: grid;
            font-size: .9rem;
            font-weight: 900;
            height: 2.5rem;
            left: var(--compare-x, 50%);
            place-items: center;
            position: absolute;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 2.5rem;
            z-index: 2;
        }

        .sp-compare-handle::before {
            background: rgba(255, 255, 255, .82);
            content: '';
            height: 28rem;
            left: 50%;
            position: absolute;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 2px;
            z-index: -1;
        }

        .sp-doctor {
            align-items: center;
            display: grid;
            gap: clamp(1.5rem, 5vw, 4rem);
            grid-template-columns: .85fr 1fr;
            margin-top: clamp(3rem, 7vw, 5rem);
        }

        .sp-doctor-photo {
            background: linear-gradient(135deg, #dffaf5, #ffffff);
            border-radius: 1.35rem;
            box-shadow: var(--sp-shadow);
            min-height: 24rem;
            overflow: hidden;
            position: relative;
        }

        .sp-doctor-photo::before {
            background: radial-gradient(circle at 50% 32%, #ffffff, rgba(255, 255, 255, 0) 11rem), linear-gradient(135deg, #e8fff9, #8ddfd7);
            content: '';
            inset: 0;
            position: absolute;
        }

        .sp-doctor-photo::after {
            color: rgba(8, 17, 38, .22);
            content: 'Especialista';
            font-size: 3.4rem;
            font-weight: 900;
            left: 50%;
            letter-spacing: -.05em;
            position: absolute;
            top: 50%;
            transform: translate(-50%, -50%) rotate(-8deg);
        }

        .sp-doctor-list {
            color: var(--sp-muted);
            display: grid;
            gap: .6rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 1.2rem;
        }

        .sp-doctor-list span::before {
            color: var(--sp-teal);
            content: '✓ ';
            font-weight: 900;
        }

        .sp-testimonials {
            margin: 0 auto;
            max-width: 46rem;
            overflow: hidden;
        }

        .sp-testimonial-track {
            display: flex;
            transition: transform .5s ease;
        }

        .sp-testimonial {
            background: #ffffff;
            border: 1px solid var(--sp-border);
            border-radius: 1.2rem;
            box-shadow: 0 18px 46px rgba(15, 23, 42, .06);
            flex: 0 0 100%;
            min-height: 13rem;
            padding: clamp(1.25rem, 3vw, 2rem);
            position: relative;
        }

        .sp-stars { color: var(--sp-teal); font-weight: 900; letter-spacing: .08em; }
        .sp-testimonial blockquote { font-size: 1.05rem; font-weight: 700; line-height: 1.55; margin: .7rem 0 1.2rem; }
        .sp-testimonial cite { color: var(--sp-muted); font-style: normal; font-weight: 700; }

        .sp-carousel-dots {
            display: flex;
            gap: .4rem;
            justify-content: center;
            margin-top: 1rem;
        }

        .sp-carousel-dots button {
            background: #c8d8df;
            border: 0;
            border-radius: 999px;
            height: .35rem;
            padding: 0;
            width: .35rem;
        }

        .sp-carousel-dots button.is-active { background: var(--sp-teal); width: 1.4rem; }

        .sp-faq {
            display: grid;
            gap: .75rem;
            margin: 0 auto;
            max-width: 48rem;
        }

        .sp-faq-item {
            background: #ffffff;
            border: 1px solid var(--sp-border);
            border-radius: .9rem;
            overflow: hidden;
        }

        .sp-faq-button {
            align-items: center;
            background: transparent;
            border: 0;
            color: var(--sp-ink);
            cursor: pointer;
            display: flex;
            font: inherit;
            font-weight: 800;
            justify-content: space-between;
            padding: 1rem 1.1rem;
            text-align: left;
            width: 100%;
        }

        .sp-faq-button span:last-child { color: var(--sp-teal); font-size: 1.2rem; }

        .sp-faq-panel {
            color: var(--sp-muted);
            display: none;
            line-height: 1.65;
            padding: 0 1.1rem 1rem;
        }

        .sp-faq-item.is-open .sp-faq-panel { display: block; }

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

            .sp-facts,
            .sp-benefits,
            .sp-steps,
            .sp-results,
            .sp-metrics,
            .sp-doctor,
            .sp-doctor-list {
                grid-template-columns: minmax(0, 1fr);
            }

            .sp-result-card { min-height: 20rem; }
            .sp-media { min-height: 14.5rem; }

            .sp-step:not(:last-child)::after { display: none; }
        }

        @media (max-width: 440px) {
            .sp-shell { padding: 0 .9rem; }
            .sp-title { font-size: 2.45rem; }
            .sp-plan-card { padding: 1rem; }
            .sp-phase { height: 3.7rem; width: 3.7rem; }
            .sp-procedure { margin-right: 4rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            .animate-float,
            .sp-sticky-whatsapp {
                animation: none;
            }
        }
    </style>
</head>
<body>
    <header class="sp-nav">
        <div class="sp-shell" style="align-items:center;display:flex;justify-content:space-between;width:100%;gap:1rem;">
            <div class="sp-brand">
                <span class="sp-brand-mark">DC</span>
                <strong>OdonCRM</strong>
            </div>

            <div class="sp-token">Plan activo {{ $trackingToken }}</div>
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
                        <p class="sp-eyebrow">Plan personalizado · Preparado el {{ $preparedDate }}</p>
                        <h1 class="sp-title">
                            {{ $hero['title_static'] }}
                            @if (filled($hero['title_typed']))
                                <span>{{ $hero['title_typed'] }}</span>
                            @endif
                        </h1>
                        <p class="sp-subtitle">{!! $hero['subtitle'] !!}</p>

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

                    <aside class="sp-plan-card animate-float" aria-label="Resumen del plan dental">
                        <div class="sp-phase">FASE 1</div>
                        <div class="sp-card-label">Procedimiento</div>
                        <div class="sp-procedure">{{ $preview['procedure'] }}</div>

                        <div class="sp-facts">
                            <div class="sp-fact">
                                <span>Duración est.</span>
                                <strong>{{ $preview['duration'] }}</strong>
                            </div>
                            <div class="sp-fact">
                                <span>Complejidad</span>
                                <strong>{{ $preview['complexity'] }}</strong>
                            </div>
                        </div>

                        <div class="sp-checks">
                            @foreach ($preview['steps'] as $step)
                                <div class="sp-check">
                                    <svg class="sp-check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="m9 12 2 2 4-4"></path></svg>
                                    {{ $step['label'] }}
                                </div>
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
                        </div>
                    </aside>
                </div>

                <div class="sp-benefits" aria-label="Beneficios de la valoración">
                    <article class="sp-benefit is-featured">
                        <span class="sp-benefit-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"></path></svg>
                        </span>
                        <div>
                            <strong>Tecnología Láser</strong>
                            <span class="sp-benefit-text">Precisión sin dolor</span>
                            <div class="sp-benefit-badge">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path><path d="m9 12 2 2 4-4"></path></svg>
                                Certificado FDA
                            </div>
                        </div>
                    </article>
                    <article class="sp-benefit">
                        <span class="sp-benefit-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"></path><circle cx="12" cy="8" r="6"></circle></svg>
                        </span>
                        <div><strong>Especialistas</strong><span class="sp-benefit-text">Certificación internacional.</span><div class="sp-benefit-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path><path d="m9 12 2 2 4-4"></path></svg>15+ años</div></div>
                    </article>
                    <article class="sp-benefit">
                        <span class="sp-benefit-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2" /><path d="M2 10h20" /></svg>
                        </span>
                        <div><strong>Financiación</strong><span class="sp-benefit-text">Hasta 24 cuotas sin interés.</span><div class="sp-benefit-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path><path d="m9 12 2 2 4-4"></path></svg>Aprobación en 5 min</div></div>
                    </article>
                </div>

                <div class="sp-metrics" aria-label="Métricas de la clínica">
                    <div class="sp-metric"><strong>2.547+</strong><span>Pacientes felices</span></div>
                    <div class="sp-metric"><strong>15 años</strong><span>De experiencia</span></div>
                    <div class="sp-metric"><strong>98%</strong><span>Satisfacción</span></div>
                    <div class="sp-metric"><strong>4.9 ★</strong><span>Rating Google</span></div>
                </div>
            </div>
        </section>

        <section class="sp-section" id="visita">
            <div class="sp-shell">
                <div class="sp-section-heading">
                    <div class="sp-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect width="18" height="18" x="3" y="4" rx="2"></rect><path d="M3 10h18"></path></svg> Tu primera visita</div>
                    <h2>¿Qué pasará en tu <span style="color:var(--sp-teal)">primera visita?</span></h2>
                    <p>Queremos que te sientas cómodo desde el primer segundo.</p>
                </div>

                <div class="sp-steps">
                    @foreach ($preview['steps'] as $index => $step)
                        <article class="sp-step">
                            <div class="sp-step-visual" aria-hidden="true">
                                <span class="sp-step-icon">
                                    @if ($index === 0)
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3Z" /><circle cx="12" cy="13" r="3" /></svg>
                                    @elseif ($index === 1)
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2"></path><path d="M17 3h2a2 2 0 0 1 2 2v2"></path><path d="M21 17v2a2 2 0 0 1-2 2h-2"></path><path d="M7 21H5a2 2 0 0 1-2-2v-2"></path></svg>
                                    @else
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"></rect><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><path d="M12 11h4"></path><path d="M12 16h4"></path><path d="M8 11h.01"></path><path d="M8 16h.01"></path></svg>
                                    @endif
                                </span>
                                <span class="sp-step-bubble">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            </div>
                            <strong>{{ $step['label'] }}</strong>
                            <p>{{ $step['text'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="sp-section">
            <div class="sp-shell">
                <div class="sp-section-heading">
                    <div class="sp-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect width="18" height="18" x="3" y="4" rx="2"></rect><path d="M3 10h18"></path></svg> Resultados visuales</div>
                    <h2>Tu sonrisa, <span style="color:var(--sp-teal)">transformada.</span></h2>
                    <p>Mira el antes y el después. Un cambio real que dura toda la vida.</p>
                </div>

                @if ($hasBeforeAfterImages)
                    <div class="sp-compare" data-before-after-compare>
                        <img src="{{ $hero['before_image_url'] }}" alt="Antes de la valoración" loading="lazy">
                        <img class="sp-compare-after" src="{{ $hero['after_image_url'] }}" alt="Después de la valoración" loading="lazy">
                        <span class="sp-result-label">Antes / Después</span>
                        <span class="sp-compare-handle" aria-hidden="true">&lt;&gt;</span>
                    </div>
                @else
                    <div class="sp-results">
                        <div class="sp-result-card before">
                            @if (filled($hero['before_video_url']))
                                <video src="{{ $hero['before_video_url'] }}" controls playsinline preload="metadata"></video>
                            @elseif (filled($hero['before_image_url']))
                                <img src="{{ $hero['before_image_url'] }}" alt="Antes de la valoración" loading="lazy">
                            @endif
                            <span class="sp-result-label">Antes</span>
                        </div>
                        <div class="sp-result-card after">
                            @if (filled($hero['after_video_url']))
                                <video src="{{ $hero['after_video_url'] }}" controls playsinline preload="metadata"></video>
                            @elseif (filled($hero['after_image_url']))
                                <img src="{{ $hero['after_image_url'] }}" alt="Después de la valoración" loading="lazy">
                            @endif
                            <span class="sp-result-label">Después</span>
                        </div>
                    </div>
                @endif

            </div>
        </section>

        <section class="sp-section">
            <div class="sp-shell">
                <div class="sp-section-heading">
                    <div class="sp-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path></svg> Lo que dicen nuestros pacientes</div>
                    <h2>Historias que <span style="color:var(--sp-teal)">sonríen</span>.</h2>
                    <p>Comentarios de personas que dieron el primer paso con una guía clara.</p>
                </div>

                <div class="sp-testimonials" data-testimonial-carousel>
                    <div class="sp-testimonial-track">
                        <article class="sp-testimonial">
                            <div class="sp-stars">★★★★★</div>
                            <blockquote>"Resultado inmediato, cero dolor y una atención impecable. Ya reserve mi próxima limpieza semestral."</blockquote>
                            <cite>Camila Torres · Blanqueamiento laser</cite>
                        </article>
                        <article class="sp-testimonial">
                            <div class="sp-stars">★★★★★</div>
                            <blockquote>"Me explicaron cada paso sin presión. Sentí que por fin entendía mi tratamiento antes de decidir."</blockquote>
                            <cite>Martín Rojas · Ortodoncia invisible</cite>
                        </article>
                        <article class="sp-testimonial">
                            <div class="sp-stars">★★★★★</div>
                            <blockquote>"La primera visita fue muy ordenada. Me fui con opciones claras, tiempos y presupuesto transparente."</blockquote>
                            <cite>Laura Díaz · Diseño de sonrisa</cite>
                        </article>
                    </div>
                    <div class="sp-carousel-dots" aria-label="Selector de testimonios"></div>
                </div>
            </div>
        </section>

        <section class="sp-section">
            <div class="sp-shell">
                <div class="sp-section-heading">
                    <div class="sp-chip">Preguntas frecuentes</div>
                    <h2>Sabemos que dar el primer paso <span style="color:var(--sp-teal)">cuesta</span>.</h2>
                    <p>Resolvemos las dudas más habituales antes de que llames.</p>
                </div>

                <div class="sp-faq" data-faq>
                    <article class="sp-faq-item is-open">
                        <button class="sp-faq-button" type="button"><span>¿Es doloroso el tratamiento?</span><span>-</span></button>
                        <div class="sp-faq-panel">La valoración inicial no duele. Si el tratamiento requiere algún procedimiento sensible, te explicaremos alternativas de anestesia y confort antes de avanzar.</div>
                    </article>
                    <article class="sp-faq-item">
                        <button class="sp-faq-button" type="button"><span>¿Cuánto dura el tratamiento completo?</span><span>+</span></button>
                        <div class="sp-faq-panel">Depende de la complejidad y del tratamiento elegido. En tu primera visita te daremos una estimación realista por fases.</div>
                    </article>
                    <article class="sp-faq-item">
                        <button class="sp-faq-button" type="button"><span>¿Cómo funciona la financiación?</span><span>+</span></button>
                        <div class="sp-faq-panel">Revisamos opciones disponibles y cuotas antes de que tomes una decisión. No hay compromiso por consultar.</div>
                    </article>
                    <article class="sp-faq-item">
                        <button class="sp-faq-button" type="button"><span>¿Qué pasa si viajo o me mudo?</span><span>+</span></button>
                        <div class="sp-faq-panel">Podemos planificar controles, seguimiento y tiempos para reducir interrupciones cuando el caso lo permite.</div>
                    </article>
                    <article class="sp-faq-item">
                        <button class="sp-faq-button" type="button"><span>¿El precio del presupuesto puede cambiar?</span><span>+</span></button>
                        <div class="sp-faq-panel">El presupuesto se confirma luego de evaluar tu caso. Si aparece algún hallazgo clínico, te lo explicaremos antes de modificar cualquier plan.</div>
                    </article>
                </div>

                <section class="sp-cta-band" aria-label="CTA final">
                    <div>
                        <strong>¿Listo para dar el primer paso?</strong>
                        <span>Agenda tu valoración y conserva tu código {{ $trackingToken }} para mantener el contexto de tu solicitud.</span>
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
        <a class="sp-sticky-whatsapp" href="{{ $whatsappLink }}" target="_blank" rel="noopener noreferrer" aria-label="Contactar por WhatsApp" data-whatsapp-link>
            <svg viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                <path fill="currentColor" d="M16.04 3.2A12.73 12.73 0 0 0 3.3 15.92c0 2.24.59 4.42 1.7 6.34L3.2 28.8l6.72-1.76a12.72 12.72 0 0 0 6.12 1.56h.01A12.73 12.73 0 0 0 28.8 15.88 12.74 12.74 0 0 0 16.04 3.2Zm0 23.24h-.01c-1.87 0-3.7-.5-5.3-1.45l-.38-.22-3.99 1.04 1.06-3.88-.25-.4a10.49 10.49 0 0 1-1.61-5.6c0-5.78 4.7-10.48 10.49-10.48a10.48 10.48 0 0 1 10.5 10.44c0 5.79-4.71 10.5-10.5 10.5Zm5.75-7.85c-.31-.16-1.86-.92-2.15-1.02-.29-.1-.5-.16-.71.16-.21.31-.81 1.02-1 1.23-.18.21-.37.23-.68.08-.31-.16-1.32-.49-2.52-1.55-.93-.83-1.56-1.86-1.74-2.17-.18-.31-.02-.48.14-.64.14-.14.31-.37.47-.55.16-.18.21-.31.31-.52.1-.21.05-.39-.03-.55-.08-.16-.71-1.71-.97-2.34-.26-.61-.52-.53-.71-.54h-.6c-.21 0-.55.08-.84.39-.29.31-1.1 1.08-1.1 2.62 0 1.55 1.13 3.04 1.29 3.25.16.21 2.22 3.39 5.38 4.75.75.32 1.34.52 1.8.66.76.24 1.45.2 1.99.12.61-.09 1.86-.76 2.12-1.5.26-.73.26-1.36.18-1.5-.08-.13-.29-.21-.6-.37Z" />
            </svg>
        </a>
    @endif

    <script>
        (() => {
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
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            sessionStorage.setItem(storageKey, sessionId);

            const revealSections = Array.from(document.querySelectorAll('.sp-section'));

            if (revealSections.length > 0) {
                revealSections.forEach((section, index) => {
                    section.classList.add('is-reveal-ready');
                    section.style.transitionDelay = prefersReducedMotion ? '0ms' : `${Math.min(index * 90, 240)}ms`;
                });

                if ('IntersectionObserver' in window && ! prefersReducedMotion) {
                    const revealObserver = new IntersectionObserver((entries) => {
                        entries.forEach((entry) => {
                            if (! entry.isIntersecting) {
                                return;
                            }

                            entry.target.classList.add('is-visible');
                            entry.target.style.transitionDelay = '0ms';
                            revealObserver.unobserve(entry.target);
                        });
                    }, {
                        rootMargin: '0px 0px -8% 0px',
                        threshold: .18,
                    });

                    revealSections.forEach((section) => revealObserver.observe(section));
                } else {
                    revealSections.forEach((section) => section.classList.add('is-visible'));
                }
            }

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

            document.querySelectorAll('[data-before-after-compare]').forEach((compare) => {
                const updateCompare = (clientX) => {
                    const rect = compare.getBoundingClientRect();
                    const x = Math.min(100, Math.max(0, ((clientX - rect.left) / rect.width) * 100));

                    compare.style.setProperty('--compare-x', `${x}%`);
                };

                compare.addEventListener('pointermove', (event) => updateCompare(event.clientX));
                compare.addEventListener('pointerdown', (event) => updateCompare(event.clientX));
            });

            document.querySelectorAll('[data-testimonial-carousel]').forEach((carousel) => {
                const track = carousel.querySelector('.sp-testimonial-track');
                const slides = Array.from(carousel.querySelectorAll('.sp-testimonial'));
                const dots = carousel.querySelector('.sp-carousel-dots');

                if (! track || ! dots || slides.length === 0) {
                    return;
                }

                let activeIndex = 0;
                const buttons = slides.map((_, index) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.setAttribute('aria-label', `Ver testimonio ${index + 1}`);
                    button.addEventListener('click', () => showSlide(index));
                    dots.appendChild(button);

                    return button;
                });

                const showSlide = (index) => {
                    activeIndex = index;
                    track.style.transform = `translateX(-${activeIndex * 100}%)`;
                    buttons.forEach((button, buttonIndex) => {
                        button.classList.toggle('is-active', buttonIndex === activeIndex);
                    });
                };

                showSlide(activeIndex);

                if (! window.matchMedia('(prefers-reduced-motion: reduce)').matches && slides.length > 1) {
                    setInterval(() => showSlide((activeIndex + 1) % slides.length), 5200);
                }
            });

            document.querySelectorAll('[data-faq]').forEach((faq) => {
                faq.querySelectorAll('.sp-faq-button').forEach((button) => {
                    button.addEventListener('click', () => {
                        const currentItem = button.closest('.sp-faq-item');

                        faq.querySelectorAll('.sp-faq-item').forEach((item) => {
                            const isCurrent = item === currentItem;
                            const indicator = item.querySelector('.sp-faq-button span:last-child');

                            item.classList.toggle('is-open', isCurrent);

                            if (indicator) {
                                indicator.textContent = isCurrent ? '-' : '+';
                            }
                        });
                    });
                });
            });

            setInterval(() => {
                const duration = Math.round((Date.now() - startedAt) / 1000);

                if (duration >= threshold && localStorage.getItem(thresholdKey) !== '1') {
                    localStorage.setItem(thresholdKey, '1');
                    send('duration_threshold', duration, { threshold_seconds: threshold });
                }
            }, Math.max(5, pingSeconds) * 1000);
        })();
    </script>
</body>
</html>
