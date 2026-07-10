<x-filament-panels::page>
    @php
        $meta = $this->metaStats();
        $metaStatus = $meta['connected'] ? 'Conectado' : 'No conectado';
        $metaStatusClass = $meta['connected'] ? 'is-connected' : 'is-pending';
        $comingSoon = [
            [
                'name' => 'TikTok',
                'tagline' => 'Captura comentarios y senales comerciales desde videos de TikTok.',
                'logo' => 'TT',
                'tone' => 'tiktok',
                'image' => asset('images/integrations/tiktok-icon.webp'),
                'status' => 'Proximamente',
            ],
            [
                'name' => 'WhatsApp Cloud API',
                'tagline' => 'Conecta conversaciones y automatiza la recepcion de actividades clinicas.',
                'logo' => 'WA',
                'tone' => 'whatsapp',
                'image' => asset('images/integrations/whatsapp-icon.avif'),
                'status' => 'Configurado por sistema',
            ],
            [
                'name' => 'Google Business Profile',
                'tagline' => 'Centraliza resenas, reputacion local y consultas de pacientes.',
                'logo' => 'G',
                'tone' => 'google',
                'image' => asset('images/integrations/google-my-business-icon.webp'),
                'status' => 'Proximamente',
            ],
        ];
    @endphp

    <style>
        .integrations-page {
            --integrations-ink: #0f172a;
            --integrations-muted: #64748b;
            --integrations-line: #e5e7eb;
            --integrations-card: #ffffff;
            color: var(--integrations-ink);
            margin-top: -.25rem;
        }

        .integrations-hero {
            margin-bottom: 1.55rem;
        }

        .integrations-kicker {
            color: oklch(55% .12 185);
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .08em;
            margin-bottom: .4rem;
            text-transform: uppercase;
        }

        .integrations-subtitle {
            color: var(--integrations-muted);
            font-size: .92rem;
            line-height: 1.55;
            margin: .65rem 0 0;
        }

        .integrations-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: minmax(0, 1fr);
        }

        @media (min-width: 960px) {
            .integrations-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1320px) {
            .integrations-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .integration-card {
            background: var(--integrations-card);
            border: 1px solid var(--integrations-line);
            border-radius: .95rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .035);
            display: flex;
            flex-direction: column;
            min-height: 18.4rem;
            overflow: hidden;
            position: relative;
            transition: border-color .16s ease, box-shadow .16s ease;
        }

        .integration-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .055);
        }

        .integration-body {
            flex: 1;
            padding: 1.75rem 1.75rem 1.55rem;
        }

        .integration-top {
            align-items: start;
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 2.05rem;
        }

        .integration-logo {
            align-items: center;
            border-radius: .7rem;
            color: #ffffff;
            display: inline-flex;
            font-size: .74rem;
            font-weight: 850;
            height: 2.85rem;
            justify-content: center;
            letter-spacing: -.04em;
            width: 2.85rem;
        }

        .integration-external {
            align-items: center;
            border: 1px solid transparent;
            border-radius: .45rem;
            color: #64748b;
            display: inline-flex;
            height: 1.85rem;
            justify-content: center;
            text-decoration: none;
            transition: background .16s ease, border-color .16s ease, color .16s ease;
            width: 1.85rem;
        }

        .integration-external:hover {
            background: #f8fafc;
            border-color: #e5e7eb;
            color: oklch(55% .12 185);
        }

        .integration-external svg {
            height: 1rem;
            width: 1rem;
        }

        .integration-logo.meta {
            background: none;
            padding: 0;
        }

        .integration-logo.tiktok { background: none; padding: 0; }
        .integration-logo.whatsapp { background: none; padding: 0; }
        .integration-logo.google { background: none; padding: 0; }

        .integration-status {
            align-items: center;
            border-radius: .45rem;
            display: inline-flex;
            font-size: .68rem;
            font-weight: 500;
            gap: .4rem;
            padding: .34rem .52rem;
        }

        .integration-body > .integration-status {
            margin-bottom: .85rem;
        }

        .integration-status::before {
            border-radius: 999px;
            content: '';
            height: .46rem;
            width: .46rem;
        }

        .integration-status.is-connected {
            background: #ecfdf5;
            color: #047857;
        }

        .integration-status.is-connected::before { background: #10b981; }

        .integration-status.is-pending {
            background: #f8fafc;
            color: #475569;
        }

        .integration-status.is-pending::before { background: #94a3b8; }

        .integration-name {
            color: var(--integrations-ink);
            font-size: 1.02rem;
            font-weight: 750;
            letter-spacing: -.025em;
            margin: 0 0 .75rem;
        }

        .integration-copy {
            color: #475569;
            font-size: .9rem;
            line-height: 1.55;
            margin: 0;
        }

        .integration-footer {
            align-items: center;
            background: #fcfcfd;
            border-top: 1px solid var(--integrations-line);
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
            justify-content: space-between;
            padding: 1rem 1.45rem;
        }

        .integration-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .integration-btn {
            align-items: center;
            border-radius: .48rem;
            display: inline-flex;
            font-size: .78rem;
            font-weight: 500;
            gap: .4rem;
            justify-content: center;
            line-height: 1;
            min-height: 2.05rem;
            padding: .56rem .72rem;
            text-decoration: none;
            transition: background-color .16s ease, border-color .16s ease, filter .16s ease;
        }

        .integration-btn:hover {
            filter: brightness(.98);
        }

        .integration-btn.primary {
            background: oklch(55% .12 185);
            color: #ffffff;
        }

        .integration-btn.soft {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            color: #334155;
        }

        .integration-btn.ghost {
            background: transparent;
            border: 1px dashed #cbd5e1;
            color: #64748b;
            cursor: not-allowed;
        }

        .integration-sync {
            background: transparent;
            border: 1px solid #e5e7eb;
            color: #334155;
            cursor: pointer;
        }

        .integration-switch {
            align-items: center;
            background: #e2e8f0;
            border-radius: 999px;
            display: inline-flex;
            height: 1.25rem;
            justify-content: flex-start;
            padding: .15rem;
            width: 2.35rem;
        }

        .integration-switch span {
            background: #ffffff;
            border-radius: 999px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .16);
            height: .95rem;
            width: .95rem;
        }

        .integration-switch.is-on {
            background: oklch(55% .12 185);
            justify-content: flex-end;
        }

        .dark .integrations-page {
            --integrations-ink: #e5e7eb;
            --integrations-muted: #94a3b8;
            --integrations-line: rgba(148, 163, 184, .16);
            --integrations-card: rgba(15, 23, 42, .76);
        }

        .dark .integration-card,
        .dark .integration-footer {
            background: rgba(15, 23, 42, .82);
        }

        .dark .integration-copy,
        .dark .integrations-subtitle {
            color: #94a3b8;
        }

        .dark .integration-btn.soft,
        .dark .integration-sync,
        .dark .integration-external {
            background: rgba(15, 23, 42, .9);
            border-color: rgba(148, 163, 184, .18);
            color: #cbd5e1;
        }

        .dark .integration-external:hover {
            background: rgba(30, 41, 59, .9);
            color: oklch(68% .105 185);
        }
    </style>

    <section class="integrations-page">
        
        <div class="integrations-grid">
            <article class="integration-card">
                <div class="integration-body">
                    <div class="integration-top">
                        <img class="integration-logo meta" src="{{ asset('images/integrations/meta-icon.png') }}" alt="Meta" width="46" height="46">
                        <a class="integration-external" href="{{ $meta['accounts_url'] }}" aria-label="Abrir cuentas Meta">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M14 3h7v7" />
                                <path d="M10 14 21 3" />
                                <path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5" />
                            </svg>
                        </a>
                    </div>

                    <span class="integration-status {{ $metaStatusClass }}">{{ $metaStatus }}</span>

                    <h3 class="integration-name">Facebook e Instagram</h3>
                    <p class="integration-copy">
                        Conecta paginas de Facebook e Instagram Business para sincronizar publicaciones, comentarios y leads desde Meta Graph API.
                    </p>

                </div>

                <footer class="integration-footer">
                    <div class="integration-actions">
                        <a class="integration-btn primary" href="{{ $meta['connect_url'] }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width:.9rem;height:.9rem"><path d="M13.828 10.172a4 4 0 0 0-5.656 0l-4 4a4 4 0 1 0 5.656 5.656l1.102-1.101m-.758-4.899a4 4 0 0 0 5.656 0l4-4a4 4 0 0 0-5.656-5.656l-1.1 1.1"/></svg>
                            <span>{{ $meta['connected'] ? 'Reconectar' : 'Conectar' }}</span>
                        </a>
                        <a class="integration-btn soft" href="{{ $meta['accounts_url'] }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width:.9rem;height:.9rem"><path d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                            <span>Ver cuentas</span>
                        </a>
                        @if ($meta['connected'])
                            <button class="integration-btn integration-sync" type="button" wire:click="syncMeta" wire:loading.attr="disabled" wire:target="syncMeta">
                                <span wire:loading.remove wire:target="syncMeta">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width:.9rem;height:.9rem"><path d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/></svg>
                                    <span>Sincronizar</span>
                                </span>
                                <span wire:loading wire:target="syncMeta">Sincronizando...</span>
                            </button>
                        @endif
                    </div>
                    <span class="integration-switch {{ $meta['connected'] ? 'is-on' : '' }}" aria-hidden="true"><span></span></span>
                </footer>
            </article>

            @foreach ($comingSoon as $integration)
                <article class="integration-card">
                    <div class="integration-body">
                        <div class="integration-top">
                            @isset($integration['image'])
                                <img class="integration-logo {{ $integration['tone'] }}" src="{{ $integration['image'] }}" alt="{{ $integration['name'] }}" width="46" height="46">
                            @else
                                <div class="integration-logo {{ $integration['tone'] }}">{{ $integration['logo'] }}</div>
                            @endisset
                            <span class="integration-external" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 3h7v7" />
                                    <path d="M10 14 21 3" />
                                    <path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5" />
                                </svg>
                            </span>
                        </div>

                        <span class="integration-status is-pending">{{ $integration['status'] }}</span>

                        <h3 class="integration-name">{{ $integration['name'] }}</h3>
                        <p class="integration-copy">{{ $integration['tagline'] }}</p>
                    </div>

                    <footer class="integration-footer">
                        <div class="integration-actions">
                            <span class="integration-btn ghost">Proximamente</span>
                        </div>
                        <span class="integration-switch" aria-hidden="true"><span></span></span>
                    </footer>
                </article>
            @endforeach
        </div>
    </section>
</x-filament-panels::page>
