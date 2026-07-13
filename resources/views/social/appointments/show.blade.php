<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agenda tu cita</title>
    <style>
        :root { color-scheme: light; font-family: Aptos, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        body { background: #f8fafc; color: #0f172a; margin: 0; }
        .appointment-shell { margin: 0 auto; max-width: 34rem; min-height: 100vh; padding: 1rem; }
        .appointment-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1.1rem; box-shadow: 0 18px 44px -34px rgba(15,23,42,.45); overflow: hidden; }
        .appointment-header { border-bottom: 1px solid #e2e8f0; padding: 1.1rem; }
        .appointment-eyebrow { color: #0f766e; font-size: .76rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        h1 { font-size: 1.35rem; line-height: 1.15; margin: .35rem 0 0; }
        .appointment-copy { color: #475569; font-size: .9rem; line-height: 1.45; margin: .55rem 0 0; }
        .appointment-body { display: grid; gap: .75rem; padding: 1rem; }
        .appointment-option { align-items: center; border: 1px solid #e2e8f0; border-radius: .85rem; display: flex; gap: .75rem; justify-content: space-between; padding: .85rem; }
        .appointment-option strong { display: block; font-size: .94rem; }
        .appointment-option span { color: #64748b; display: block; font-size: .78rem; margin-top: .15rem; }
        .appointment-btn { align-items: center; background: oklch(55% .12 185); border: 0; border-radius: .6rem; color: #fff; cursor: pointer; display: inline-flex; font-size: .82rem; font-weight: 700; gap: .35rem; min-height: 2.2rem; padding: .55rem .75rem; }
        .appointment-message { border-radius: .8rem; font-size: .86rem; line-height: 1.4; padding: .8rem; }
        .appointment-message.success { background: #ecfdf5; color: #047857; }
        .appointment-message.error { background: #fef2f2; color: #b91c1c; }
        .appointment-expired { background: #fff7ed; color: #9a3412; }
    </style>
</head>
<body>
    <main class="appointment-shell">
        <section class="appointment-card">
            <header class="appointment-header">
                <div class="appointment-eyebrow">Clínica Dental</div>
                <h1>Agenda tu cita</h1>
                <p class="appointment-copy">
                    Elige uno de los horarios disponibles para {{ $offer->socialComment->suggestedProcedure?->name ?? 'tu valoración dental' }}.
                </p>
            </header>

            <div class="appointment-body">
                @if (session('appointment_success'))
                    <div class="appointment-message success">{{ session('appointment_success') }}</div>
                @endif

                @if (session('appointment_error'))
                    <div class="appointment-message error">{{ session('appointment_error') }}</div>
                @endif

                @if ($expired)
                    <div class="appointment-message appointment-expired">
                        Este enlace ya expiró o fue utilizado. Escríbenos por WhatsApp para generar nuevas opciones.
                    </div>
                @else
                    @foreach ($options as $option)
                        <form class="appointment-option" method="POST" action="{{ route('social-appointments.confirm', ['token' => $offer->token]) }}">
                            @csrf
                            <input type="hidden" name="option" value="{{ $option['index'] }}">
                            <div>
                                <strong>{{ \Carbon\Carbon::parse($option['datetime'])->isoFormat('dddd D [de] MMMM') }}</strong>
                                <span>{{ \Carbon\Carbon::parse($option['datetime'])->format('g:i A') }}</span>
                            </div>
                            <button class="appointment-btn" type="submit">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width:.9rem;height:.9rem"><path d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Confirmar
                            </button>
                        </form>
                    @endforeach
                @endif
            </div>
        </section>
    </main>
</body>
</html>
