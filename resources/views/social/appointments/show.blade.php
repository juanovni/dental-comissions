<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agenda tu cita</title>
    <style>
        :root {
            --appt-bg: #edf7f5;
            --appt-card: #ffffff;
            --appt-muted: #64748b;
            --appt-text: #0f172a;
            --appt-border: #dbe8e7;
            --appt-primary: oklch(55% .12 185);
            --appt-primary-soft: #dcf7f3;
            --appt-shadow: 0 24px 70px -44px rgba(15, 23, 42, .55);
            color-scheme: light;
            font-family: Aptos, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        * { box-sizing: border-box; }
        body {
            background:
                radial-gradient(circle at top left, rgba(0, 155, 143, .12), transparent 32rem),
                linear-gradient(180deg, #f7fdfc 0%, var(--appt-bg) 100%);
            color: var(--appt-text);
            margin: 0;
        }
        button, input { font: inherit; }
        .visually-hidden-copy { left: -9999px; position: fixed; top: -9999px; }

        .appointment-shell {
            display: grid;
            gap: 1rem;
            margin: 0 auto;
            max-width: 90rem;
            min-height: 100vh;
            padding: 1rem;
        }
        .appointment-shell.is-confirmed { align-content: center; }

        .appointment-hero {
            align-items: end;
            display: grid;
            gap: .65rem;
            padding-top: .45rem;
        }
        .appointment-eyebrow {
            align-items: center;
            color: #047c72;
            display: inline-flex;
            font-size: .74rem;
            font-weight: 600;
            gap: .42rem;
            letter-spacing: .09em;
            text-transform: uppercase;
        }
        .appointment-eyebrow svg { height: .92rem; width: .92rem; }
        h1 { font-size: clamp(1.75rem, 6vw, 2.6rem); font-weight: 650; letter-spacing: -.04em; line-height: 1.05; margin: .45rem 0 0; }
        .appointment-copy { color: #475569; font-size: .93rem; line-height: 1.55; margin: .55rem 0 0; max-width: 38rem; }

        .appointment-card {
            background: var(--appt-card);
            border: 1px solid var(--appt-border);
            border-radius: 1.25rem;
            box-shadow: var(--appt-shadow);
            padding: 1rem;
        }

        .appointment-context {
            display: grid;
            gap: .7rem;
        }

        .context-item {
            align-items: center;
            background: #fbfefd;
            border: 1px solid var(--appt-border);
            border-radius: 1rem;
            display: flex;
            gap: .75rem;
            padding: .85rem;
        }
        .context-icon, .summary-icon {
            align-items: center;
            background: var(--appt-primary-soft);
            border-radius: 999px;
            color: #047c72;
            display: inline-flex;
            flex: 0 0 auto;
            height: 2rem;
            justify-content: center;
            width: 2rem;
        }
        .context-icon svg, .summary-icon svg { height: 1rem; width: 1rem; }
        .context-label { color: var(--appt-muted); display: block; font-size: .72rem; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; }
        .context-value { display: block; font-size: .98rem; font-weight: 600; margin-top: .2rem; }

        .schedule-head {
            align-items: end;
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .schedule-kicker { color: var(--appt-muted); display: block; font-size: .73rem; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; }
        .schedule-title { display: block; font-size: clamp(1.45rem, 3vw, 2.05rem); font-weight: 650; letter-spacing: -.04em; line-height: 1; margin-top: .25rem; }
        .schedule-count { color: var(--appt-muted); font-size: .78rem; white-space: nowrap; }

        .appointment-carousel-shell {
            position: relative;
        }

        .appointment-carousel-shell::before,
        .appointment-carousel-shell::after {
            content: '';
            inset-block: 0 .55rem;
            pointer-events: none;
            position: absolute;
            width: 3rem;
            z-index: 4;
        }

        .appointment-carousel-shell::before {
            background: linear-gradient(90deg, #ffffff, rgba(255, 255, 255, 0));
            left: -.1rem;
        }

        .appointment-carousel-shell::after {
            background: linear-gradient(270deg, #ffffff, rgba(255, 255, 255, 0));
            right: -.1rem;
        }

        .appointment-carousel-btn {
            align-items: center;
            background: rgba(255, 255, 255, .94);
            border: 1px solid var(--appt-border);
            border-radius: 999px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .12);
            color: var(--appt-text);
            cursor: pointer;
            display: inline-flex;
            font-size: 1.25rem;
            font-weight: 700;
            height: 2.55rem;
            justify-content: center;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            transition: transform .16s ease, box-shadow .16s ease, background .16s ease;
            width: 2.55rem;
            z-index: 8;
        }

        .appointment-carousel-btn:hover { background: #ffffff; box-shadow: 0 16px 34px rgba(15, 23, 42, .15); transform: translateY(-50%) scale(1.04); }
        .appointment-carousel-btn.prev { left: .35rem; }
        .appointment-carousel-btn.next { right: .35rem; }

        .day-pills {
            display: grid;
            gap: .75rem;
            grid-auto-columns: minmax(10.8rem, 1fr);
            grid-auto-flow: column;
            overflow-x: auto;
            overscroll-behavior-x: contain;
            padding: .15rem 3.35rem .55rem;
            scroll-behavior: smooth;
            scroll-padding-inline: 3.35rem;
            scroll-snap-type: x proximity;
            scrollbar-width: none;
        }
        .day-pills::-webkit-scrollbar { display: none; }

        .day-pill {
            align-items: stretch;
            background: linear-gradient(180deg, #ffffff 0%, #fafeFD 100%);
            border: 1px solid var(--appt-border);
            border-radius: 1rem;
            display: grid;
            flex: 0 0 auto;
            gap: .8rem;
            min-height: 13.6rem;
            min-width: 10.8rem;
            padding: 1rem;
            position: relative;
            scroll-snap-align: start;
            text-align: left;
            transition: border-color .16s ease, background .16s ease, box-shadow .16s ease, transform .16s ease;
        }
        .day-pill:hover { border-color: rgba(15, 118, 110, .32); transform: translateY(-1px); }
        .day-pill.is-active {
            background: linear-gradient(180deg, #d7f5ef 0%, #c9eee8 100%);
            border-color: rgba(0, 155, 143, .5);
            box-shadow: 0 18px 38px -30px rgba(0, 99, 88, .72);
            color: var(--appt-text);
        }
        .day-pill.is-preferred { border-color: rgba(15, 118, 110, .35); }
        .day-pill.is-full { opacity: .5; }
        .day-pill.is-full.is-active { opacity: 1; }

        .day-pill-name {
            color: var(--appt-muted);
            display: block;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .day-pill-date {
            display: block;
            font-size: 1.45rem;
            font-weight: 650;
            letter-spacing: -.04em;
            line-height: 1.05;
            margin-top: .22rem;
        }
        .day-pill-header { position: relative; }
        .day-pill-status {
            background: rgba(255, 255, 255, .7);
            border: 1px solid rgba(219, 232, 231, .86);
            border-radius: 999px;
            display: inline-flex;
            font-size: .67rem;
            font-weight: 700;
            margin-top: .6rem;
            padding: .2rem .48rem;
        }
        .day-pill-status.available { color: #047c72; }
        .day-pill-status.full { color: #b91c1c; }
        .day-pill.is-active .day-pill-status.available,
        .day-pill.is-active .day-pill-status.full { color: #047c72; opacity: 1; }

        .slot-grid {
            display: grid;
            gap: .42rem;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .slot-btn {
            background: rgba(255, 255, 255, .84);
            border: 1px solid var(--appt-border);
            border-radius: .65rem;
            color: var(--appt-text);
            cursor: pointer;
            font-size: .85rem;
            font-weight: 650;
            min-height: 2.1rem;
            padding: .42rem .35rem;
            text-align: center;
            transition: background-color .16s ease, border-color .16s ease, color .16s ease, filter .16s ease, transform .16s ease;
        }
        .slot-btn:hover, .slot-btn:focus { border-color: rgba(15, 118, 110, .45); outline: none; transform: translateY(-1px); }
        .slot-btn.is-selected { background: var(--appt-primary); border-color: var(--appt-primary); color: #ffffff; box-shadow: 0 10px 20px -16px rgba(0, 99, 88, .9); }

        .empty-day-message {
            color: var(--appt-muted);
            font-size: .88rem;
            line-height: 1.5;
            padding: .75rem 0;
        }

        .offer-context {
            background: #fafffe;
            border: 1px solid var(--appt-border);
            border-radius: .75rem;
            padding: .7rem .85rem;
        }
        .offer-context-label {
            color: var(--appt-muted);
            display: block;
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .08em;
            margin-bottom: .35rem;
            text-transform: uppercase;
        }
        .offer-context-time {
            align-items: center;
            display: inline-flex;
            font-size: .82rem;
            font-weight: 600;
            gap: .35rem;
            margin-right: .6rem;
            white-space: nowrap;
        }
        .offer-context-time svg {
            color: #047c72;
            height: .72rem;
            width: .72rem;
        }

        .appointment-message { border-radius: .8rem; font-size: .87rem; line-height: 1.45; padding: .85rem; }
        .appointment-message.success { background: #ecfdf5; color: #047857; }
        .appointment-message.error { background: #fef2f2; color: #b91c1c; }
        .appointment-message.warning { background: #fff7ed; color: #9a3412; }

        .confirmation-card {
            background: var(--appt-card);
            border: 1px solid var(--appt-border);
            border-radius: 1.2rem;
            box-shadow: var(--appt-shadow);
            overflow: hidden;
        }
        .confirmation-hero {
            background: linear-gradient(145deg, #d8f7f1 0%, #c8f0e8 100%);
            border-bottom: 1px solid var(--appt-border);
            padding: 2.2rem 1.2rem 2rem;
            text-align: center;
        }
        .confirmation-check {
            align-items: center;
            background: #008574;
            border-radius: 999px;
            box-shadow: 0 16px 34px -18px rgba(0, 90, 78, .8);
            color: #ffffff;
            display: inline-flex;
            height: 4rem;
            justify-content: center;
            margin-bottom: 1.1rem;
            width: 4rem;
        }
        .confirmation-check svg { height: 2rem; width: 2rem; }
        .confirmation-kicker {
            color: #00796b;
            display: block;
            font-size: .78rem;
            font-weight: 600;
            letter-spacing: .18em;
            text-transform: uppercase;
        }
        .confirmation-title {
            font-size: clamp(1.85rem, 6vw, 2.55rem);
            font-weight: 650;
            letter-spacing: -.045em;
            line-height: 1.04;
            margin: .7rem 0 0;
        }
        .confirmation-copy {
            color: #475569;
            font-size: .98rem;
            line-height: 1.55;
            margin: .85rem auto 0;
            max-width: 34rem;
        }
        .confirmation-details { display: grid; }
        .confirmation-detail {
            align-items: center;
            border-bottom: 1px solid var(--appt-border);
            display: flex;
            gap: .9rem;
            min-height: 6.1rem;
            padding: 1.1rem;
        }
        .confirmation-detail:last-child { border-bottom: 0; }
        .confirmation-icon {
            align-items: center;
            background: var(--appt-primary-soft);
            border-radius: 999px;
            color: #047c72;
            display: inline-flex;
            flex: 0 0 auto;
            height: 2.45rem;
            justify-content: center;
            width: 2.45rem;
        }
        .confirmation-icon svg { height: 1.18rem; width: 1.18rem; }
        .confirmation-label { color: var(--appt-muted); display: block; font-size: .72rem; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; }
        .confirmation-value { display: block; font-size: 1.02rem; font-weight: 650; margin-top: .22rem; }
        .confirmation-subvalue { color: #475569; display: block; font-size: .86rem; margin-top: .15rem; }
        .confirmation-actions {
            border-top: 1px solid var(--appt-border);
            display: grid;
            gap: .55rem;
            padding: 1rem;
        }
        .confirmation-action {
            align-items: center;
            background: #fafffe;
            border: 1px solid var(--appt-border);
            border-radius: .62rem;
            color: var(--appt-text);
            cursor: pointer;
            display: inline-flex;
            font-size: .92rem;
            font-weight: 600;
            gap: .5rem;
            justify-content: center;
            min-height: 2.75rem;
            padding: .7rem .95rem;
            text-decoration: none;
            transition: border-color .16s ease, filter .16s ease, transform .16s ease;
        }
        .confirmation-action:hover, .confirmation-action:focus { border-color: rgba(15, 118, 110, .45); filter: brightness(.99); outline: none; transform: translateY(-1px); }
        .confirmation-action.primary { background: var(--appt-primary); border-color: var(--appt-primary); color: #ffffff; }
        .confirmation-action svg { height: 1.08rem; width: 1.08rem; }
        .confirmation-action.wide { grid-column: 1 / -1; }
        .confirmation-footer {
            color: #47606d;
            font-size: .82rem;
            line-height: 1.45;
            margin: -.25rem auto 0;
            max-width: 34rem;
            text-align: center;
        }

        .summary-card {
            align-items: center;
            background: rgba(255, 255, 255, .94);
            backdrop-filter: blur(14px);
            display: grid;
            gap: .85rem;
            position: sticky;
            bottom: .85rem;
            z-index: 5;
        }
        .summary-content { align-items: start; display: flex; gap: .75rem; min-width: 0; }
        .summary-content > span:last-child { min-width: 0; }
        .summary-label { color: var(--appt-muted); display: block; font-size: .84rem; }
        .summary-title { display: block; font-size: 1rem; font-weight: 650; line-height: 1.3; margin-top: .12rem; overflow-wrap: anywhere; }
        .summary-meta { color: #475569; display: block; font-size: .82rem; margin-top: .18rem; }
        .appointment-btn {
            align-items: center;
            background: var(--appt-primary);
            border: 0;
            border-radius: .65rem;
            color: #fff;
            cursor: pointer;
            display: inline-flex;
            font-size: .88rem;
            font-weight: 700;
            gap: .42rem;
            justify-content: center;
            min-height: 3rem;
            padding: .78rem 1rem;
            transition: filter .16s ease, opacity .16s ease, transform .16s ease;
            width: 100%;
        }
        .appointment-btn svg { height: 1rem; width: 1rem; }
        .appointment-btn:disabled { background: #7bc5bd; cursor: not-allowed; opacity: 1; }
        .appointment-btn:not(:disabled):hover { filter: brightness(.97); transform: translateY(-1px); }

        .patient-info-row { display: grid; gap: .4rem; }
        .patient-info-label { color: var(--appt-muted); font-size: .78rem; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; }
        .patient-info-help { color: #64748b; font-size: .78rem; line-height: 1.35; }
        .patient-info-input {
            background: #fafffe;
            border: 1px solid var(--appt-border);
            border-radius: .6rem;
            color: var(--appt-text);
            font-size: .94rem;
            padding: .68rem .78rem;
            transition: border-color .16s ease;
            width: 100%;
        }
        .patient-info-input:focus { border-color: rgba(15, 118, 110, .45); outline: none; }
        .phone-confirm-row {
            align-items: center;
            background: #fafefd;
            border: 1px solid var(--appt-border);
            border-radius: .6rem;
            cursor: pointer;
            display: flex;
            font-size: .86rem;
            gap: .6rem;
            min-height: 2.6rem;
            padding: .55rem .7rem;
        }
        .phone-confirm-row input[type="checkbox"] { flex: 0 0 auto; height: 1rem; width: 1rem; }
        .phone-confirm-text { line-height: 1.4; }
        .phone-confirm-text strong { color: var(--appt-text); font-weight: 600; }

        .full-day-badge {
            background: #fee2e2;
            border-radius: .35rem;
            color: #b91c1c;
            display: inline-block;
            font-size: .72rem;
            font-weight: 700;
            padding: .2rem .45rem;
        }

        @media (min-width: 760px) {
            .appointment-shell { gap: 1.25rem; padding: 2rem 1.75rem; }
            .appointment-hero { grid-template-columns: minmax(0, 1fr) auto; }
            .appointment-copy { margin-bottom: .1rem; }
            .appointment-context { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .day-pills { gap: .65rem; }
            .confirmation-details { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .confirmation-detail:nth-child(odd) { border-right: 1px solid var(--appt-border); }
            .confirmation-detail:nth-last-child(-n + 2) { border-bottom: 0; }
            .confirmation-actions { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .summary-card { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .appointment-btn { width: auto; }
        }

        @media (max-width: 640px) {
            body { padding-bottom: 12rem; }
            .schedule-head { align-items: start; flex-direction: column; }
            .schedule-count { white-space: normal; }
            .appointment-carousel-shell { margin-inline: -1rem; }
            .appointment-carousel-shell::before,
            .appointment-carousel-shell::after { display: none; }
            .appointment-carousel-btn { height: 2.35rem; opacity: .92; top: 47%; width: 2.35rem; }
            .appointment-carousel-btn.prev { left: .55rem; }
            .appointment-carousel-btn.next { right: .55rem; }
            .day-pills { grid-auto-columns: minmax(16.2rem, 76vw); padding-inline: 1rem 4.4rem; scroll-padding-inline: 1rem; }
            .day-pill { min-height: 13rem; }
            .summary-card {
                border-radius: 1.1rem 1.1rem 0 0;
                bottom: 0;
                box-shadow: 0 -18px 44px -30px rgba(15, 23, 42, .75);
                left: 0;
                margin: 0;
                padding: .9rem 1rem calc(.9rem + env(safe-area-inset-bottom));
                position: fixed;
                right: 0;
            }
            .appointment-btn { width: 100%; }
        }

        @media (max-width: 360px) {
            .slot-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>
</head>
<body>
    @php
        $procedureName = $procedure?->name ?? 'Valoración dental';
        $doctorName = $doctor?->name;
        $confirmedDate = $confirmedAppointment?->scheduled_at;
        $confirmedProcedure = $confirmedAppointment?->procedure?->name ?? $procedureName;
        $confirmedDuration = $confirmedAppointment?->duration_minutes ?: $duration;
        $confirmedDoctor = $doctorName;
        $appointmentSummary = collect([
            'Mi cita dental quedó registrada:',
            'Tratamiento: '.$confirmedProcedure,
            'Fecha: '.($confirmedDate?->isoFormat('dddd D [de] MMMM YYYY') ?? 'Fecha registrada'),
            'Hora: '.($confirmedDate?->format('H:i') ?? 'Hora registrada').($confirmedDuration ? ' h · '.$confirmedDuration.' min' : ''),
            $confirmedDoctor ? 'Profesional: '.$confirmedDoctor : null,
        ])->filter()->implode("\n");

        $windowDays = $availabilityWindow['days'] ?? [];
        $preferredDateFull = $availabilityWindow['preferred_date_full'] ?? false;
        $firstAvailableDay = $availabilityWindow['first_available_day'] ?? null;
        $daysWithSlots = collect($windowDays)->filter(fn ($d) => ! $d['is_full'])->count();
        $totalSlots = collect($windowDays)->sum('slot_count');
        $firstWindowDay = collect($windowDays)->first();
        $lastWindowDay = collect($windowDays)->last();
        $scheduleStart = $firstWindowDay['date'] ?? null;
        $scheduleEnd = $lastWindowDay['date'] ?? null;
        $scheduleRange = $scheduleStart && $scheduleEnd
            ? $scheduleStart->isoFormat('D MMM').' — '.$scheduleEnd->isoFormat('D MMM YYYY')
            : 'Próximos días';
    @endphp

    <main class="appointment-shell {{ $confirmedAppointment ? 'is-confirmed' : '' }}">
        @unless ($confirmedAppointment)
        <header class="appointment-hero">
            <div class="appointment-eyebrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v4"/><path d="M12 17v4"/><path d="M3 12h4"/><path d="M17 12h4"/><path d="m5.6 5.6 2.8 2.8"/><path d="m15.6 15.6 2.8 2.8"/><path d="m18.4 5.6-2.8 2.8"/><path d="m8.4 15.6-2.8 2.8"/></svg>
                Clínica Dental
            </div>

            <p class="appointment-copy">
                Elige el horario que mejor te funcione. Validaremos disponibilidad antes de registrar la cita.
            </p>
        </header>
        @endunless

        @if ($confirmedAppointment)
            <section class="confirmation-card" aria-label="Cita confirmada">
                <div class="confirmation-hero">
                    <span class="confirmation-check">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 13 4 4L19 7"/></svg>
                    </span>
                    <span class="confirmation-kicker">Cita confirmada</span>
                    <h2 class="confirmation-title">¡Tu cita quedó registrada!</h2>
                    <p class="confirmation-copy">
                        Te enviamos la confirmación por WhatsApp. Si necesitas hacer un cambio, responde a ese mensaje y con gusto te ayudamos.
                    </p>
                </div>

                <div class="confirmation-details">
                    <div class="confirmation-detail">
                        <span class="confirmation-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="m9 16 2 2 4-4"/></svg></span>
                        <span><span class="confirmation-label">Fecha</span><span class="confirmation-value">{{ $confirmedDate?->isoFormat('dddd D [de] MMMM YYYY') ?? 'Fecha registrada' }}</span></span>
                    </div>
                    <div class="confirmation-detail">
                        <span class="confirmation-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                        <span><span class="confirmation-label">Hora</span><span class="confirmation-value">{{ $confirmedDate?->format('H:i') ?? 'Hora registrada' }} h @if ($confirmedDuration) · {{ $confirmedDuration }} min @endif</span></span>
                    </div>
                    <div class="confirmation-detail">
                        <span class="confirmation-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v20"/><path d="M7 7h10"/><path d="M7 17h10"/><path d="M5 12h14"/></svg></span>
                        <span><span class="confirmation-label">Tratamiento</span><span class="confirmation-value">{{ $confirmedProcedure }}</span></span>
                    </div>
                    @if ($confirmedDoctor)
                        <div class="confirmation-detail">
                            <span class="confirmation-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span>
                            <span><span class="confirmation-label">Profesional</span><span class="confirmation-value">{{ $confirmedDoctor }}</span></span>
                        </div>
                    @endif
                </div>

                <div class="confirmation-actions" aria-label="Acciones de la cita">
                    <a class="confirmation-action primary" href="{{ route('social-appointments.calendar', ['token' => $offer->token]) }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M12 14v5"/><path d="M9.5 16.5H14.5"/></svg>
                        Agregar al calendario
                    </a>
                    <a class="confirmation-action" href="https://wa.me/?text={{ rawurlencode($appointmentSummary) }}" target="_blank" rel="noopener">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 11.5a8 8 0 0 1-11.8 7L4 20l1.4-4.3A8 8 0 1 1 20 11.5Z"/><path d="M9.5 8.8c.2 3 1.8 4.7 4.8 5"/></svg>
                        Compartir por WhatsApp
                    </a>
                    <button class="confirmation-action wide" type="button" id="copy-summary" data-summary="{{ e($appointmentSummary) }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="13" height="13" x="8" y="8" rx="2"/><path d="M5 16H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v1"/></svg>
                        <span id="copy-summary-label">Copiar resumen</span>
                    </button>
                </div>
            </section>
            <p class="confirmation-footer">¿Necesitas reagendar? Escríbenos por WhatsApp respondiendo a la confirmación de tu cita.</p>
        @else
        <section class="appointment-card appointment-context" aria-label="Información de la cita">
            <div class="context-item">
                <span class="context-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 7h6"/><path d="M9 12h6"/><path d="M9 17h4"/><path d="M5 3h14v18H5z"/></svg></span>
                <span><span class="context-label">Tratamiento</span><span class="context-value">{{ $procedureName }} @if ($duration) · {{ $duration }} min @endif</span></span>
            </div>

            @if ($doctorName)
                <div class="context-item">
                    <span class="context-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span>
                    <span><span class="context-label">Profesional</span><span class="context-value">{{ $doctorName }}</span></span>
                </div>
            @endif
        </section>

        <section class="appointment-card" aria-label="Horarios disponibles">
            @if (session('appointment_success'))
                <div class="appointment-message success">{{ session('appointment_success') }}</div>
            @elseif (session('appointment_error'))
                <div class="appointment-message error">{{ session('appointment_error') }}</div>
            @elseif ($expired)
                <div class="appointment-message warning">Este enlace ya expiró o fue utilizado. Escríbenos por WhatsApp para generar nuevas opciones.</div>
            @elseif ($daysWithSlots === 0)
                <div class="appointment-message warning">No encontramos horarios disponibles en los próximos días. Escríbenos por WhatsApp para ayudarte a coordinar una cita.</div>
            @else
                <div class="schedule-head">
                    <div>
                        <span class="schedule-kicker">
                            @if ($preferredDate && $preferredDateFull)
                                {{ $preferredDate->isoFormat('ddd D [de] MMM') }} está completo
                            @else
                                Semana
                            @endif
                        </span>
                        <span class="schedule-title">
                            @if ($preferredDate && $preferredDateFull)
                                Mostrando opciones cercanas
                            @else
                                {{ $scheduleRange }}
                            @endif
                        </span>
                    </div>
                    <span class="schedule-count">{{ $totalSlots }} horarios · {{ $daysWithSlots }} días</span>
                </div>

                @if ($preferredDate && $preferredDateFull)
                    <div class="offer-context" style="margin-bottom:.85rem;">
                        <span class="offer-context-label">{{ $preferredDate->isoFormat('ddd D [de] MMMM') }}</span>
                        <span><span class="full-day-badge">Completo</span> &nbsp; <span style="color:var(--appt-muted);font-size:.82rem;">No quedan horarios libres para este día. Te mostramos alternativas cercanas.</span></span>
                    </div>
                @endif

                <div class="appointment-carousel-shell">
                    <button class="appointment-carousel-btn prev" type="button" aria-label="Ver días anteriores" data-appointment-carousel="prev">&lsaquo;</button>
                    <button class="appointment-carousel-btn next" type="button" aria-label="Ver días siguientes" data-appointment-carousel="next">&rsaquo;</button>

                    <div class="day-pills" id="day-pills" data-appointment-days>
                        @foreach ($windowDays as $day)
                        @php
                            $date = $day['date'];
                            $isActive = $loop->first && ! $preferredDateFull;
                            $isActive = $isActive || ($loop->first && $preferredDateFull && $firstAvailableDay && $date->isSameDay($firstAvailableDay));
                            $isActive = $isActive || ($preferredDate && ! $preferredDateFull && $date->isSameDay($preferredDate));
                        @endphp
                        <div
                            class="day-pill
                                {{ $isActive ? 'is-active' : '' }}
                                {{ $day['is_preferred'] && ! $day['is_full'] ? 'is-preferred' : '' }}
                                {{ $day['is_full'] ? 'is-full' : '' }}"
                            data-date="{{ $date->toDateString() }}"
                            aria-label="{{ $day['long_label'] }}"
                            role="group"
                        >
                            <span class="day-pill-header">
                                <span class="day-pill-name">{{ $date->isoFormat('ddd') }}</span>
                                <span class="day-pill-date">{{ $date->isoFormat('D MMM') }}</span>
                                @if ($day['is_full'])
                                    <span class="day-pill-status full">Completo</span>
                                @else
                                    <span class="day-pill-status available">{{ $day['slot_count'] }} horario{{ $day['slot_count'] !== 1 ? 's' : '' }}</span>
                                @endif
                            </span>

                            @if ($day['is_full'])
                                <div class="empty-day-message">Este día no tiene horarios disponibles.</div>
                            @else
                                <div class="slot-grid" aria-label="Horarios de {{ $day['long_label'] }}">
                                    @foreach ($day['slots'] as $slot)
                                        <button
                                            class="slot-btn"
                                            type="button"
                                            data-datetime="{{ $slot->format('Y-m-d H:i:s') }}"
                                            data-summary="{{ $slot->isoFormat('dddd D [de] MMMM') }} · {{ $slot->format('H:i') }}"
                                            data-meta="{{ trim(($doctorName ? $doctorName.' · ' : '').$procedureName) }}"
                                            aria-label="Seleccionar {{ $slot->isoFormat('dddd D [de] MMMM [a las] H:mm') }}"
                                        >
                                            {{ $slot->format('H:i') }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        @if (! $expired && $daysWithSlots > 0 && ! session('appointment_success'))
            <form class="appointment-card summary-card" method="POST" action="{{ route('social-appointments.confirm', ['token' => $offer->token]) }}" id="appointment-confirm-form">
                @csrf
                <input type="hidden" name="selected_datetime" id="selected-datetime" value="">
                <input type="hidden" name="option" id="selected-option" value="">

                <div class="patient-info-row">
                    <label class="patient-info-label" for="patient-name">Nombre del paciente</label>
                    <input class="patient-info-input" type="text" name="patient_name" id="patient-name" value="{{ old('patient_name', $patientName) }}" placeholder="Ej: Juan Constantine" autocomplete="name" required>
                    <span class="patient-info-help">
                        @if ($needsPatientName)
                            Necesitamos este dato para registrar tu cita.
                        @else
                            Agendaremos con este nombre. Puedes corregirlo si la cita es para otra persona.
                        @endif
                    </span>
                </div>

                @if ($needsPatientName && $patientPhone)
                    <label class="phone-confirm-row">
                        <input type="checkbox" name="phone_confirmed" value="1" required>
                        <span class="phone-confirm-text">Usaremos <strong>{{ $patientPhone }}</strong> para recordatorios por WhatsApp</span>
                    </label>
                @endif

                <div class="summary-content">
                    <span class="summary-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                    <span>
                        <span class="summary-label">Cita seleccionada</span>
                        <span class="summary-title" id="selected-summary">Selecciona un horario disponible</span>
                        <span class="summary-meta" id="selected-meta">{{ trim(($doctorName ? $doctorName.' · ' : '').$procedureName) }}</span>
                    </span>
                </div>
                <button class="appointment-btn" type="submit" id="confirm-button" disabled>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="m9 16 2 2 4-4"/></svg>
                    Confirmar cita
                </button>
            </form>
        @endif
        @endif
    </main>

    <script>
        (function () {
            var pills = document.querySelectorAll('.day-pill');
            var slotButtons = document.querySelectorAll('.slot-btn');
            var daysTrack = document.querySelector('[data-appointment-days]');
            var carouselButtons = document.querySelectorAll('[data-appointment-carousel]');
            var datetimeInput = document.getElementById('selected-datetime');
            var optionInput = document.getElementById('selected-option');
            var summary = document.getElementById('selected-summary');
            var meta = document.getElementById('selected-meta');
            var confirmButton = document.getElementById('confirm-button');

            slotButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    var card = button.closest('.day-pill');

                    pills.forEach(function (candidate) { candidate.classList.remove('is-active'); });
                    slotButtons.forEach(function (candidate) { candidate.classList.remove('is-selected'); });

                    if (card) {
                        card.classList.add('is-active');
                    }

                    button.classList.add('is-selected');

                    if (datetimeInput) {
                        datetimeInput.value = button.dataset.datetime || '';
                    }

                    if (optionInput) {
                        optionInput.value = '';
                    }

                    if (summary) {
                        summary.textContent = button.dataset.summary || 'Horario seleccionado';
                    }

                    if (meta && button.dataset.meta) {
                        meta.textContent = button.dataset.meta;
                    }

                    if (confirmButton) {
                        confirmButton.disabled = false;
                    }
                });
            });

            carouselButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    var card = daysTrack ? daysTrack.querySelector('.day-pill') : null;

                    if (! daysTrack || ! card) {
                        return;
                    }

                    var gap = parseFloat(getComputedStyle(daysTrack).columnGap || '0');
                    var distance = card.getBoundingClientRect().width + gap;
                    var direction = button.dataset.appointmentCarousel === 'next' ? 1 : -1;

                    daysTrack.scrollBy({
                        left: direction * distance,
                        behavior: 'smooth'
                    });
                });
            });

            var copyButton = document.getElementById('copy-summary');
            var copyLabel = document.getElementById('copy-summary-label');

            if (copyButton && copyLabel) {
                copyButton.addEventListener('click', function () {
                    var summary = copyButton.dataset.summary || '';
                    var markCopied = function () {
                        copyLabel.textContent = 'Resumen copiado';
                        window.setTimeout(function () { copyLabel.textContent = 'Copiar resumen'; }, 1800);
                    };

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(summary).then(markCopied);
                        return;
                    }

                    var textarea = document.createElement('textarea');
                    textarea.value = summary;
                    textarea.setAttribute('readonly', 'readonly');
                    textarea.className = 'visually-hidden-copy';
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                    markCopied();
                });
            }
        })();
    </script>
</body>
</html>
