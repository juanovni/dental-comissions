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

        .gcal-integration-page .fi-header > div {
            max-width: none;
            width: 100%;
        }

        .gcal-integration-page .fi-header-subheading {
            max-width: min(100%, 86rem);
            width: 100%;
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
            background: oklch(55% .12 185);
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
                                <span wire:loading.remove wire:target="disconnect({{ $doctor['id'] }})">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width:.9rem;height:.9rem"><path d="M9.143 17.082a24.248 24.248 0 0 0 3.844.148m-3.844-.148a24.447 24.447 0 0 1-5.394-2.117m5.394 2.117L5.25 18.75m7.737-1.668a24.248 24.248 0 0 1 3.844-.148m-3.844.148 1.063 1.518m-11.727-9.81a24.248 24.248 0 0 0 1.58-2.92m12.39 2.92a24.305 24.305 0 0 0 3.058-6.72m-16.447 7.8a24.04 24.04 0 0 1-1.28-3.29m13.688 7.8a24.099 24.099 0 0 0 1.28-3.29M12 6v3m0 0v3m0-3h3m-3 0H9"/></svg>
                                    <span>Desconectar</span>
                                </span>
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
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width:.9rem;height:.9rem"><path d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                            <span>Conectar</span>
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
