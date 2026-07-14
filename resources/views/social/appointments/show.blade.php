<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agenda tu cita</title>
    <style>
        :root {
            --appt-bg: #f4fbfa;
            --appt-card: #ffffff;
            --appt-muted: #64748b;
            --appt-text: #0f172a;
            --appt-border: #dbe8e7;
            --appt-primary: oklch(55% .12 185);
            --appt-primary-soft: #dcf7f3;
            --appt-shadow: 0 14px 38px -30px rgba(15, 23, 42, .46);
            color-scheme: light;
            font-family: Aptos, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        * { box-sizing: border-box; }
        body { background: var(--appt-bg); color: var(--appt-text); margin: 0; }
        button, input { font: inherit; }

        .appointment-shell {
            display: grid;
            gap: 1rem;
            margin: 0 auto;
            max-width: 48rem;
            min-height: 100vh;
            padding: 1.1rem;
        }

        .appointment-hero { padding-top: .45rem; }
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
            border-radius: 1rem;
            box-shadow: var(--appt-shadow);
            padding: 1rem;
        }

        .appointment-context {
            display: grid;
            gap: .75rem;
        }

        .context-item {
            align-items: center;
            background: #fbfefd;
            border: 1px solid var(--appt-border);
            border-radius: .85rem;
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

        .schedule-head { display: flex; gap: 1rem; justify-content: space-between; margin-bottom: .85rem; }
        .schedule-kicker { color: var(--appt-muted); display: block; font-size: .73rem; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; }
        .schedule-title { display: block; font-size: 1.05rem; font-weight: 600; margin-top: .2rem; }
        .schedule-count { color: var(--appt-muted); font-size: .78rem; white-space: nowrap; }

        .date-grid { display: grid; gap: .75rem; }
        .date-card {
            border: 1px solid var(--appt-border);
            border-radius: .9rem;
            padding: .8rem;
        }
        .date-card.is-today { background: #e0f7f2; border-color: rgba(15, 118, 110, .28); }
        .date-name { color: var(--appt-muted); display: block; font-size: .72rem; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; }
        .date-value { display: block; font-size: 1.05rem; font-weight: 650; margin-top: .18rem; }
        .slot-grid { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: .75rem; }
        .slot-btn {
            background: #ffffff;
            border: 1px solid var(--appt-border);
            border-radius: .55rem;
            color: var(--appt-text);
            cursor: pointer;
            font-size: .85rem;
            font-weight: 600;
            min-height: 2.1rem;
            padding: .4rem .62rem;
            transition: background-color .16s ease, border-color .16s ease, color .16s ease, filter .16s ease;
        }
        .slot-btn:hover, .slot-btn:focus { border-color: rgba(15, 118, 110, .45); outline: none; }
        .slot-btn.is-selected { background: var(--appt-primary); border-color: var(--appt-primary); color: #ffffff; }

        .appointment-message { border-radius: .8rem; font-size: .87rem; line-height: 1.45; padding: .85rem; }
        .appointment-message.success { background: #ecfdf5; color: #047857; }
        .appointment-message.error { background: #fef2f2; color: #b91c1c; }
        .appointment-message.warning { background: #fff7ed; color: #9a3412; }

        .summary-card {
            align-items: center;
            display: grid;
            gap: .85rem;
        }
        .summary-content { align-items: start; display: flex; gap: .75rem; min-width: 0; }
        .summary-label { color: var(--appt-muted); display: block; font-size: .84rem; }
        .summary-title { display: block; font-size: 1rem; font-weight: 650; line-height: 1.3; margin-top: .12rem; }
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
            font-weight: 600;
            gap: .42rem;
            justify-content: center;
            min-height: 2.55rem;
            padding: .68rem .9rem;
            transition: filter .16s ease, opacity .16s ease;
            width: 100%;
        }
        .appointment-btn svg { height: 1rem; width: 1rem; }
        .appointment-btn:disabled { cursor: not-allowed; opacity: .52; }
        .appointment-btn:not(:disabled):hover { filter: brightness(.97); }

        @media (min-width: 760px) {
            .appointment-shell { gap: 1.25rem; padding: 2rem 1.25rem; }
            .appointment-context { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .date-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .summary-card { grid-template-columns: minmax(0, 1fr) 14rem; }
            .appointment-btn { width: auto; }
        }
    </style>
</head>
<body>
    @php
        $procedureName = $procedure?->name ?? 'Valoración dental';
        $duration = app(\App\Services\SocialCrmSettingsService::class)->appointmentSlotDuration();
        $doctorName = $doctor?->name;
        $firstDate = collect($options)->pluck('datetime')->filter()->map(fn ($date) => \Carbon\Carbon::parse($date))->sort()->first();
        $lastDate = collect($options)->pluck('datetime')->filter()->map(fn ($date) => \Carbon\Carbon::parse($date))->sort()->last();
        $rangeLabel = $firstDate && $lastDate
            ? $firstDate->isoFormat('D MMM').($firstDate->isSameDay($lastDate) ? '' : ' - '.$lastDate->isoFormat('D MMM YYYY'))
            : 'Opciones disponibles';
    @endphp

    <main class="appointment-shell">
        <header class="appointment-hero">
            <div class="appointment-eyebrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v4"/><path d="M12 17v4"/><path d="M3 12h4"/><path d="M17 12h4"/><path d="m5.6 5.6 2.8 2.8"/><path d="m15.6 15.6 2.8 2.8"/><path d="m18.4 5.6-2.8 2.8"/><path d="m8.4 15.6-2.8 2.8"/></svg>
                Clínica Dental
            </div>
            <h1>Agenda tu cita</h1>
            <p class="appointment-copy">Selecciona uno de los horarios disponibles que te ofrecimos por WhatsApp. Validaremos disponibilidad antes de registrar la cita.</p>
        </header>

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
            @elseif (! $hasAvailableOptions)
                <div class="appointment-message warning">Estos horarios ya no están disponibles. Escríbenos por WhatsApp para generar nuevas opciones.</div>
            @else
                <div class="schedule-head">
                    <div>
                        <span class="schedule-kicker">Opciones ofrecidas</span>
                        <span class="schedule-title">{{ $rangeLabel }}</span>
                    </div>
                    <span class="schedule-count">{{ count($options) }} horarios</span>
                </div>

                <div class="date-grid">
                    @foreach ($groups as $group)
                        @php $date = \Carbon\Carbon::parse($group['date']); @endphp
                        <div class="date-card {{ $date->isToday() ? 'is-today' : '' }}">
                            <span class="date-name">{{ $date->isoFormat('ddd') }}</span>
                            <span class="date-value">{{ $date->isoFormat('D MMM') }}</span>
                            <div class="slot-grid">
                                @foreach ($group['options'] as $option)
                                    @php $slot = \Carbon\Carbon::parse($option['datetime']); @endphp
                                    <button
                                        class="slot-btn"
                                        type="button"
                                        data-option="{{ $option['index'] }}"
                                        data-summary="{{ $slot->isoFormat('dddd D [de] MMMM') }} · {{ $slot->format('g:i A') }}"
                                        data-meta="{{ trim(($doctorName ? $doctorName.' · ' : '').$procedureName) }}"
                                    >
                                        {{ $slot->format('g:i A') }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        @if (! $expired && $hasAvailableOptions && ! session('appointment_success'))
            <form class="appointment-card summary-card" method="POST" action="{{ route('social-appointments.confirm', ['token' => $offer->token]) }}" id="appointment-confirm-form">
                @csrf
                <input type="hidden" name="option" id="selected-option" value="">
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
    </main>

    <script>
        (function () {
            var buttons = document.querySelectorAll('.slot-btn');
            var input = document.getElementById('selected-option');
            var summary = document.getElementById('selected-summary');
            var meta = document.getElementById('selected-meta');
            var confirmButton = document.getElementById('confirm-button');

            buttons.forEach(function (button) {
                button.addEventListener('click', function () {
                    buttons.forEach(function (candidate) { candidate.classList.remove('is-selected'); });
                    button.classList.add('is-selected');

                    input.value = button.dataset.option;
                    summary.textContent = button.dataset.summary;
                    meta.textContent = button.dataset.meta;
                    confirmButton.disabled = false;
                });
            });
        })();
    </script>
</body>
</html>
