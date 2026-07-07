<x-filament-panels::page>
    @php
        $doctors = $this->getDoctors();
        $connected = collect($doctors)->filter(fn($d) => $d['is_connected']);
        $disconnected = collect($doctors)->filter(fn($d) => !$d['is_connected']);
    @endphp

    <style>
        .gcal-page {
            --gcal-ink: #0f172a;
            --gcal-muted: #64748b;
            --gcal-line: #e5e7eb;
            --gcal-card: #ffffff;
            color: var(--gcal-ink);
            margin-top: -.25rem;
        }

        .gcal-hero {
            margin-bottom: 1.55rem;
        }

        .gcal-kicker {
            color: #2563eb;
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .08em;
            margin-bottom: .4rem;
            text-transform: uppercase;
        }

        .gcal-subtitle {
            color: var(--gcal-muted);
            font-size: .92rem;
            line-height: 1.55;
            margin: .65rem 0 0;
        }

        .gcal-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: minmax(0, 1fr);
        }

        @media (min-width: 768px) {
            .gcal-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .gcal-doctor-card {
            background: var(--gcal-card);
            border: 1px solid var(--gcal-line);
            border-radius: .85rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .035);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: border-color .16s ease, box-shadow .16s ease;
        }

        .gcal-doctor-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(15, 23, 42, .05);
        }

        .gcal-card-body {
            flex: 1;
            padding: 1.25rem 1.25rem 1rem;
        }

        .gcal-card-header {
            align-items: center;
            display: flex;
            gap: .85rem;
            margin-bottom: .85rem;
        }

        .gcal-avatar {
            align-items: center;
            background: linear-gradient(135deg, #4285f4, #34a853);
            border-radius: 999px;
            color: #fff;
            display: inline-flex;
            font-size: .82rem;
            font-weight: 700;
            height: 2.4rem;
            justify-content: center;
            width: 2.4rem;
        }

        .gcal-doctor-name {
            font-size: 1rem;
            font-weight: 650;
            letter-spacing: -.02em;
            margin: 0;
        }

        .gcal-doctor-email {
            color: var(--gcal-muted);
            font-size: .8rem;
            margin: .1rem 0 0;
        }

        .gcal-status {
            align-items: center;
            border-radius: .4rem;
            display: inline-flex;
            font-size: .7rem;
            font-weight: 500;
            gap: .35rem;
            padding: .3rem .55rem;
        }

        .gcal-status::before {
            border-radius: 999px;
            content: '';
            height: .42rem;
            width: .42rem;
        }

        .gcal-status.connected {
            background: #ecfdf5;
            color: #047857;
        }

        .gcal-status.connected::before { background: #10b981; }

        .gcal-status.disconnected {
            background: #f8fafc;
            color: #475569;
        }

        .gcal-status.disconnected::before { background: #94a3b8; }

        .gcal-google-email {
            color: var(--gcal-muted);
            font-size: .78rem;
            margin-top: .55rem;
        }

        .gcal-google-email strong {
            color: var(--gcal-ink);
            font-weight: 600;
        }

        .gcal-card-footer {
            align-items: center;
            background: #fcfcfd;
            border-top: 1px solid var(--gcal-line);
            display: flex;
            gap: .5rem;
            justify-content: flex-end;
            padding: .85rem 1.25rem;
        }

        .gcal-btn {
            align-items: center;
            border-radius: .45rem;
            display: inline-flex;
            font-size: .78rem;
            font-weight: 500;
            gap: .4rem;
            justify-content: center;
            line-height: 1;
            min-height: 2rem;
            padding: .5rem .75rem;
            text-decoration: none;
            transition: background-color .16s ease, border-color .16s ease, filter .16s ease;
        }

        .gcal-btn:hover {
            filter: brightness(.97);
        }

        .gcal-btn.primary {
            background: #4285f4;
            color: #ffffff;
        }

        .gcal-btn.danger {
            background: #ef4444;
            color: #ffffff;
        }

        .gcal-empty {
            color: var(--gcal-muted);
            font-size: .9rem;
            padding: 2rem 0;
            text-align: center;
        }

        .gcal-section-title {
            font-size: .85rem;
            font-weight: 650;
            letter-spacing: -.01em;
            margin: 1.5rem 0 .75rem;
        }

        .dark .gcal-page {
            --gcal-ink: #e5e7eb;
            --gcal-muted: #94a3b8;
            --gcal-line: rgba(148, 163, 184, .16);
            --gcal-card: rgba(15, 23, 42, .76);
        }

        .dark .gcal-doctor-card,
        .dark .gcal-card-footer {
            background: rgba(15, 23, 42, .82);
        }

        .dark .gcal-btn.danger {
            background: #dc2626;
        }
    </style>

    <section class="gcal-page">
        <div class="gcal-hero">
            <p class="gcal-kicker">Google Calendar</p>
            <p class="gcal-subtitle">
                Conecta la cuenta de Google de cada doctor para consultar disponibilidad de horarios y evitar agendamientos conflictivos.
                Se requiere autorización individual con alcance de solo lectura.
            </p>
        </div>

        @if ($connected->isNotEmpty())
            <h3 class="gcal-section-title">Conectados</h3>
            <div class="gcal-grid">
                @foreach ($connected as $doctor)
                    <article class="gcal-doctor-card">
                        <div class="gcal-card-body">
                            <div class="gcal-card-header">
                                <div class="gcal-avatar">{{ substr($doctor['name'], 0, 2) }}</div>
                                <div>
                                    <h4 class="gcal-doctor-name">{{ $doctor['name'] }}</h4>
                                    <p class="gcal-doctor-email">{{ $doctor['email'] }}</p>
                                </div>
                            </div>
                            <span class="gcal-status connected">Conectado</span>
                            @if ($doctor['google_calendar_email'])
                                <p class="gcal-google-email">
                                    Cuenta: <strong>{{ $doctor['google_calendar_email'] }}</strong>
                                </p>
                            @endif
                        </div>
                        <footer class="gcal-card-footer">
                            <button
                                class="gcal-btn danger"
                                type="button"
                                wire:click="disconnect({{ $doctor['id'] }})"
                                wire:loading.attr="disabled"
                                wire:target="disconnect({{ $doctor['id'] }})"
                            >
                                <span wire:loading.remove wire:target="disconnect({{ $doctor['id'] }})">Desconectar</span>
                                <span wire:loading wire:target="disconnect({{ $doctor['id'] }})">Desconectando...</span>
                            </button>
                        </footer>
                    </article>
                @endforeach
            </div>
        @endif

        <h3 class="gcal-section-title">Doctores disponibles</h3>
        <div class="gcal-grid">
            @forelse ($disconnected as $doctor)
                <article class="gcal-doctor-card">
                    <div class="gcal-card-body">
                        <div class="gcal-card-header">
                            <div class="gcal-avatar">{{ substr($doctor['name'], 0, 2) }}</div>
                            <div>
                                <h4 class="gcal-doctor-name">{{ $doctor['name'] }}</h4>
                                <p class="gcal-doctor-email">{{ $doctor['email'] }}</p>
                            </div>
                        </div>
                        <span class="gcal-status disconnected">No conectado</span>
                    </div>
                    <footer class="gcal-card-footer">
                        <a class="gcal-btn primary" href="{{ $doctor['connect_url'] }}">
                            Conectar
                        </a>
                    </footer>
                </article>
            @empty
                @if ($connected->isEmpty())
                    <p class="gcal-empty">No hay doctores registrados en el sistema.</p>
                @else
                    <p class="gcal-empty">Todos los doctores tienen Google Calendar conectado.</p>
                @endif
            @endforelse
        </div>
    </section>
</x-filament-panels::page>
