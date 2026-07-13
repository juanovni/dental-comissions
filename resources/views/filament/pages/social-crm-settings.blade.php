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

        .crm-settings-guide-page .crm-pill-picker .fi-fo-checkbox-list {
            gap: .5rem;
        }

        .crm-settings-guide-page .crm-pill-picker label {
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            min-height: 2.25rem;
            padding: .45rem .75rem;
            transition: background-color .16s ease, border-color .16s ease, color .16s ease;
        }

        .crm-settings-guide-page .crm-pill-picker label:has(input:checked) {
            background: oklch(55% .12 185);
            border-color: oklch(55% .12 185);
            color: #ffffff;
        }

        .crm-settings-guide-page .crm-pill-picker label:has(input:checked) * {
            color: #ffffff !important;
        }

        .crm-settings-guide-page .crm-pill-picker label:has(input:checked) input {
            accent-color: #ffffff;
        }

        .crm-settings-guide-page .crm-pill-picker label:has(input:focus-visible) {
            outline: 2px solid oklch(68% .105 185);
            outline-offset: 2px;
        }

        .crm-settings-guide-page .crm-schedule-general,
        .crm-settings-guide-page .crm-day-block {
            border-color: rgba(15, 118, 110, .24);
            border-radius: .85rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        }

        .crm-settings-guide-page .crm-schedule-general {
            background: #f0fdfa;
        }

        .crm-settings-guide-page .crm-day-blocks-section {
            border-color: transparent;
            box-shadow: none;
        }

        .crm-settings-guide-page .crm-day-blocks-section > div {
            padding-left: 0;
            padding-right: 0;
        }

        .crm-settings-guide-page .crm-day-blocks {
            gap: .85rem;
        }

        .crm-settings-guide-page .crm-day-block {
            background: #ffffff;
            min-height: 13rem;
        }

        .crm-settings-guide-page .crm-day-block.is-disabled {
            background: #f8fafc;
            border-color: #cbd5e1;
            border-style: dashed;
        }

        .crm-settings-guide-page .crm-day-block.is-disabled .crm-time-field {
            opacity: .62;
        }

        .crm-settings-guide-page .crm-time-field input {
            min-height: 2.35rem;
        }

        .crm-settings-guide-page .crm-day-block .fi-section-header-description,
        .crm-settings-guide-page .crm-day-blocks-section .fi-section-header-description,
        .crm-settings-guide-page .crm-schedule-general .fi-section-header-description {
            color: #475569;
            font-size: .82rem;
            font-weight: 400;
            line-height: 1.45;
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

        .dark .crm-settings-guide-page .crm-pill-picker label {
            border-color: rgba(148, 163, 184, .35);
        }

        .dark .crm-settings-guide-page .crm-schedule-general {
            background: rgba(20, 184, 166, .08);
            border-color: rgba(45, 212, 191, .18);
        }

        .dark .crm-settings-guide-page .crm-day-block {
            background: rgba(15, 23, 42, .56);
            border-color: rgba(45, 212, 191, .18);
        }

        .dark .crm-settings-guide-page .crm-day-block.is-disabled {
            background: rgba(15, 23, 42, .34);
            border-color: rgba(148, 163, 184, .26);
        }

        .dark .crm-settings-guide-page .crm-day-block .fi-section-header-description,
        .dark .crm-settings-guide-page .crm-day-blocks-section .fi-section-header-description,
        .dark .crm-settings-guide-page .crm-schedule-general .fi-section-header-description {
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
