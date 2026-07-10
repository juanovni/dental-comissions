<x-filament-panels::page class="crm-settings-guide-page">
    <style>
        .crm-settings-guide-layout {
            display: grid;
            gap: .9rem;
            grid-template-columns: minmax(0, 1fr);
        }

        .crm-settings-guide-main {
            min-width: 0;
        }

        .crm-settings-guide-main [id] {
            scroll-margin-top: 5.75rem;
        }

        .crm-settings-guide-nav-title {
            color: #64748b;
            font-size: .76rem;
            font-weight: 600;
            margin: 0 0 .45rem;
            padding: 0 .35rem;
        }

        .crm-settings-guide-nav-list {
            display: grid;
            gap: .2rem;
        }

        .crm-settings-guide-nav-link {
            border-radius: .55rem;
            color: #334155;
            display: block;
            padding: .55rem .6rem;
            text-decoration: none;
            transition: background-color .16s ease, color .16s ease;
        }

        .crm-settings-guide-nav-link:hover,
        .crm-settings-guide-nav-link:focus {
            background: #f8fafc;
            color: #0f766e;
            outline: none;
        }

        .crm-settings-guide-nav-link.active {
            background: #f0fdfa;
            color: #0f766e;
        }

        .dark .crm-settings-guide-nav-link.active {
            background: rgba(45, 212, 191, .12);
            color: #2dd4bf;
        }

        .crm-settings-guide-nav-label {
            display: block;
            font-size: .84rem;
            font-weight: 600;
            line-height: 1.25;
        }

        .crm-settings-guide-nav-description {
            color: #64748b;
            display: block;
            font-size: .76rem;
            font-weight: 400;
            line-height: 1.35;
            margin-top: .15rem;
        }

        .crm-settings-guide-page .fi-header {
            display: none;
        }

        @media (min-width: 1024px) {
            .crm-settings-guide-layout {
                align-items: start;
                grid-template-columns: 16rem minmax(0, 1fr);
            }

            .crm-settings-guide-sidebar {
                position: sticky;
                top: 5rem;
            }
        }

        .dark .crm-settings-guide-nav {
            background: rgba(15, 23, 42, .82);
            border-color: rgba(148, 163, 184, .16);
        }

        .dark .crm-settings-guide-nav-title {
            color: #e5e7eb;
        }

        .dark .crm-settings-guide-nav-link {
            color: #cbd5e1;
        }

        .dark .crm-settings-guide-nav-link:hover,
        .dark .crm-settings-guide-nav-link:focus {
            background: rgba(255, 255, 255, .05);
            color: #2dd4bf;
        }

        .dark .crm-settings-guide-nav-description {
            color: #94a3b8;
        }
    </style>

    <div class="crm-settings-guide-layout">
        <aside class="crm-settings-guide-sidebar">
            <nav class="crm-settings-guide-nav" aria-label="Navegación de configuración CRM">
                <p class="crm-settings-guide-nav-title">
                    CONFIGURACIÓN DE CRM
                </p>

                <div class="crm-settings-guide-nav-list">
                    @foreach ($this->settingsNavigationItems() as $item)
                        <a
                            href="#{{ $item['id'] }}"
                            class="crm-settings-guide-nav-link"
                        >
                            <span class="crm-settings-guide-nav-label">{{ $item['label'] }}</span>
                            <span class="crm-settings-guide-nav-description">{{ $item['description'] }}</span>
                        </a>
                    @endforeach
                </div>
            </nav>
        </aside>

        <div class="crm-settings-guide-main">
            {{ $this->form }}
        </div>
    </div>

    <script>
        (function() {
            function highlightNav() {
                var hash = window.location.hash;
                var links = document.querySelectorAll('.crm-settings-guide-nav-link');
                links.forEach(function(link) {
                    link.classList.toggle('active', link.getAttribute('href') === hash);
                });
            }
            highlightNav();
            window.addEventListener('hashchange', highlightNav);
        })();
    </script>
</x-filament-panels::page>
