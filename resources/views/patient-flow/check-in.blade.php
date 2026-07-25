<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirma tu llegada</title>
    <style>
        :root { color-scheme: light; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        body { background: #f8fafc; color: #0f172a; margin: 0; min-height: 100vh; }
        .check-shell { display: grid; grid-template-rows: auto 1fr auto; min-height: 100vh; }
        .check-header, .check-footer { align-items: center; border-color: #e5e7eb; display: flex; justify-content: center; padding: 1rem; }
        .check-header { background: #fff; border-bottom: 1px solid #e5e7eb; flex-direction: column; gap: .55rem; }
        .check-footer { border-top: 1px solid #e5e7eb; color: #475569; font-size: .78rem; }
        .check-logo { align-items: center; background: #0f8f7f; border-radius: 1rem; color: #fff; display: inline-flex; font-weight: 700; height: 3rem; justify-content: center; width: 3rem; }
        .check-main { align-items: center; display: flex; justify-content: center; padding: 2rem 1rem; }
        .check-card { background: #fff; border: 1px solid #dbe3ea; border-radius: 1rem; box-shadow: 0 1px 3px rgba(15, 23, 42, .08); display: grid; gap: 1rem; max-width: 32rem; padding: 1.75rem; width: 100%; }
        .check-card-success { background: #dcfce7; border-color: #86efac; text-align: center; }
        h1 { font-size: 1.45rem; line-height: 1.2; margin: 0; }
        p { color: #475569; font-size: .98rem; line-height: 1.55; margin: 0; }
        label { display: grid; gap: .35rem; font-size: .82rem; font-weight: 650; }
        input { border: 1px solid #0f8f7f; border-radius: .65rem; font-size: 1rem; height: 3.2rem; outline: none; padding: 0 .85rem; }
        button { background: #0f8f7f; border: 0; border-radius: .65rem; color: #fff; cursor: pointer; font-size: 1rem; font-weight: 700; min-height: 3.2rem; padding: .75rem 1rem; }
        .check-help { background: transparent; color: #475569; font-size: .85rem; font-weight: 600; text-align: center; text-decoration: none; }
        .check-facts { background: #fff; border-radius: .75rem; display: grid; gap: .55rem; padding: .85rem; text-align: left; }
        .check-fact { display: flex; justify-content: space-between; gap: 1rem; }
        .check-fact span:first-child { color: #64748b; }
        .check-fact span:last-child { font-weight: 700; }
        .check-options { display: grid; gap: .55rem; }
        .check-option { background: #fff; border: 1px solid #e5e7eb; border-radius: .65rem; color: #0f172a; display: flex; justify-content: space-between; min-height: 2.8rem; }
        @media (max-width: 640px) { .check-card { padding: 1.25rem; } }
    </style>
</head>
<body>
    <div class="check-shell">
        <header class="check-header">
            <div class="check-logo">+</div>
            <strong>Clínica Dental odonCRM</strong>
        </header>

        <main class="check-main">
            @if (($status['type'] ?? null) === 'success')
                <section class="check-card check-card-success">
                    <div class="check-logo" style="margin: 0 auto; border-radius: 999px;">✓</div>
                    <h1>{{ $status['title'] }}</h1>
                    <p>{{ $status['message'] }}</p>
                    <div class="check-facts">
                        <div class="check-fact"><span>Estado</span><span>{{ $status['state'] }}</span></div>
                        <div class="check-fact"><span>Actualizado</span><span>{{ $status['updated'] }}</span></div>
                        @if (! empty($status['time']))<div class="check-fact"><span>Hora</span><span>{{ $status['time'] }}</span></div>@endif
                        @if (! empty($status['doctor']))<div class="check-fact"><span>Doctor</span><span>{{ $status['doctor'] }}</span></div>@endif
                    </div>
                    <p>Toma asiento, en breve seras llamado.</p>
                </section>
            @else
                <section class="check-card">
                    <h1>{{ $status['title'] ?? 'Confirma tu llegada' }}</h1>
                    <p>{{ $status['message'] ?? 'Ingresa tu telefono o codigo de cita para avisar a recepcion.' }}</p>

                    @if (($status['type'] ?? null) === 'multiple' && $appointments !== [])
                        <div class="check-options">
                            @foreach ($appointments as $appointment)
                                <form method="POST" action="{{ route('patient-flow.check-in.store', ['clinicSlug' => $clinicSlug]) }}">
                                    @csrf
                                    <input type="hidden" name="appointment_id" value="{{ $appointment['id'] }}">
                                    <button type="submit" class="check-option"><span>{{ $appointment['time'] }}</span><span>{{ $appointment['doctor'] ?? 'Doctor' }}</span></button>
                                </form>
                            @endforeach
                        </div>
                    @else
                        <form method="POST" action="{{ route('patient-flow.check-in.store', ['clinicSlug' => $clinicSlug]) }}" style="display: grid; gap: .9rem;">
                            @csrf
                            <label>Telefono o codigo
                                <input name="identifier" value="{{ old('identifier') }}" placeholder="+52 555 123 4567" autocomplete="tel">
                            </label>
                            @error('identifier') <p style="color:#dc2626;">{{ $message }}</p> @enderror
                            <button type="submit">Ya llegue</button>
                        </form>
                    @endif

                    <a class="check-help" href="javascript:history.back()">Necesito ayuda de recepcion</a>
                </section>
            @endif
        </main>

        <footer class="check-footer">odonCRM · Patient Flow</footer>
    </div>
</body>
</html>
