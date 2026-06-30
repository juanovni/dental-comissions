<x-filament-panels::page>
    @php
        $stats = $this->stats();
        $comments = $this->comments();
        $filters = [
            'leads' => ['label' => 'Leads', 'icon' => 'user-plus', 'count' => $stats['leads']],
            'crisis' => ['label' => 'Crisis', 'icon' => 'exclamation-triangle', 'count' => $stats['crisis']],
            'vip' => ['label' => 'Pacientes VIP', 'icon' => 'star', 'count' => $stats['vip']],
            'medical' => ['label' => 'Atencion Medica', 'icon' => 'heart', 'count' => $stats['medical']],
            'all' => ['label' => 'Activos', 'icon' => 'magnifying-glass', 'count' => $stats['all']],
            'archived' => ['label' => 'Archivados', 'icon' => 'archive-box', 'count' => $stats['archived']],
        ];
        $selectedComment = $this->selectedComment();
        $selectedPatient = $selectedComment?->socialIdentity?->patient ?: $selectedComment?->convertedPatient;
        $selectedTimeline = $selectedComment ? $this->timelineEvents($selectedComment->id) : [];
        $drawerConversation = $selectedComment ? $this->conversationEvents($selectedComment) : [];
    @endphp

    <style>
        .social-inbox-page {
            --inbox-accent: oklch(0.59 0.2 259.81);
            --inbox-ink: #0f172a;
            --inbox-muted: #64748b;
            --inbox-line: rgba(15, 23, 42, .08);
            --inbox-card: #ffffff;
            color: var(--inbox-ink);
            margin-top: -.25rem;
        }

        .social-inbox-toolbar {
            align-items: center;
            display: flex;
            gap: .75rem;
            justify-content: flex-end;
            margin-bottom: .9rem;
        }

        @media (min-width: 900px) {
            .social-inbox-toolbar {
                margin-top: -4.05rem;
            }
        }

        @media (max-width: 760px) {
            .social-inbox-toolbar {
                justify-content: stretch;
                margin-bottom: .9rem;
            }
        }

        .smart-search {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
            box-shadow: none;
            color: var(--pk-ink);
            font-size: .82rem;
            height: 2.35rem;
            outline: none;
            padding: .45rem .75rem;
            width: min(100%, 24rem);
        }

        .smart-filters {
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            flex-wrap: wrap;
            gap: 1.35rem;
            margin: 0 0 1rem;
        }

        .smart-filter {
            align-items: center;
            background: transparent;
            border: 0;
            border-bottom: 2px solid transparent;
            border-radius: 0;
            color: #4b5563;
            display: inline-flex;
            font-size: .82rem;
            font-weight: 500;
            gap: .3rem;
            margin-bottom: -1px;
            padding: .65rem .05rem .8rem;
            transition: border-color .18s ease, color .18s ease;
        }

        .smart-filter:hover {
            color: var(--inbox-accent);
        }

        .smart-filter.is-active {
            border-bottom-color: #111827;
            color: #111827;
        }

        .smart-filter-icon {
            color: #9ca3af;
            display: inline-flex;
            height: 1rem;
            width: 1rem;
            transition: color .18s ease;
        }

        .smart-filter-icon svg {
            height: 1rem;
            width: 1rem;
        }

        .smart-filter.is-active .smart-filter-icon {
            color: #111827;
        }

        .smart-filter-count {
            align-items: center;
            background: #f3f4f6;
            border-radius: .4rem;
            color: #475569;
            display: inline-flex;
            font-size: .7rem;
            font-weight: 500;
            height: 1.35rem;
            justify-content: center;
            min-width: 1.3rem;
            padding: 0 .35rem;
            transition: color .18s ease;
        }

        .smart-filter.is-active .smart-filter-count {
            color: #111827;
        }

        @media (max-width: 760px) {
            .smart-filters {
                gap: 1rem;
                overflow-x: auto;
                padding-bottom: .1rem;
            }

            .smart-filter {
                flex: 0 0 auto;
            }
        }

        .smart-grid {
            align-items: start;
            display: grid;
            gap: 1rem;
            grid-template-columns: minmax(0, 1fr);
        }

        @media (min-width: 1120px) {
            .smart-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .smart-list-column {
            max-height: calc(100vh - 12rem);
            overflow: auto;
            padding-right: .2rem;
        }

        .smart-drawer-backdrop {
            background: rgba(15, 23, 42, .22);
            inset: 0;
            position: fixed;
            z-index: 48;
            animation: drawerBackdropIn .14s ease;
        }

        .smart-drawer-backdrop.is-closing {
            animation: drawerBackdropOut .18s ease forwards;
        }

        .smart-drawer {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: .875rem;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .08);
            color: #0f172a;
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 2rem);
            overflow: hidden;
            position: fixed;
            right: 1rem;
            top: 1rem;
            width: min(38rem, calc(100vw - 2rem));
            z-index: 49;
            animation: drawerSlideIn .14s ease;
        }

        .smart-drawer.is-closing {
            animation: drawerSlideOut .2s ease forwards;
        }

        @keyframes drawerSlideIn {
            from { transform: translateX(.75rem); opacity: .4; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes drawerSlideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(.75rem); opacity: 0; }
        }

        @keyframes drawerBackdropIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes drawerBackdropOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        .smart-drawer-header {
            align-items: flex-start;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            gap: .75rem;
            justify-content: space-between;
            padding: 1rem;
            flex: 0 0 auto;
        }

        .smart-drawer-close {
            align-items: center;
            background: transparent;
            border: 1px solid transparent;
            border-radius: .45rem;
            color: #64748b;
            cursor: pointer;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 1rem;
            font-weight: 500;
            height: 2rem;
            justify-content: center;
            line-height: 1;
            transition: .14s ease;
            width: 2rem;
        }

        .smart-drawer-close:hover {
            background: #f9fafb;
            border-color: #e5e7eb;
            color: #0f172a;
        }

        .smart-drawer-title {
            color: #0f172a;
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.2;
            min-width: 0;
            overflow: visible;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .smart-drawer-subtitle {
            color: #64748b;
            font-size: .76rem;
            font-weight: 500;
            margin-top: .2rem;
            white-space: nowrap;
        }

        .smart-drawer-body {
            flex: 1 1 auto;
            overflow-y: auto;
            padding: 1rem;
            display: grid;
            gap: .8rem;
            align-content: start;
        }

        .smart-drawer-footer {
            border-top: 1px solid #e5e7eb;
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            padding: .9rem 1rem;
        }

        .smart-drawer-tabs {
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            gap: .85rem;
            overflow-x: auto;
            padding: 0 1rem;
        }

        .smart-drawer-tab {
            background: transparent;
            border: 0;
            border-bottom: 2px solid transparent;
            color: #64748b;
            cursor: pointer;
            flex: 0 0 auto;
            font-size: .76rem;
            font-weight: 500;
            padding: .7rem 0 .75rem;
            transition: border-color .14s ease, color .14s ease;
        }

        .smart-drawer-tab:hover,
        .smart-drawer-tab.is-active {
            border-bottom-color: #000000;
            color: #0f172a;
        }

        [x-cloak] { display: none !important; }

        .smart-drawer-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: .75rem;
            display: grid;
            gap: .55rem;
            padding: .85rem;
        }

        .smart-drawer-card.is-accent {
            border-color: #e5e7eb;
        }

        .smart-drawer-card.is-ai {
            background: #ffffff;
            border-color: #e5e7eb;
        }

        .smart-drawer-card-kicker {
            color: #0f172a;
            font-size: .78rem;
            font-weight: 600;
        }

        .smart-drawer-card.is-ai .smart-drawer-card-kicker {
            color: #0f172a;
        }

        .smart-drawer-card-title {
            color: #0f172a;
            font-size: .82rem;
            font-weight: 500;
            margin: 0;
        }

        .smart-drawer-card-text {
            color: #64748b;
            font-size: .82rem;
            font-weight: 500;
            line-height: 1.45;
            margin: 0;
            white-space: pre-line;
        }

        .smart-drawer-muted {
            color: #64748b !important;
        }

        .smart-drawer-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            padding-top: .35rem;
        }

        .smart-drawer-timeline {
            display: grid;
            gap: 0;
        }

        .smart-drawer-timeline-item {
            display: grid;
            gap: .75rem;
            grid-template-columns: 2rem minmax(0, 1fr);
            padding-bottom: 1rem;
            position: relative;
        }

        .smart-drawer-timeline-item:not(:last-child)::before {
            background: #e5e7eb;
            bottom: 0;
            content: '';
            left: .95rem;
            position: absolute;
            top: 2rem;
            width: 1px;
        }

        .smart-drawer-timeline-item:last-child {
            padding-bottom: 0;
        }

        .smart-activity-icon {
            align-items: center;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            color: #64748b;
            display: inline-flex;
            height: 2rem;
            justify-content: center;
            position: relative;
            width: 2rem;
            z-index: 1;
        }

        .smart-activity-icon svg {
            height: .95rem;
            width: .95rem;
            color: #000000;
        }

        .smart-activity-icon.blue,
        .smart-activity-icon.indigo,
        .smart-activity-icon.cyan { color: #2563eb; }

        .smart-activity-icon.orange { color: #f97316; }
        .smart-activity-icon.green,
        .smart-activity-icon.teal,
        .smart-activity-icon.emerald { color: #0f766e; }

        .smart-activity-title {
            align-items: center;
            color: #0f172a;
            display: flex;
            gap: .4rem;
            font-size: .84rem;
            font-weight: 500;
            line-height: 1.35;
        }

        .smart-activity-meta {
            color: #64748b;
            font-size: .74rem;
            margin-top: .15rem;
        }

        .smart-activity-note {
            color: #64748b;
            font-size: .8rem;
            line-height: 1.45;
            margin: .35rem 0 0;
        }

        .smart-conversation-bubble {
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: .625rem;
            color: #111827;
            font-size: .82rem;
            line-height: 1.45;
            margin-top: .45rem;
            padding: .7rem;
            white-space: pre-line;
        }

        .smart-conversation-summary {
            background: #ffffff;
            border: 1px solid #eef2f7;
            border-radius: .75rem;
            margin-bottom: .85rem;
            overflow: hidden;
        }

        .smart-conversation-route {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
            justify-content: space-between;
            padding: .65rem .75rem;
        }

        .smart-conversation-route-main {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
        }

        .smart-conversation-route-arrow,
        .smart-conversation-summary-date {
            color: #64748b;
            font-size: .78rem;
        }

        .smart-conversation-metrics {
            border-top: 1px solid #eef2f7;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .smart-conversation-metric {
            padding: .65rem .75rem;
        }

        .smart-conversation-metric:not(:last-child) {
            border-right: 1px solid #eef2f7;
        }

        .smart-conversation-metric span {
            color: #64748b;
            display: block;
            font-size: .72rem;
        }

        .smart-conversation-metric strong {
            color: #0f172a;
            display: block;
            font-size: .94rem;
            line-height: 1.25;
            margin-top: .1rem;
        }

        .smart-conversation-content {
            min-width: 0;
            width: 100%;
        }

        .smart-conversation-head {
            align-items: flex-start;
            display: flex;
            gap: .75rem;
            justify-content: space-between;
        }

        .smart-conversation-kind {
            color: #64748b;
            font-size: .76rem;
            margin-top: .15rem;
        }

        .smart-conversation-time {
            color: #64748b;
            flex: 0 0 auto;
            font-size: .76rem;
            padding-top: .1rem;
        }

        .smart-conversation-rule {
            color: #64748b;
            font-size: .76rem;
            margin-top: .45rem;
        }

        .smart-channel-change {
            align-items: center;
            color: #64748b;
            display: flex;
            font-size: .76rem;
            gap: .45rem;
            margin: .45rem 0 .65rem;
        }

        .smart-channel-change svg {
            height: .9rem;
            width: .9rem;
        }

        .smart-activity-progress {
            align-items: center;
            display: flex;
            gap: .5rem;
            margin-top: .45rem;
        }

        .smart-activity-progress-track {
            background: #eef2f7;
            border-radius: 999px;
            flex: 1;
            height: .25rem;
            overflow: hidden;
        }

        .smart-activity-progress-fill {
            background: #28a79d;
            display: block;
            height: 100%;
        }

        .smart-activity-progress span:last-child {
            color: #64748b;
            font-size: .7rem;
            font-weight: 500;
        }

        .smart-activity-empty {
            color: #64748b;
            font-size: .82rem;
            margin: 0;
        }

        .smart-thermo-head {
            align-items: center;
            display: flex;
            gap: .75rem;
            justify-content: space-between;
        }

        .smart-thermo-score {
            color: #0f172a;
            font-size: .82rem;
            font-weight: 600;
        }

        .smart-thermo-track {
            background: #eef2f7;
            border-radius: 999px;
            height: .55rem;
            overflow: hidden;
        }

        .smart-thermo-fill {
            background: #2563eb;
            border-radius: inherit;
            display: block;
            height: 100%;
            transition: width .2s ease;
        }

        .smart-thermo-fill.temp-warm { background: #f97316; }
        .smart-thermo-fill.temp-hot { background: #dc2626; }
        .smart-thermo-fill.temp-max { background: #dc2626; }

        .smart-thermo-state {
            align-items: center;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: .4rem;
            display: inline-flex;
            font-size: .68rem;
            font-weight: 500;
            gap: .28rem;
            padding: .24rem .45rem;
            white-space: nowrap;
        }

        .smart-thermo-state.temp-cold { color: #1d4ed8; }
        .smart-thermo-state.temp-warm { color: #c2410c; }
        .smart-thermo-state.temp-hot,
        .smart-thermo-state.temp-max { color: #b91c1c; }

        .smart-thermo-state::before {
            content: '';
            display: inline-block;
            width: .5rem;
            height: .5rem;
            border-radius: 50%;
            margin-right: .3rem;
            vertical-align: middle;
        }
        .smart-thermo-state.temp-cold::before { background: #3b82f6; }
        .smart-thermo-state.temp-warm::before { background: #f59e0b; }
        .smart-thermo-state.temp-hot::before,
        .smart-thermo-state.temp-max::before { background: #ef4444; }

        @media (max-width: 760px) {
            .smart-drawer {
                border-radius: 0;
                max-height: 100vh;
                right: 0;
                top: 0;
                width: 100%;
            }
        }

        .dark .smart-drawer {
            background: #0f172a;
            border-color: rgba(148, 163, 184, .18);
        }

        .dark .smart-drawer-header {
            border-bottom-color: rgba(148, 163, 184, .18);
        }

        .dark .smart-drawer-tabs {
            border-bottom-color: rgba(148, 163, 184, .18);
        }

        .dark .smart-drawer-footer {
            border-top-color: rgba(148, 163, 184, .18);
        }

        .dark .smart-drawer-tab {
            color: #94a3b8;
        }

        .dark .smart-drawer-tab:hover,
        .dark .smart-drawer-tab.is-active {
            border-bottom-color: #e5e7eb;
            color: #e5e7eb;
        }

        .dark .smart-drawer-close {
            background: transparent;
            border-color: transparent;
            color: #94a3b8;
        }

        .dark .smart-drawer-close:hover {
            background: rgba(30, 41, 59, .86);
            border-color: rgba(148, 163, 184, .18);
            color: #f1f5f9;
        }

        .dark .smart-drawer-title {
            color: #f1f5f9;
        }

        .dark .smart-drawer-body {
            background: #0f172a;
        }

        .dark .smart-drawer-card {
            background: rgba(15, 23, 42, .86);
            border-color: rgba(148, 163, 184, .18);
        }

        .dark .smart-drawer-card-kicker,
        .dark .smart-drawer-card-title {
            color: #e2e8f0;
        }

        .dark .smart-drawer-card-text {
            color: #cbd5e1;
        }

        .dark .smart-drawer-card.is-ai {
            background: rgba(15, 23, 42, .86);
            border-color: rgba(148, 163, 184, .18);
        }

        .dark .smart-drawer-timeline-item:not(:last-child)::before {
            background: rgba(148, 163, 184, .18);
        }

        .dark .smart-activity-icon {
            background: rgba(15, 23, 42, .86);
            border-color: rgba(148, 163, 184, .18);
        }

        .dark .smart-activity-title {
            color: #e2e8f0;
        }

        .dark .smart-activity-meta,
        .dark .smart-activity-note,
        .dark .smart-activity-empty,
        .dark .smart-activity-progress span:last-child {
            color: #94a3b8;
        }

        .dark .smart-conversation-bubble {
            background: rgba(15, 23, 42, .72);
            border-color: rgba(148, 163, 184, .18);
            color: #e2e8f0;
        }

        .dark .smart-conversation-summary {
            background: rgba(15, 23, 42, .72);
            border-color: rgba(148, 163, 184, .18);
        }

        .dark .smart-conversation-metrics,
        .dark .smart-conversation-metric:not(:last-child) {
            border-color: rgba(148, 163, 184, .18);
        }

        .dark .smart-conversation-metric strong {
            color: #e2e8f0;
        }

        .dark .smart-conversation-route-arrow,
        .dark .smart-conversation-summary-date,
        .dark .smart-conversation-kind,
        .dark .smart-conversation-time,
        .dark .smart-conversation-rule,
        .dark .smart-channel-change,
        .dark .smart-conversation-metric span {
            color: #94a3b8;
        }

        .dark .smart-activity-progress-track {
            background: rgba(148, 163, 184, .18);
        }

        .dark .smart-thermo-score {
            color: #cbd5e1;
        }

        .dark .smart-thermo-track {
            background: rgba(148, 163, 184, .18);
        }

        .smart-card {
            background: var(--inbox-card);
            border: 1px solid #e5e7eb;
            border-top: 4px solid #14b8a6;
            border-radius: .75rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .06);
            display: grid;
            gap: .9rem;
            overflow: visible;
            padding: clamp(1rem, 1.5vw, 1.2rem);
            position: relative;
            transition: border-color .14s ease, box-shadow .14s ease;
        }

        .smart-card:hover {
            box-shadow: 0 2px 8px rgba(15, 23, 42, .06);
        }

        .smart-card:has(.smart-dropdown[open]) {
            z-index: 30;
        }

        .smart-card.intent-crisis {
            border-top-color: #dc2626;
        }

        .smart-card.intent-lead { border-top-color: var(--inbox-accent); }
        .smart-card.intent-vip { border-top-color: #16a34a; }
        .smart-card.intent-medical { border-top-color: #f59e0b; }

        .smart-card.is-derived {
            background: linear-gradient(180deg, #fffbeb, #ffffff 42%);
            border-color: #fde68a;
            border-top-color: #f59e0b;
        }

        .smart-card.is-hot-lead::before {
            animation: hotLeadPulse 1.15s ease-in-out infinite;
            background: radial-gradient(circle, rgba(249, 115, 22, .24), transparent 64%);
            content: '';
            height: 8rem;
            inset: -3rem auto auto -3rem;
            pointer-events: none;
            position: absolute;
            width: 8rem;
        }

        .smart-card.intent-crisis.risk-critical::after {
            animation: inboxPulse 1.25s ease-in-out infinite;
            background: radial-gradient(circle, rgba(239, 68, 68, .24), transparent 62%);
            content: '';
            height: 8rem;
            inset: -3rem -3rem auto auto;
            position: absolute;
            width: 8rem;
        }

        @keyframes inboxPulse {
            0%, 100% { opacity: .45; transform: scale(.96); }
            50% { opacity: .95; transform: scale(1.08); }
        }

        @keyframes hotLeadPulse {
            0%, 100% { opacity: .38; transform: scale(.96); }
            50% { opacity: .9; transform: scale(1.1); }
        }

        .smart-card.is-selected {
            border-color: #0f766e;
            box-shadow: 0 0 0 2px rgba(20, 184, 166, .18), 0 2px 8px rgba(15, 23, 42, .08);
        }

        .smart-card.is-live {
            animation: livePulse 1.8s ease-in-out 3;
        }

        @keyframes livePulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0), 0 18px 60px -52px rgba(15, 23, 42, .85); }
            45% { box-shadow: 0 0 0 6px rgba(34, 197, 94, .2), 0 18px 60px -52px rgba(15, 23, 42, .85); }
        }

        .smart-card-top {
            align-items: start;
            display: flex;
            gap: .85rem;
            justify-content: space-between;
        }

        .smart-person {
            display: flex;
            gap: .75rem;
            min-width: 0;
        }

        .smart-avatar {
            align-items: center;
            background: #f1f5f9;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            color: var(--inbox-accent);
            display: flex;
            flex: 0 0 auto;
            font-size: .9rem;
            font-weight: 700;
            height: 2.85rem;
            justify-content: center;
            text-transform: uppercase;
            width: 2.85rem;
        }

        .intent-crisis .smart-avatar { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
        .intent-lead .smart-avatar { background: #eff6ff; border-color: #bfdbfe; color: var(--inbox-accent); }
        .intent-vip .smart-avatar { background: #ecfdf5; border-color: #bbf7d0; color: #047857; }
        .intent-medical .smart-avatar { background: #fffbeb; border-color: #fed7aa; color: #b45309; }

        .smart-user {
            color: var(--inbox-ink);
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.15;
            overflow-wrap: anywhere;
        }

        .smart-source,
        .smart-time {
            color: var(--inbox-muted);
            font-size: .78rem;
            font-weight: 500;
            margin-top: .25rem;
        }

        .smart-badges {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: .3rem;
            justify-content: flex-end;
        }

        .smart-badge {
            border-radius: 999px;
            font-size: .625rem;
            font-weight: 600;
            letter-spacing: .03em;
            padding: .175rem .4rem;
        }

        .smart-badge.danger { background: #fef2f2; color: #b91c1c; }
        .smart-badge.info { background: #ecfeff; color: #0e7490; }
        .smart-badge.warning { background: #fffbeb; color: #b45309; }
        .smart-badge.success { background: #ecfdf5; color: #047857; }
        .smart-badge.neutral { background: #f1f5f9; color: #475569; }
        .smart-badge.hot { background: #fff7ed; color: #c2410c; }

        .smart-token {
            align-items: center;
            background: #fffbeb;
            border: 1px dashed #f59e0b;
            border-radius: .7rem;
            color: #92400e;
            display: flex;
            font-size: .8rem;
            font-weight: 700;
            gap: .45rem;
            padding: .55rem .7rem;
        }

        .smart-action-banner {
            align-items: center;
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 1px solid #f59e0b;
            border-radius: .5rem;
            display: flex;
            gap: .5rem;
            margin: .5rem 0;
            padding: .55rem .75rem;
            font-size: .78rem;
            color: #92400e;
            font-weight: 600;
        }

        .smart-action-banner svg {
            flex-shrink: 0;
            width: 1rem;
            height: 1rem;
        }

        .smart-action-banner .smart-action-link {
            margin-left: auto;
            background: #f59e0b;
            color: #fff;
            padding: .25rem .6rem;
            border-radius: .35rem;
            text-decoration: none;
            font-size: .72rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .smart-action-banner .smart-action-link:hover {
            background: #d97706;
        }

        .smart-progress {
            align-items: center;
            display: flex;
            gap: .3rem;
        }

        .smart-dot {
            background: #d1d5db;
            border-radius: 999px;
            height: .375rem;
            transition: background .14s ease;
            width: .375rem;
        }

        .smart-dot.active { background: #3b82f6; }
        .smart-dot.done { background: #10b981; }

        .smart-message {
            color: #111827;
            font-size: clamp(.898rem, 1.35vw, 0.875rem);
            font-weight: 500;
            letter-spacing: -.015em;
            line-height: 1.5;
            padding: .2rem 0;
        }

        .smart-panels {
            display: grid;
            gap: .5rem;
            grid-template-columns: minmax(0, 1fr);
        }

        @media (min-width: 760px) {
            .smart-panels {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .smart-panel {
            border: 1px solid #e5e7eb;
            border-radius: .75rem;
            overflow: hidden;
        }

        .smart-panel:not(details) {
            display: grid;
            gap: .35rem;
            padding: .75rem .875rem;
        }

        .smart-panel h3 {
            color: #0f172a;
            font-size: .78rem;
            font-weight: 600;
            margin: 0 0 .45rem;
        }

        .smart-panel summary {
            cursor: pointer;
            color: #0f172a;
            font-size: .78rem;
            font-weight: 600;
            list-style: none;
            padding: .625rem .875rem;
        }

        .smart-panel summary::-webkit-details-marker { display: none; }
        .smart-panel summary::before { content: ''; }

        .smart-panel[open] summary {
            border-bottom: 1px solid #e5e7eb;
        }

        .smart-ai-panel summary {
            color: #0f172a;
        }

        .smart-panel-body {
            padding: .75rem .875rem;
        }

        .smart-panel-kicker {
            color: #64748b;
            font-size: .74rem;
            line-height: 1.45;
            margin: -.25rem 0 .6rem;
        }

        .smart-panel-body p,
        .smart-panel-body strong,
        .smart-panel > p,
        .smart-panel > strong {
            color: #0f172a;
            font-size: .8125rem;
            font-weight: 500;
            line-height: 1.45;
            margin: 0;
        }

        .smart-panel-body strong,
        .smart-panel > strong,
        .smart-panel p strong {
            font-weight: 600;
        }

        .smart-muted { color: #64748b !important; }

        .smart-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            align-items: center;
        }

        .smart-action {
            align-items: center;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: .45rem;
            color: #111827;
            display: inline-flex;
            font-size: .76rem;
            font-weight: 500;
            justify-content: center;
            line-height: 1;
            min-height: 2rem;
            padding: .38rem .65rem;
            text-decoration: none;
            transition: background-color .14s ease, border-color .14s ease, color .14s ease;
        }

        .smart-action svg {
            height: .9rem;
            width: .9rem;
        }

        .smart-action.with-icon {
            gap: .35rem;
        }

        .smart-action:hover { background: #f9fafb; border-color: #d1d5db; color: #111827; }
        .smart-action.primary { background: #000000; border-color: #000000; color: #ffffff; }
        .smart-action.primary:hover { background: #1a1a1a; border-color: #1a1a1a; color: #ffffff; }
        .smart-action.success { background: #0f766e; color: white; }
        .smart-action.success:hover { background: #0d6b63; }
        .smart-action.warning { background: #ffffff; border-color: #fed7aa; color: #b45309; }
        .smart-action.danger { background: #ffffff; border-color: #fecaca; color: #b91c1c; }
        .smart-action.muted { background: #ffffff; border-color: #e5e7eb; color: #475569; }
        .smart-action.muted:hover { background: #f9fafb; border-color: #d1d5db; color: #374151; }

        .smart-section-head {
            align-items: flex-start;
            display: flex;
            gap: .75rem;
            justify-content: space-between;
        }

        .smart-trigger {
            align-items: center;
            background: transparent;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
            color: #6b7280;
            cursor: pointer;
            display: inline-flex;
            font-size: 1.1rem;
            font-weight: 500;
            height: 2rem;
            justify-content: center;
            letter-spacing: .05em;
            line-height: 1;
            transition: all .14s ease;
            width: 2rem;
        }

        .smart-trigger:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            color: #374151;
        }

        .smart-dropdown {
            position: relative;
        }

        .smart-dropdown summary {
            list-style: none;
        }

        .smart-dropdown summary::-webkit-details-marker {
            display: none;
        }

        .smart-dropdown[open] .smart-trigger {
            background: #f9fafb;
            border-color: #d1d5db;
            color: #374151;
        }

        .smart-dropdown-menu {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: .75rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
            min-width: 12rem;
            padding: .375rem 0;
            position: absolute;
            right: 0;
            top: calc(100% + .375rem);
            z-index: 50;
        }

        .smart-dropdown-item {
            align-items: center;
            color: #1f2937;
            cursor: pointer;
            display: flex;
            font-size: .8125rem;
            font-weight: 500;
            gap: .625rem;
            padding: .5rem .875rem;
            text-decoration: none;
            transition: background .12s ease;
            width: 100%;
        }

        .smart-dropdown-item:hover {
            background: #f3f4f6;
        }

        .smart-dropdown-item.danger {
            color: #dc2626;
        }

        .smart-dropdown-item.danger:hover {
            background: #fef2f2;
        }

        .smart-dropdown-item svg {
            color: #9ca3af;
            flex-shrink: 0;
            height: 1rem;
            width: 1rem;
        }

        .smart-dropdown-item.danger svg {
            color: #f87171;
        }

        .smart-empty {
            align-items: center;
            border-radius: 1rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            color: #475569;
            display: grid;
            grid-column: 1 / -1;
            justify-items: center;
            min-height: 20rem;
            padding: clamp(2rem, 4vw, 3.25rem) 1.25rem;
            text-align: center;
            width: 100%;
        }

        .smart-empty-illustration {
            height: 12.25rem;
            margin-bottom: 1.1rem;
            object-fit: contain;
            width: 12.8rem;
        }

        .smart-empty-title {
            color: #111827;
            font-size: 1.12rem;
            font-weight: 750;
            letter-spacing: -.015em;
            line-height: 1.25;
            margin: 0;
        }

        .smart-empty-copy {
            color: #64748b;
            font-size: .88rem;
            line-height: 1.55;
            margin: .55rem 0 0;
            max-width: 32rem;
        }

        .smart-modal-backdrop {
            align-items: center;
            background: rgba(15, 23, 42, .48);
            display: flex;
            inset: 0;
            justify-content: center;
            padding: clamp(.6rem, 1.5vh, 1rem);
            position: fixed;
            z-index: 60;
        }

        .smart-modal {
            background: #ffffff;
            border-radius: .875rem;
            box-shadow: 0 4px 24px rgba(15, 23, 42, .12);
            display: grid;
            gap: .75rem;
            max-height: calc(100dvh - clamp(1.2rem, 3vh, 2rem));
            max-width: 72rem;
            overflow-y: auto;
            padding: 1.25rem;
            position: relative;
            width: min(100%, 72rem);
        }

        @supports not (height: 100dvh) {
            .smart-modal {
                max-height: calc(100vh - clamp(1.2rem, 3vh, 2rem));
            }
        }

        .smart-modal-close {
            align-items: center;
            background: transparent;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            color: #94a3b8;
            display: inline-flex;
            font-size: .95rem;
            font-weight: 600;
            height: 1.75rem;
            justify-content: center;
            line-height: 1;
            position: absolute;
            right: .85rem;
            top: .85rem;
            transition: .14s ease;
            width: 1.75rem;
        }

        .smart-modal-close:hover {
            background: #eef2f7;
            color: #0f172a;
        }

        .smart-modal-header {
            padding-right: 2.6rem;
        }

        .smart-modal-header .smart-muted {
            color: #64748b;
            font-size: .76rem;
            margin-top: .2rem;
        }

        .smart-modal h2 {
            color: #0f172a;
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
        }

        .smart-modal-layout {
            display: grid;
            gap: 1rem;
            grid-template-columns: minmax(0, 1fr);
        }

        .smart-modal-footer {
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            padding-top: .75rem;
        }

        @media (min-width: 980px) {
            .smart-modal-layout {
                grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr);
            }
        }

        .smart-modal-column {
            align-content: start;
            display: grid;
            gap: .75rem;
        }

        .smart-modal-section {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: .75rem;
            display: grid;
            gap: .6rem;
            padding: .85rem;
        }


        .smart-field-label {
            color: #334155;
            display: block;
            font-size: .7rem;
            font-weight: 600;
            margin-bottom: .25rem;
        }

        .smart-copy-field {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
            color: #334155;
            font-size: .78rem;
            font-weight: 500;
            line-height: 1.4;
            padding: .5rem .625rem;
            width: 100%;
            height: auto;
        }

        .smart-copy-field[readonly] {
            cursor: text;
        }

        .smart-copy-control {
            position: relative;
        }

        .smart-copy-control .smart-copy-field {
            padding-right: 2.35rem;
        }

        .smart-copy-icon {
            align-items: center;
            background-color: #ffffff;
            border: 0;
            border-radius: .375rem;
            color: #64748b;
            display: inline-flex;
            height: 1.75rem;
            justify-content: center;
            padding: 0;
            position: absolute;
            right: .35rem;
            top: 50%;
            transform: translateY(-50%);
            transition: background .14s ease, color .14s ease;
            width: 1.75rem;
        }

        .smart-copy-icon:hover {
            background: #eef2f7;
            color: #0f172a;
        }

        .smart-copy-icon:disabled {
            color: #cbd5e1;
            cursor: not-allowed;
            opacity: .65;
        }

        .smart-copy-icon:disabled:hover {
            background: transparent;
            color: #cbd5e1;
        }

        .smart-copy-icon svg {
            height: .95rem;
            width: .95rem;
        }

        .smart-modal-note {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: .625rem;
            color: #0369a1;
            font-size: .78rem;
            font-weight: 500;
            line-height: 1.5;
            padding: .65rem .75rem;
        }

        .smart-modal-warning {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: .625rem;
            color: #92400e;
            font-size: .78rem;
            font-weight: 500;
            line-height: 1.5;
            padding: .65rem .75rem;
        }

        .smart-preview-stack {
            display: grid;
            gap: .75rem;
        }

        .smart-preview-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: .75rem;
            display: grid;
            gap: .55rem;
            padding: .85rem;
        }

        .smart-preview-kicker {
            color: #0f172a;
            font-size: .78rem;
            font-weight: 600;
        }

        .smart-preview-title {
            color: #0f172a;
            font-size: .95rem;
            font-weight: 600;
            letter-spacing: -.015em;
            line-height: 1.3;
            margin: 0;
        }

        .smart-preview-copy {
            color: #64748b;
            font-size: .8rem;
            font-weight: 500;
            line-height: 1.5;
            margin: 0;
        }

        .smart-meta {
            display: flex;
            flex-direction: column;
            gap: .15rem;
        }

        .smart-meta span {
            color: #64748b;
            font-size: .76rem;
            font-weight: 500;
            line-height: 1.4;
        }

        .smart-meta strong {
            color: #334155;
            font-weight: 600;
        }

        .smart-meta span.url {
            color: #94a3b8;
            font-size: .68rem;
            word-break: break-all;
        }

        .dark .smart-dot {
            background: rgba(148, 163, 184, .25);
        }

        .dark .smart-dot.active { background: #60a5fa; }
        .dark .smart-dot.done { background: #34d399; }

        .dark .smart-panel {
            border-color: rgba(148, 163, 184, .18);
        }

        .dark .smart-panel summary {
            color: #e5e7eb;
        }

        .dark .smart-panel h3 {
            color: #e5e7eb;
        }

        .dark .smart-panel[open] summary {
            border-bottom-color: rgba(148, 163, 184, .18);
        }

        .dark .smart-ai-panel summary {
            color: #e5e7eb;
        }

        .dark .smart-panel-body p,
        .dark .smart-panel-body strong,
        .dark .smart-panel > p,
        .dark .smart-panel > strong,
        .dark .smart-panel p strong {
            color: #e2e8f0;
        }

        .dark .smart-dropdown-menu {
            background: rgba(15, 23, 42, .95);
            border-color: rgba(148, 163, 184, .18);
        }

        .dark .smart-dropdown-item {
            color: #e2e8f0;
        }

        .dark .smart-dropdown-item:hover {
            background: rgba(148, 163, 184, .12);
        }

        .dark .smart-dropdown-item.danger {
            color: #f87171;
        }

        .dark .smart-dropdown-item.danger:hover {
            background: rgba(239, 68, 68, .12);
        }

        .dark .smart-trigger {
            border-color: rgba(148, 163, 184, .18);
            color: #94a3b8;
        }

        .dark .smart-trigger:hover {
            background: rgba(148, 163, 184, .12);
            border-color: rgba(148, 163, 184, .28);
            color: #e2e8f0;
        }

        .dark .smart-modal-note {
            background: rgba(29, 78, 216, .18);
            border-color: rgba(96, 165, 250, .24);
            color: #bfdbfe;
        }

        .dark .smart-modal {
            background: #1e293b;
            border: 1px solid rgba(148, 163, 184, .18);
        }

        .dark .smart-modal-section {
            background: rgba(15, 23, 42, .6);
            border-color: rgba(148, 163, 184, .14);
        }

        .dark .smart-modal-section.is-soft {
            background: rgba(15, 23, 42, .4);
        }

        .dark .smart-modal-footer {
            border-top-color: rgba(148, 163, 184, .14);
        }

        .dark .smart-modal-close {
            border-color: rgba(148, 163, 184, .18);
            color: #64748b;
        }

        .dark .smart-field-label {
            color: #94a3b8;
        }

        .dark .smart-copy-field {
            background: rgba(15, 23, 42, .5);
            border-color: rgba(148, 163, 184, .14);
            color: #cbd5e1;
        }

        .dark .smart-copy-icon {
            color: #94a3b8;
        }

        .dark .smart-copy-icon:hover {
            background: rgba(148, 163, 184, .12);
            color: #e2e8f0;
        }

        .dark .smart-copy-icon:disabled,
        .dark .smart-copy-icon:disabled:hover {
            background: transparent;
            color: #475569;
        }

        .dark .smart-preview-card {
            background: rgba(15, 23, 42, .6);
            border-color: rgba(148, 163, 184, .14);
        }

        .dark .smart-preview-kicker,
        .dark .smart-preview-title {
            color: #e5e7eb;
        }

        .dark .smart-preview-copy {
            color: #94a3b8;
        }

        .dark .smart-meta span {
            color: #94a3b8;
        }

        .dark .smart-meta strong {
            color: #cbd5e1;
        }

        .dark .smart-modal-warning {
            background: rgba(180, 83, 9, .12);
            border-color: rgba(251, 191, 36, .22);
            color: #fcd34d;
        }

        .dark .smart-empty {
            background: rgba(15, 23, 42, .86);
            border-color: rgba(148, 163, 184, .18);
        }

        .dark .smart-empty-title {
            color: #f8fafc;
        }

        .dark .smart-empty-copy {
            color: #94a3b8;
        }
    </style>

    <section class="social-inbox-page" wire:poll.10s>
        <header class="social-inbox-toolbar">
            <input
                class="smart-search"
                type="search"
                wire:model.live.debounce.350ms="search"
                placeholder="Buscar por comentario, autor o usuario"
            />
        </header>

        <div class="smart-filters">
            @foreach ($filters as $key => $item)
                <button
                    type="button"
                    wire:click="setFilter('{{ $key }}')"
                    @class(['smart-filter', 'is-active' => $filter === $key])
                >
                    <span class="smart-filter-icon">
                        @switch($item['icon'])
                            @case('user-plus')
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" /></svg>
                                @break
                            @case('exclamation-triangle')
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                                @break
                            @case('star')
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" /></svg>
                                @break
                            @case('heart')
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></svg>
                                @break
                            @case('magnifying-glass')
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                                @break
                            @case('archive-box')
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" /></svg>
                                @break
                        @endswitch
                    </span>
                    <span>{{ $item['label'] }}</span>
                    <span class="smart-filter-count">{{ $item['count'] }}</span>
                </button>
            @endforeach
        </div>

        <div class="smart-grid smart-list-column">
            @forelse ($comments as $comment)
                @php
                    $risk = $comment->reputation_risk?->value ?? 'low';
                    $classification = $comment->classification;
                    $patient = $comment->socialIdentity?->patient ?: $comment->convertedPatient;
                    $lastActivity = $patient?->activityRecords?->sortByDesc('activity_date')->sortByDesc('id')->first();
                    $isLead = in_array($classification, [
                        \App\Enums\SocialCommentClassification::SalesLead,
                        \App\Enums\SocialCommentClassification::CommercialQuestion,
                    ], true);
                    $isCrisis = in_array($risk, ['high', 'critical'], true) || in_array($classification, [
                        \App\Enums\SocialCommentClassification::Complaint,
                        \App\Enums\SocialCommentClassification::NegativeOpinion,
                        \App\Enums\SocialCommentClassification::LegalSensitive,
                    ], true);
                    $isMedical = $classification === \App\Enums\SocialCommentClassification::MedicalSensitive;
                    $isVip = filled($patient) && ($patient->activityRecords?->count() ?? 0) > 0;
                    $isDerived = $comment->conversion_status === \App\Enums\SocialConversionStatus::TokenGenerated;
                    $isHotLead = filled($comment->hot_lead_at);
                    $isReheated = filled($comment->reheated_at);
                    $autoReply = $this->autoReplyStatus($comment);
                    $intent = $isCrisis ? 'crisis' : ($isLead ? 'lead' : ($isVip ? 'vip' : ($isMedical ? 'medical' : 'normal')));
                    $intentTitle = match ($intent) {
                        'crisis' => 'RIESGO CRITICO',
                        'vip' => 'PACIENTE VIP',
                        'medical' => 'ATENCION MEDICA',
                        'lead' => 'LEAD COMERCIAL',
                        default => 'COMENTARIO SOCIAL',
                    };
                    $initial = \Illuminate\Support\Str::of($comment->author_name ?: $comment->author_username ?: '?')->substr(0, 1)->upper();
                    $detailUrl = \App\Filament\Resources\SocialComments\SocialCommentResource::getUrl('view', ['record' => $comment]);
                    $patientUrl = $patient ? \App\Filament\Resources\Patients\PatientResource::getUrl('edit', ['record' => $patient]) : null;
                @endphp

                <article @class([
                    'smart-card',
                    'intent-' . $intent,
                    'risk-' . $risk,
                    'is-derived' => $isDerived,
                    'is-hot-lead' => $isHotLead,
                    'is-selected' => $selectedComment?->id === $comment->id,
                    'is-live' => $recentActivityLeadId === $comment->id,
                ])>
                    <div class="smart-card-top">
                        <div class="smart-person">
                            <div class="smart-avatar">{{ $initial }}</div>
                            <div>
                                <div class="smart-user">
                                    {{ $comment->author_username ? '@' . $comment->author_username : ($comment->author_name ?: 'Autor desconocido') }}
                                </div>
                                <div class="smart-source">via {{ $comment->platform->label() }} / {{ $intentTitle }}</div>
                                <div class="smart-time">{{ $comment->created_at?->diffForHumans() }}</div>
                            </div>
                        </div>

                        <div class="smart-badges">
                            <span @class(['smart-badge', 'danger' => $isCrisis, 'info' => $isLead, 'warning' => $isMedical, 'success' => $isVip, 'neutral' => ! $isCrisis && ! $isLead && ! $isMedical && ! $isVip])>
                                {{ $classification?->label() ?? 'Sin clasificar' }}
                            </span>
                            <span @class(['smart-badge', 'danger' => in_array($risk, ['high', 'critical'], true), 'warning' => $risk === 'medium', 'neutral' => $risk === 'low'])>
                                Riesgo {{ $comment->reputation_risk?->label() ?? 'bajo' }}
                            </span>
                            <span class="smart-badge neutral">{{ $comment->sentiment?->label() ?? 'Sin sentimiento' }}</span>
                            <span @class(['smart-badge', 'hot' => $isHotLead, 'neutral' => ! $isHotLead])>
                                {{ $isHotLead ? '🔥 ' : '' }}Score {{ $comment->interest_score ?? 0 }}
                            </span>
                            @if ($isDerived)
                                <span class="smart-badge warning">Derivado</span>
                            @endif
                            @if ($isReheated)
                                <span class="smart-badge hot">Recalentado</span>
                            @endif
                            <span @class(['smart-badge', $autoReply['class']])>{{ $autoReply['label'] }}</span>

                            <details class="smart-dropdown">
                                <summary class="smart-trigger" aria-label="Mas opciones">⋯</summary>
                                <div class="smart-dropdown-menu">
                                    @if ($patientUrl)
                                        <a class="smart-dropdown-item" href="{{ $patientUrl }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                                            Ver ficha
                                        </a>
                                    @else
                                        <a class="smart-dropdown-item" href="{{ $detailUrl }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                            Crear ficha
                                        </a>
                                    @endif

                                    <a class="smart-dropdown-item" href="{{ $detailUrl }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                        Detalle
                                    </a>

                                    <button class="smart-dropdown-item" type="button" wire:click="markReviewed({{ $comment->id }})">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                        Revisado
                                    </button>

                                    <button class="smart-dropdown-item" type="button" wire:click="runAutoReply({{ $comment->id }})">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                                        Auto-responder
                                    </button>

                                    <button class="smart-dropdown-item" type="button" wire:click="ignore({{ $comment->id }})">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                        Ignorar
                                    </button>

                                    @if ($isCrisis)
                                        <button class="smart-dropdown-item" type="button" wire:click="escalate({{ $comment->id }})">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" /></svg>
                                            Escalar
                                        </button>
                                    @endif

                                    <button class="smart-dropdown-item danger" type="button" wire:click="markSpam({{ $comment->id }})">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                                        Spam
                                    </button>
                                </div>
                            </details>
                        </div>
                    </div>

                    <div class="smart-message">"{{ $comment->comment_text }}"</div>

                    @if ($comment->conversion_status === \App\Enums\SocialConversionStatus::PendingPatientCreation)
                        <div class="smart-action-banner">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                            <span>El cliente hizo clic en WhatsApp. Crea su ficha y agenda la cita.</span>
                            <a class="smart-action-link" href="{{ $detailUrl }}">Crear ficha</a>
                        </div>
                    @endif

                    @if ($isDerived && filled($comment->tracking_token))
                        <div class="smart-token">
                            <span>Token WhatsApp:</span>
                            <strong>{{ $comment->tracking_token }}</strong>
                        </div>
                    @endif

                    <div class="smart-progress" aria-label="Flujo de seguimiento">
                        <span class="smart-dot {{ $isDerived ? 'done' : 'active' }}"></span>
                        <span class="smart-dot {{ $isDerived ? 'active' : '' }}"></span>
                        <span class="smart-dot"></span>
                        <span class="smart-dot"></span>
                    </div>

                    <div class="smart-panels">
                        <details class="smart-panel">
                            <summary>Contexto Clinico</summary>
                            <div class="smart-panel-body">
                                @if ($patient)
                                    <p><strong>Paciente vinculado:</strong> {{ $patient->full_name }}</p>
                                    <p class="smart-muted">{{ $lastActivity ? 'Ultima cita: ' . $lastActivity->activity_date?->format('d/m/Y') : 'Sin cita registrada' }}</p>
                                    <p class="smart-muted">{{ $lastActivity?->doctor?->name ?: 'Doctor no registrado' }}</p>
                                @else
                                    <p><strong>Nuevo lead:</strong> Sin ficha clinica</p>
                                    <p class="smart-muted">Telefono: {{ $comment->socialIdentity?->phone ?: 'pendiente de capturar' }}</p>
                                    <p class="smart-muted">Procedimiento: {{ $comment->suggestedProcedure?->name ?: 'sin sugerencia' }}</p>
                                @endif
                            </div>
                        </details>

                        <details class="smart-panel smart-ai-panel">
                            <summary>Respuesta base IA</summary>
                            <div class="smart-panel-body">
                                <div class="smart-panel-kicker">Antes de derivar. No incluye token ni link de seguimiento.</div>
                                <p>{{ $comment->suggested_reply ?: 'Sin respuesta sugerida. Revisar contexto antes de responder.' }}</p>
                                @if ($comment->ai_reason)
                                    <p class="smart-muted" style="margin-top:.45rem">Motivo: {{ $comment->ai_reason }}</p>
                                @endif
                            </div>
                        </details>
                    </div>

                    <div class="smart-actions">
                        <button class="smart-action primary" type="button" wire:click="selectComment({{ $comment->id }})">Abrir 360</button>

                        @if ($isLead || blank($comment->tracking_token))
                            <button class="smart-action success" type="button" wire:click="routeToWhatsapp({{ $comment->id }})">
                                {{ $isDerived ? 'Ver texto de seguimiento' : 'Derivar' }}
                            </button>
                        @endif

                        <button class="smart-action warning" type="button" wire:click="runAutoReply({{ $comment->id }})">
                            {{ filled($comment->auto_reply_message) || filled($comment->auto_reply_error) ? 'Reintentar auto-reply' : 'Generar auto-reply' }}
                        </button>

                    </div>
                </article>
            @empty
                <div class="smart-empty">
                    <img class="smart-empty-illustration" src="{{ asset('images/empty-social-comments_1.svg') }}" alt="" aria-hidden="true">
                    <h3 class="smart-empty-title">Sin comentarios por ahora</h3>
                    <p class="smart-empty-copy">
                        No hay comentarios disponibles para este segmento. Prueba otro filtro o espera la proxima sincronizacion social.
                    </p>
                </div>
            @endforelse
        </div>

        <div class="mt-5">
            {{ $comments->links() }}
        </div>

        @if ($selectedComment)
            @php
                $drawerTimeline = $selectedTimeline;
                $drawerPatient = $selectedPatient;
                $drawerPlatformLabel = $selectedComment->platform?->label() ?? 'Red social';
                $engagementScore = (int) $selectedComment->recent_engagement_score;
                $displayEngagementScore = min($engagementScore, 100);
                $engagementState = match (true) {
                    $engagementScore >= 100 => ['label' => 'Alta prioridad', 'class' => 'temp-max'],
                    $engagementScore >= 71 => ['label' => 'Caliente', 'class' => 'temp-hot'],
                    $engagementScore >= 31 => ['label' => 'Tibio', 'class' => 'temp-warm'],
                    default => ['label' => 'Frio', 'class' => 'temp-cold'],
                };
            @endphp
            <div class="smart-drawer-backdrop" wire:click="closeCommentDrawer"></div>

            <aside class="smart-drawer" x-data="{ tab: 'summary' }" @keydown.escape.window="$wire.closeCommentDrawer()">
                <div class="smart-drawer-header">
                    <div style="min-width:0">
                        <div class="smart-drawer-title">
                            {{ $selectedComment->author_username ? '@'.$selectedComment->author_username : ($selectedComment->author_name ?: 'Lead seleccionado') }}
                        </div>
                        <div class="smart-drawer-subtitle">
                            {{ $drawerPlatformLabel }} · {{ $selectedComment->conversion_status?->label() ?? 'Sin estado' }}
                        </div>
                    </div>

                    <button class="smart-drawer-close" type="button" wire:click="closeCommentDrawer" aria-label="Cerrar detalle">&times;</button>
                </div>

                <nav class="smart-drawer-tabs" aria-label="Secciones del lead">
                    <button class="smart-drawer-tab" type="button" :class="{ 'is-active': tab === 'summary' }" @click="tab = 'summary'">Resumen</button>
                    <button class="smart-drawer-tab" type="button" :class="{ 'is-active': tab === 'conversation' }" @click="tab = 'conversation'">Conversacion</button>
                    <button class="smart-drawer-tab" type="button" :class="{ 'is-active': tab === 'activity' }" @click="tab = 'activity'">Actividad</button>
                </nav>

                <div class="smart-drawer-body">
                    @if ($selectedComment->conversion_status === \App\Enums\SocialConversionStatus::PendingPatientCreation)
                        <div class="smart-action-banner" style="margin-bottom:.75rem">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                            <span>El cliente hizo clic en WhatsApp. Crea su ficha y agenda la cita.</span>
                            <a class="smart-action-link" href="{{ \App\Filament\Resources\SocialComments\SocialCommentResource::getUrl('view', ['record' => $selectedComment]) }}">Crear ficha</a>
                        </div>
                    @endif

                    <section class="smart-drawer-card" x-show="tab === 'summary'" x-cloak>
                        <div class="smart-thermo-head">
                            <div>
                                <div class="smart-drawer-card-kicker">Termometro de Interes</div>
                                <div class="smart-drawer-card-title">Intensidad reciente</div>
                            </div>
                            <span class="smart-thermo-state {{ $engagementState['class'] }}">{{ $engagementState['label'] }}</span>
                        </div>

                        <div class="smart-thermo-track" aria-label="Intensidad reciente {{ $displayEngagementScore }} de 100">
                            <span class="smart-thermo-fill {{ $engagementState['class'] }}" style="width: {{ $displayEngagementScore }}%"></span>
                        </div>

                        <div class="smart-thermo-score">
                            Score reciente: {{ $engagementScore }}
                            @if ($selectedComment->engagement_priority_reason)
                                <span class="smart-drawer-muted">/ {{ $selectedComment->engagement_priority_reason }}</span>
                            @endif
                        </div>

                        <hr style="border:0;border-top:1px solid #e5e7eb;margin:1rem 0">

                        {{-- Mini CRM --}}
                        <div class="smart-drawer-card-kicker">Mini CRM</div>
                        <div class="smart-drawer-card-title" style="display:flex;align-items:center;flex-wrap:wrap">
                            @if ($drawerPatient)
                                {{ $drawerPatient->full_name }}
                                <a class="smart-action" href="{{ \App\Filament\Resources\Patients\PatientResource::getUrl('edit', ['record' => $drawerPatient]) }}" style="display:inline-flex;margin-left:.5rem;padding:.15rem .5rem;font-size:.7rem">Ver ficha</a>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1rem;height:1rem;flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                                <span style="margin-left:.3rem">Lead sin ficha clinica</span>
                            @endif
                        </div>
                        <p class="smart-drawer-card-text">
                            Score: {{ $selectedComment->interest_score ?? 0 }}
                            @if (filled($selectedComment->hot_lead_at))
                                / Hot lead desde {{ $selectedComment->hot_lead_at->diffForHumans() }}
                            @endif
                            @if (filled($selectedComment->reheated_at))
                                / Recalentado {{ $selectedComment->reheated_at->diffForHumans() }}
                            @endif
                        </p>
                        <div class="smart-panels">
                            <section class="smart-panel">
                                <h3>Procedimiento</h3>
                                <p>{{ $selectedComment->suggestedProcedure?->name ?: 'Sin sugerencia' }}</p>
                            </section>
                            <section class="smart-panel">
                                <h3>Alertas</h3>
                                <p>{{ $selectedComment->leadAlerts?->whereNull('resolved_at')->count() ?: 0 }} abiertas</p>
                            </section>
                        </div>
                    </section>

                    {{-- Card 1: Hilo de conversacion --}}
                    <section class="smart-drawer-card is-accent" x-show="tab === 'conversation'" x-cloak>
                        <div class="smart-drawer-card-kicker">Hilo de conversacion</div>
                        @php
                            $conversationCollection = collect($drawerConversation);
                            $conversationChannels = $conversationCollection->where('channel', '!=', 'system')->values();
                            $firstConversationChannel = $conversationChannels->first();
                            $lastConversationChannel = $conversationChannels->last();
                            $messageCount = $conversationCollection->count();
                            $automatedCount = $conversationCollection->where('is_automated', true)->count();
                            $automationRate = $messageCount > 0 ? (int) round(($automatedCount / $messageCount) * 100) : 0;
                            $firstHumanEvent = $conversationCollection->first(fn ($event) => ! ($event['is_automated'] ?? false) && filled($event['created_at'] ?? null));
                            $firstAutoEvent = $conversationCollection->first(fn ($event) => ($event['is_automated'] ?? false) && filled($event['created_at'] ?? null) && (! $firstHumanEvent || $event['created_at']->greaterThanOrEqualTo($firstHumanEvent['created_at'])));
                            $responseTime = 'N/D';

                            if ($firstHumanEvent && $firstAutoEvent) {
                                $responseSeconds = (int) $firstHumanEvent['created_at']->diffInSeconds($firstAutoEvent['created_at']);
                                $responseTime = $responseSeconds < 60 ? $responseSeconds.' seg' : floor($responseSeconds / 60).' min';
                            }

                            $previousChannel = null;
                        @endphp

                        @if ($messageCount > 0)
                            <div class="smart-conversation-summary">
                                <div class="smart-conversation-route">
                                    <div class="smart-conversation-route-main">
                                        @if ($firstConversationChannel)
                                            <span style="color:#64748b;font-size:.78rem">Canales:</span>
                                            <span class="smart-badge {{ $firstConversationChannel['channel_class'] }}">{{ $firstConversationChannel['channel_label'] }}</span>
                                        @endif
                                        @if ($firstConversationChannel && $lastConversationChannel && $firstConversationChannel['channel'] !== $lastConversationChannel['channel'])
                                            <span class="smart-conversation-route-arrow">→</span>
                                            <span class="smart-badge {{ $lastConversationChannel['channel_class'] }}">{{ $lastConversationChannel['channel_label'] }}</span>
                                        @endif
                                    </div>
                                    <span class="smart-conversation-summary-date">{{ $conversationCollection->first()['short_date'] ?? 'Fecha no registrada' }}</span>
                                </div>
                                <div class="smart-conversation-metrics">
                                    <div class="smart-conversation-metric">
                                        <span>Respuesta prom.</span>
                                        <strong>{{ $responseTime }}</strong>
                                    </div>
                                    <div class="smart-conversation-metric">
                                        <span>Mensajes</span>
                                        <strong>{{ $messageCount }}</strong>
                                    </div>
                                    <div class="smart-conversation-metric">
                                        <span>Automatización</span>
                                        <strong>{{ $automationRate }}%</strong>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="smart-drawer-timeline">
                            @forelse ($drawerConversation as $event)
                                @php
                                    $currentChannel = $event['channel'] ?? $event['platform'];
                                    $showChannelChange = filled($previousChannel) && $currentChannel !== 'system' && $currentChannel !== $previousChannel;
                                @endphp

                                @if ($showChannelChange)
                                    <div class="smart-channel-change">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                                        <span>Cambio de canal a</span>
                                        <span class="smart-badge {{ $event['channel_class'] }}">{{ $event['channel_label'] }}</span>
                                    </div>
                                @endif

                                <div class="smart-drawer-timeline-item">
                                    <span class="smart-activity-icon {{ $event['color'] }}" aria-hidden="true">
                                        @switch($event['platform'])
                                            @case('facebook')
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 21v-7h2.35l.35-2.72h-2.7V9.55c0-.79.22-1.33 1.35-1.33h1.44V5.79c-.25-.03-1.1-.1-2.1-.1-2.08 0-3.5 1.27-3.5 3.6v1.99H8.34V14h2.35v7h2.81Z" /></svg>
                                                @break
                                            @case('instagram')
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><rect width="15" height="15" x="4.5" y="4.5" rx="4" /><path stroke-linecap="round" stroke-linejoin="round" d="M15.5 11.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" /><path stroke-linecap="round" d="M16.75 7.75h.01" /></svg>
                                                @break
                                            @case('whatsapp')
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.75 19.25 6 15.6a7 7 0 1 1 2.42 2.35l-3.67 1.3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9.2 8.95c.18-.5.42-.55.74-.55h.43c.22 0 .4.14.49.34l.68 1.52c.08.18.04.39-.1.53l-.47.48c.48.84 1.16 1.52 2 2l.48-.47c.14-.14.35-.18.53-.1l1.52.68c.2.09.34.27.34.49v.43c0 .32-.05.56-.55.74-.4.14-.83.21-1.28.21-2.64 0-5.26-2.62-5.26-5.26 0-.45.07-.88.21-1.28Z" /></svg>
                                                @break
                                            @case('action')
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 8V4H8"></path><rect width="16" height="12" x="4" y="8" rx="2"></rect><path d="M2 14h2"></path><path d="M20 14h2"></path><path d="M15 13v2"></path><path d="M9 13v2"></path></svg>
                                                @break
                                        @endswitch
                                    </span>
                                    <div class="smart-conversation-content">
                                        <div class="smart-conversation-head">
                                            <div>
                                                <div class="smart-activity-title">
                                                    {{ $event['author'] }}
                                                    @unless ($event['is_automated'])
                                                        <span class="smart-badge {{ $event['channel_class'] }}">{{ $event['channel_label'] }}</span>
                                                    @endunless
                                                </div>
                                                <div class="smart-conversation-kind">{{ $event['kind_label'] }}</div>
                                            </div>
                                            <span class="smart-conversation-time">{{ $event['time'] }}</span>
                                        </div>
                                        <div class="smart-conversation-bubble">{{ $event['message'] }}</div>
                                        @if (filled($event['rule_label']))
                                            <div class="smart-conversation-rule">↯ {{ $event['rule_label'] }}</div>
                                        @endif
                                    </div>
                                </div>

                                @php
                                    if ($currentChannel !== 'system') {
                                        $previousChannel = $currentChannel;
                                    }
                                @endphp
                            @empty
                                <p class="smart-activity-empty">Sin conversacion registrada.</p>
                            @endforelse
                        </div>

                        <div class="smart-drawer-actions">
                            <button class="smart-action success" type="button" wire:click="suggestHistoricalReply({{ $selectedComment->id }})">
                                Sugerir respuesta basada en historial
                            </button>
                            <button class="smart-action warning" type="button" wire:click="runAutoReply({{ $selectedComment->id }})">
                                {{ filled($selectedComment->auto_reply_message) || filled($selectedComment->auto_reply_error) ? 'Reintentar auto-respuesta' : 'Generar auto-respuesta' }}
                            </button>
                        </div>
                    </section>

                    @if ($historicalSuggestionCommentId === $selectedComment->id && filled($historicalReplySuggestion))
                        <section class="smart-drawer-card is-ai" x-show="tab === 'conversation'" x-cloak>
                            <div class="smart-drawer-card-kicker">Gemini / auditoria</div>
                            <div class="smart-drawer-card-title">Sugerencia basada en historial</div>
                            <p class="smart-drawer-card-text">{{ $historicalReplySuggestion }}</p>
                        </section>
                    @endif

                    {{-- Card 2: Pulso del Cliente --}}
                    <section class="smart-drawer-card" x-show="tab === 'activity'" x-cloak>
                        <div class="smart-drawer-card-kicker">Actividad</div>
                        <div class="smart-drawer-timeline">
                            @forelse ($drawerTimeline as $event)
                                <div class="smart-drawer-timeline-item">
                                    <span class="smart-activity-icon {{ $event['color'] }}" aria-hidden="true">
                                        @switch($event['icon'])
                                            @case('eye')
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                                @break
                                            @case('arrow-path')
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M21.015 4.356v4.992m0 0h-4.992m4.992 0-3.181-3.183a8.25 8.25 0 0 0-13.803 3.7" /></svg>
                                                @break
                                            @case('clock')
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                @break
                                            @case('fire')
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 0 0 2.648-6.402 4.5 4.5 0 0 1-2.647-1.359 4.5 4.5 0 0 0-2.648 1.359A3.75 3.75 0 0 0 12 18Z" /></svg>
                                                @break
                                            @case('cursor-arrow-rays')
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m15.042 21.672-3.684-7.377m0 0-3.684 7.377m3.684-7.377L21 3 3 10.671l8.358 3.624Z" /></svg>
                                                @break
                                            @case('play')
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" /></svg>
                                                @break
                                            @case('check-circle')
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                @break
                                            @case('chat-bubble-left')
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                                                @break
                                            @default
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                                        @endswitch
                                    </span>

                                    <div>
                                        <div class="smart-activity-title">{{ $event['label'] }}</div>
                                        <div class="smart-activity-meta">
                                            {{ $event['date'] ?: 'Fecha no registrada' }}{{ $event['duration'] ? ' / '.$event['duration'].'s de actividad' : '' }}
                                        </div>
                                        <p class="smart-activity-note">
                                            @switch($event['group'])
                                                @case('conversion')
                                                    El lead avanzo hacia WhatsApp desde el Smart Link.
                                                    @break
                                                @case('video')
                                                    Interaccion con el contenido visual del tratamiento.
                                                    @break
                                                @case('engagement')
                                                    Senal de interes durante la visita a la landing.
                                                    @break
                                                @case('navigation')
                                                    Visita registrada en el Smart Link rastreable.
                                                    @break
                                                @default
                                                    Evento registrado en el flujo digital del lead.
                                            @endswitch
                                        </p>

                                        @if ($event['progress'])
                                            <div class="smart-activity-progress" aria-label="Progreso {{ $event['progress'] }}%">
                                                <span class="smart-activity-progress-track">
                                                    <span class="smart-activity-progress-fill" style="width: {{ $event['progress'] }}%"></span>
                                                </span>
                                                <span>{{ $event['progress'] }}%</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="smart-activity-empty">Sin eventos de Smart Link todavia.</p>
                            @endforelse
                        </div>
                    </section>
                </div>

                <footer class="smart-drawer-footer">
                    <a class="smart-action primary" href="{{ \App\Filament\Resources\SocialComments\SocialCommentResource::getUrl('view', ['record' => $selectedComment]) }}">Ver caso completo</a>
                    @if ($drawerPatient)
                        <a class="smart-action" href="{{ \App\Filament\Resources\Patients\PatientResource::getUrl('edit', ['record' => $drawerPatient]) }}">Ver ficha</a>
                    @else
                        <a class="smart-action" href="{{ \App\Filament\Resources\SocialComments\SocialCommentResource::getUrl('view', ['record' => $selectedComment]) }}">Crear ficha</a>
                    @endif
                </footer>
            </aside>
        @endif

        @if ($whatsappModalOpen)
            <div class="smart-modal-backdrop" wire:key="whatsapp-bridge-modal">
                <section class="smart-modal" role="dialog" aria-modal="true" aria-labelledby="whatsapp-modal-title">
                    <button class="smart-modal-close" type="button" wire:click="closeWhatsappModal" aria-label="Cerrar modal">×</button>

                    <header class="smart-modal-header">
                        <h2 id="whatsapp-modal-title">Derivar lead a WhatsApp</h2>
                        <p class="smart-muted">Personaliza el Smart Link, revisa el contenido visual y copia la respuesta final para publicar en Instagram o Facebook.</p>
                    </header>

                    <div class="smart-modal-layout">
                        <div class="smart-modal-column">
                            <section class="smart-modal-section is-soft">
                                <div class="smart-preview-kicker">Seguimiento</div>

                                <label>
                                    <span class="smart-field-label">Token</span>
                                    <div class="smart-copy-control">
                                        <input class="smart-copy-field" type="text" value="{{ $whatsappToken }}" readonly>
                                        <button class="smart-copy-icon" type="button" x-data @click="copySmartField(@js($whatsappToken), 'Token copiado')" aria-label="Copiar token" @disabled(! $whatsappGenerated)>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125v-9.75c0-.621.504-1.125 1.125-1.125H8.25m7.5 7.5h3.375c.621 0 1.125-.504 1.125-1.125v-9.75c0-.621-.504-1.125-1.125-1.125h-9.75A1.125 1.125 0 0 0 8.25 6.375v3.375" />
                                            </svg>
                                        </button>
                                    </div>
                                </label>

                                <label>
                                    <span class="smart-field-label">Link WhatsApp</span>
                                    <div class="smart-copy-control">
                                        <input class="smart-copy-field" type="text" value="{{ $whatsappLink ?: 'Configura WHATSAPP_BUSINESS_PHONE para generar link directo' }}" readonly>
                                        <button class="smart-copy-icon" type="button" x-data @click="copySmartField(@js($whatsappLink), 'Link de WhatsApp copiado')" aria-label="Copiar link WhatsApp" @disabled(blank($whatsappLink))>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125v-9.75c0-.621.504-1.125 1.125-1.125H8.25m7.5 7.5h3.375c.621 0 1.125-.504 1.125-1.125v-9.75c0-.621-.504-1.125-1.125-1.125h-9.75A1.125 1.125 0 0 0 8.25 6.375v3.375" />
                                            </svg>
                                        </button>
                                    </div>
                                </label>

                                <label>
                                    <span class="smart-field-label">Smart Link rastreable</span>
                                    <div class="smart-copy-control">
                                        <input class="smart-copy-field" type="text" value="{{ $smartLink ?: 'Se generara al confirmar' }}" readonly>
                                        <button class="smart-copy-icon" type="button" x-data @click="copySmartField(@js($smartLink), 'Smart Link copiado')" aria-label="Copiar Smart Link" @disabled(blank($smartLink))>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125v-9.75c0-.621.504-1.125 1.125-1.125H8.25m7.5 7.5h3.375c.621 0 1.125-.504 1.125-1.125v-9.75c0-.621-.504-1.125-1.125-1.125h-9.75A1.125 1.125 0 0 0 8.25 6.375v3.375" />
                                            </svg>
                                        </button>
                                    </div>
                                </label>
                            </section>

                            <section class="smart-modal-section">
                                <div class="smart-section-head">
                                    <div>
                                        <div class="smart-preview-kicker">Texto final para copiar y responder</div>
                                        <p class="smart-preview-copy">Mensaje listo para copiar y responder al comentario.</p>
                                    </div>

                                    @if ($whatsappGenerated)
                                        <button class="smart-action with-icon" type="button" x-data @click="copySmartField(@js($whatsappReplyText), 'Texto copiado')">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125v-9.75c0-.621.504-1.125 1.125-1.125H8.25m7.5 7.5h3.375c.621 0 1.125-.504 1.125-1.125v-9.75c0-.621-.504-1.125-1.125-1.125h-9.75A1.125 1.125 0 0 0 8.25 6.375v3.375" />
                                            </svg>
                                            <span>Copiar texto</span>
                                        </button>
                                    @endif
                                </div>

                                @if ($whatsappGenerated)
                                    <textarea class="smart-copy-field" style="min-height: 120px; resize: vertical;" rows="4" readonly>{{ $whatsappReplyText }}</textarea>
                                @else
                                    <div class="smart-modal-note">
                                        El texto final aparecera despues de generar el seguimiento. El token no se crea hasta confirmar.
                                    </div>
                                @endif
                            </section>

                        </div>

                        <div class="smart-modal-column">
                            <section class="smart-modal-section">
                                <label>
                                    <span class="smart-field-label">Procedimiento de interes</span>
                                    <select class="smart-copy-field" wire:model.live="whatsappProcedureId">
                                        <option value="">Sin definir / contenido generico</option>
                                        @foreach ($whatsappProcedureOptions as $procedureId => $procedureName)
                                            <option value="{{ $procedureId }}">{{ $procedureName }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                @if ($smartLinkPreview['uses_unknown'] ?? false)
                                    <div class="smart-modal-warning">
                                        Este Smart Link usara contenido generico. Selecciona un procedimiento con categoria configurada para mostrar una landing personalizada.
                                    </div>
                                @endif
                            </section>

                            <div class="smart-preview-stack">
                                <section class="smart-preview-card">
                                    <div class="smart-preview-kicker">Preview del Smart Link</div>
                                    <div class="smart-preview-title">{{ $smartLinkPreview['title'] ?? 'Tu sonrisa merece un plan claro, humano y sin presion.' }}</div>
                                    <div class="smart-preview-copy">{{ $smartLinkPreview['subtitle'] ?? 'Mira como trabajamos y continua por WhatsApp para recibir orientacion de la clinica.' }}</div>
                                </section>

                                <section class="smart-preview-card">
                                    <div class="smart-preview-kicker">Contenido seleccionado</div>
                                    <div class="smart-meta">
                                        <span><strong>Procedimiento:</strong> {{ $smartLinkPreview['procedure'] ?? 'Sin definir' }}</span>
                                        <span><strong>Clave:</strong> {{ $smartLinkPreview['category'] ?? 'unknown' }}</span>
                                        <span><strong>Etiqueta:</strong> {{ $smartLinkPreview['eyebrow'] ?? 'Valoracion dental personalizada' }}</span>
                                        <span><strong>Visual:</strong> {{ $smartLinkPreview['visual_label'] ?? 'Diagnostico integral' }}</span>
                                        @if (filled($smartLinkPreview['video_url'] ?? null))
                                            <span class="url"><strong>Video:</strong> {{ $smartLinkPreview['video_url'] }}</span>
                                        @endif
                                        @if (filled($smartLinkPreview['before_video_url'] ?? null))
                                            <span class="url"><strong>Antes:</strong> {{ $smartLinkPreview['before_video_url'] }}</span>
                                        @endif
                                        @if (filled($smartLinkPreview['after_video_url'] ?? null))
                                            <span class="url"><strong>Despues:</strong> {{ $smartLinkPreview['after_video_url'] }}</span>
                                        @endif
                                    </div>
                                </section>
                            </div>

                        </div>
                    </div>

                    <footer class="smart-modal-footer">
                        <button class="smart-action primary" type="button" wire:click="confirmWhatsappRouting">
                            {{ $whatsappGenerated ? 'Actualizar seguimiento' : 'Generar seguimiento' }}
                        </button>
                    </footer>
                </section>
            </div>
        @endif
    </section>

    <script>
        window.copySmartField = async function (text, toast = 'Copiado') {
            if (! text || ! navigator.clipboard) {
                return;
            }

            try {
                await navigator.clipboard.writeText(text);

                if (window.FilamentNotification) {
                    new window.FilamentNotification()
                        .title(toast)
                        .success()
                        .send();
                }
            } catch (error) {
                console.warn('No se pudo copiar el texto.', error);

                if (window.FilamentNotification) {
                    new window.FilamentNotification()
                        .title('No se pudo copiar')
                        .danger()
                        .send();
                }
            }
        };

        window.addEventListener('social-whatsapp-link-generated', async (event) => {
            const text = event.detail?.text || '';

            if (! text || ! navigator.clipboard) {
                return;
            }

            try {
                await navigator.clipboard.writeText(text);
            } catch (error) {
                console.warn('No se pudo copiar automaticamente el link de WhatsApp.', error);
            }
        });
    </script>
</x-filament-panels::page>
