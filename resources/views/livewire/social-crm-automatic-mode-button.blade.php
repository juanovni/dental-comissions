<div class="crm-automatic-mode">
    <style>
        .crm-automatic-mode {
            align-items: center;
            display: inline-flex;
        }

        .crm-automatic-mode-button {
            align-items: center;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: .625rem;
            color: #4b5563;
            cursor: pointer;
            display: inline-flex;
            height: 2.25rem;
            justify-content: center;
            position: relative;
            transition: background-color .14s ease, border-color .14s ease, color .14s ease;
            width: 2.25rem;
        }

        .crm-automatic-mode-button:hover,
        .crm-automatic-mode-button:focus {
            background: #f9fafb;
            border-color: #14b8a6;
            color: #0f766e;
            outline: none;
        }

        .crm-automatic-mode-button.is-active {
            background: #f0fdfa;
            border-color: #14b8a6;
            color: #0f766e;
        }

        .crm-automatic-mode-button svg {
            height: 1.1rem;
            width: 1.1rem;
        }

        .crm-automatic-mode-button.is-active svg {
            animation: crm-automatic-mode-pulse 1.8s ease-in-out infinite;
        }

        .crm-automatic-mode-indicator {
            background: #22c55e;
            border: 2px solid #ffffff;
            border-radius: 999px;
            height: .7rem;
            animation: crm-automatic-mode-dot 1.8s ease-in-out infinite;
            position: absolute;
            right: -.16rem;
            top: -.16rem;
            width: .7rem;
        }

        @keyframes crm-automatic-mode-pulse {
            0%, 100% {
                opacity: 1;
            }

            50% {
                opacity: .72;
            }
        }

        @keyframes crm-automatic-mode-dot {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(34, 197, 94, .38);
            }

            50% {
                box-shadow: 0 0 0 .28rem rgba(34, 197, 94, 0);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .crm-automatic-mode-button.is-active svg,
            .crm-automatic-mode-indicator {
                animation: none;
            }

            .crm-automatic-mode-icon {
                transform: none !important;
            }
        }

        .dark .crm-automatic-mode-button {
            background: rgba(15, 23, 42, .92);
            border-color: rgba(148, 163, 184, .18);
            color: #e5e7eb;
        }

        .dark .crm-automatic-mode-button:hover,
        .dark .crm-automatic-mode-button:focus,
        .dark .crm-automatic-mode-button.is-active {
            background: rgba(45, 212, 191, .12);
            border-color: rgba(45, 212, 191, .42);
            color: #2dd4bf;
        }

        .dark .crm-automatic-mode-indicator {
            border-color: #020617;
        }
    </style>

    <button
        @class([
            'crm-automatic-mode-button group',
            'is-active' => $this->isAutomaticModeActive(),
        ])
        type="button"
        wire:click="toggleAutomaticMode"
        wire:loading.attr="disabled"
        wire:target="toggleAutomaticMode"
        aria-label="{{ $this->isAutomaticModeActive() ? 'Desactivar modo automático CRM' : 'Activar modo automático CRM' }}"
        title="{{ $this->isAutomaticModeActive() ? 'Desactivar modo automático' : 'Activar modo automático' }}"
    >
        <svg class="crm-automatic-mode-icon transition-transform duration-200 group-hover:-rotate-12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 8V4H8"></path>
            <rect width="16" height="12" x="4" y="8" rx="2"></rect>
            <path d="M2 14h2"></path>
            <path d="M20 14h2"></path>
            <path d="M15 13v2"></path>
            <path d="M9 13v2"></path>
        </svg>

        @if ($this->isAutomaticModeActive())
            <span class="crm-automatic-mode-indicator" aria-hidden="true"></span>
        @endif
    </button>
</div>
