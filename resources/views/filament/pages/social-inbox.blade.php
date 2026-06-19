<x-filament-panels::page>
    @php
        $stats = $this->stats();
        $comments = $this->comments();
        $filters = [
            'leads' => ['label' => 'Leads', 'icon' => '🔥', 'count' => $stats['leads']],
            'crisis' => ['label' => 'Crisis', 'icon' => '🚨', 'count' => $stats['crisis']],
            'vip' => ['label' => 'Pacientes VIP', 'icon' => '🏥', 'count' => $stats['vip']],
            'medical' => ['label' => 'Atencion Medica', 'icon' => '🩺', 'count' => $stats['medical']],
            'all' => ['label' => 'Activos', 'icon' => '🔍', 'count' => $stats['all']],
            'archived' => ['label' => 'Archivados', 'icon' => '📦', 'count' => $stats['archived']],
        ];
        $selectedComment = $this->selectedComment();
        $selectedPatient = $selectedComment?->socialIdentity?->patient ?: $selectedComment?->convertedPatient;
        $selectedTimeline = $selectedComment ? $this->timelineEvents($selectedComment->id) : [];
        $selectedMilestones = $selectedComment ? $this->recentMilestones($selectedComment->id) : [];
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
            gap: 1rem;
            justify-content: flex-end;
            margin-bottom: 1rem;
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
            border-radius: .85rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            color: var(--inbox-ink);
            outline: none;
            padding: .82rem 1rem;
            width: min(100%, 32rem);
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
            color: #334155;
            display: inline-flex;
            font-size: .84rem;
            font-weight: 700;
            gap: .4rem;
            margin-bottom: -1px;
            padding: .65rem .05rem .8rem;
            transition: border-color .18s ease, color .18s ease;
        }

        .smart-filter:hover {
            color: var(--inbox-accent);
        }

        .smart-filter.is-active {
            border-bottom-color: var(--inbox-accent);
            color: var(--inbox-accent);
        }

        .smart-filter-icon {
            font-size: .9rem;
            line-height: 1;
        }

        .smart-filter-count {
            align-items: center;
            background: #f1f5f9;
            border-radius: 999px;
            color: #64748b;
            display: inline-flex;
            font-size: .72rem;
            font-weight: 800;
            height: 1.25rem;
            justify-content: center;
            min-width: 1.25rem;
            padding: 0 .38rem;
        }

        .smart-filter.is-active .smart-filter-count {
            background: color-mix(in oklch, var(--inbox-accent) 16%, white);
            color: var(--inbox-accent);
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
            background: rgba(15, 23, 42, .38);
            inset: 0;
            position: fixed;
            z-index: 48;
            animation: drawerBackdropIn .22s ease;
        }

        .smart-drawer-backdrop.is-closing {
            animation: drawerBackdropOut .18s ease forwards;
        }

        .smart-drawer {
            background: #ffffff;
            border-left: 1px solid #e5e7eb;
            box-shadow: -12px 0 56px -16px rgba(15, 23, 42, .32);
            display: flex;
            flex-direction: column;
            position: fixed;
            right: 0;
            top: 0;
            bottom: 0;
            width: min(100%, 58rem);
            z-index: 49;
            animation: drawerSlideIn .28s cubic-bezier(.16, 1, .3, 1);
        }

        .smart-drawer.is-closing {
            animation: drawerSlideOut .2s ease forwards;
        }

        @keyframes drawerSlideIn {
            from { transform: translateX(100%); opacity: .4; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes drawerSlideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
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
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            gap: .75rem;
            padding: .85rem 1rem;
            flex: 0 0 auto;
        }

        .smart-drawer-close {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
            color: #64748b;
            cursor: pointer;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 1.05rem;
            font-weight: 850;
            height: 1.85rem;
            justify-content: center;
            line-height: 1;
            transition: .14s ease;
            width: 1.85rem;
        }

        .smart-drawer-close:hover {
            background: #eef2f7;
            color: #0f172a;
        }

        .smart-drawer-title {
            color: #0f172a;
            font-size: .92rem;
            font-weight: 700;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .smart-drawer-subtitle {
            color: #64748b;
            font-size: .74rem;
            font-weight: 500;
            margin-left: auto;
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .smart-drawer-body {
            flex: 1 1 auto;
            overflow-y: auto;
            padding: 1rem;
            display: grid;
            gap: .75rem;
            align-content: start;
        }

        .smart-drawer-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: .75rem;
            display: grid;
            gap: .65rem;
            padding: .85rem;
        }

        .smart-drawer-card.is-accent {
            border-left: 3px solid #14b8a6;
        }

        .smart-drawer-card.is-ai {
            background: linear-gradient(180deg, #eef6ff, #ffffff);
            border-color: #bfdbfe;
        }

        .smart-drawer-card-kicker {
            color: #000000;
            font-size: .66rem;
            font-weight: 900;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .smart-drawer-card.is-ai .smart-drawer-card-kicker {
            color: #1d4ed8;
        }

        .smart-drawer-card-title {
            color: #0f172a;
            font-size: .88rem;
            font-weight: 700;
            margin: 0;
        }

        .smart-drawer-card-text {
            color: #334155;
            font-size: .84rem;
            line-height: 1.55;
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
            gap: .6rem;
            margin-top: .3rem;
        }

        .smart-drawer-timeline-item {
            border-left: 3px solid #14b8a6;
            padding-left: .65rem;
        }

        .smart-drawer-timeline-item strong {
            color: #0f172a;
            display: block;
            font-size: .8rem;
        }

        .smart-drawer-timeline-item span {
            color: #64748b;
            display: block;
            font-size: .74rem;
            margin-top: .1rem;
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

        .smart-thermo-fill.is-warm { background: #f97316; }
        .smart-thermo-fill.is-hot { background: #dc2626; }
        .smart-thermo-fill.is-max { background: #b91c1c; }

        .smart-thermo-state {
            border-radius: .375rem;
            font-size: .7rem;
            font-weight: 500;
            padding: .25rem .45rem;
        }

        .smart-thermo-state.is-cold { background: #eff6ff; color: #1d4ed8; }
        .smart-thermo-state.is-warm { background: #fff7ed; color: #c2410c; }
        .smart-thermo-state.is-hot,
        .smart-thermo-state.is-max { background: #fef2f2; color: #b91c1c; }

        .smart-milestones {
            display: grid;
            gap: .45rem;
            margin: .15rem 0 0;
            padding-left: 1rem;
        }

        .smart-milestones li {
            color: #334155;
            font-size: .82rem;
            line-height: 1.45;
        }

        @media (max-width: 760px) {
            .smart-drawer {
                width: 100%;
            }
        }

        .dark .smart-drawer {
            background: #0f172a;
            border-left-color: rgba(148, 163, 184, .18);
        }

        .dark .smart-drawer-header {
            border-bottom-color: rgba(148, 163, 184, .18);
        }

        .dark .smart-drawer-close {
            background: rgba(15, 23, 42, .86);
            border-color: rgba(148, 163, 184, .18);
            color: #94a3b8;
        }

        .dark .smart-drawer-close:hover {
            background: rgba(30, 41, 59, .86);
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

        .dark .smart-drawer-card-title {
            color: #e2e8f0;
        }

        .dark .smart-drawer-card-text {
            color: #cbd5e1;
        }

        .dark .smart-drawer-card.is-ai {
            background: linear-gradient(180deg, rgba(29, 78, 216, .18), rgba(15, 23, 42, .86));
            border-color: rgba(96, 165, 250, .24);
        }

        .dark .smart-drawer-timeline-item strong {
            color: #e2e8f0;
        }

        .dark .smart-drawer-timeline-item span {
            color: #94a3b8;
        }

        .dark .smart-thermo-score,
        .dark .smart-milestones li {
            color: #cbd5e1;
        }

        .dark .smart-thermo-track {
            background: rgba(148, 163, 184, .18);
        }

        .smart-card {
            background: var(--inbox-card);
            border: 1px solid #e5e7eb;
            border-top: 4px solid #14b8a6;
            border-radius: 1rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 18px 50px -48px rgba(15, 23, 42, .65);
            display: grid;
            gap: .9rem;
            overflow: hidden;
            padding: clamp(1rem, 1.5vw, 1.2rem);
            position: relative;
            transition: border-color .18s ease, box-shadow .18s ease;
        }

        .smart-card:hover {
            box-shadow: 0 2px 8px rgba(15, 23, 42, .06);
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
            box-shadow: 0 0 0 2px rgba(20, 184, 166, .18), 0 18px 60px -52px rgba(15, 23, 42, .9);
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
            font-weight: 850;
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
            font-weight: 800;
            line-height: 1.15;
            overflow-wrap: anywhere;
        }

        .smart-source,
        .smart-time {
            color: var(--inbox-muted);
            font-size: .78rem;
            font-weight: 700;
            margin-top: .25rem;
        }

        .smart-badges {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }

        .smart-badge {
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .02em;
            padding: .34rem .52rem;
            text-transform: uppercase;
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
            font-weight: 850;
            gap: .45rem;
            padding: .55rem .7rem;
        }

        .smart-stepper {
            align-items: center;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: .75rem;
            display: grid;
            gap: 0;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            list-style: none;
            margin: 0;
            overflow: hidden;
            padding: .55rem .6rem;
        }

        .smart-step {
            align-items: center;
            color: #64748b;
            display: flex;
            font-size: .73rem;
            font-weight: 700;
            gap: .42rem;
            min-width: 0;
            position: relative;
        }

        .smart-step:not(:last-child)::after {
            background: #e5e7eb;
            content: '';
            height: 1px;
            left: calc(1.45rem + .55rem);
            position: absolute;
            right: .65rem;
            top: 50%;
            transform: translateY(-50%);
            z-index: 0;
        }

        .smart-step-number,
        .smart-step-label {
            position: relative;
            z-index: 1;
        }

        .smart-step-number {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #dbe3ef;
            border-radius: 999px;
            color: #475569;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: .68rem;
            font-weight: 800;
            height: 1.45rem;
            justify-content: center;
            width: 1.45rem;
        }

        .smart-step-label {
            background: #ffffff;
            overflow: hidden;
            padding-right: .25rem;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .smart-step.is-active {
            color: #1d4ed8;
        }

        .smart-step.is-active .smart-step-number {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .smart-step.is-complete {
            color: #000000;
        }

        .smart-step.is-complete .smart-step-number {
            background: #ecfdf5;
            border-color: #99f6e4;
            color: #000000;
        }

        .smart-step.is-complete:not(:last-child)::after {
            background: #99f6e4;
        }

        @media (max-width: 760px) {
            .smart-stepper {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                row-gap: .55rem;
            }

            .smart-step:nth-child(2)::after {
                display: none;
            }
        }

        .smart-message {
            color: #111827;
            font-size: clamp(.98rem, 1.35vw, 1.08rem);
            font-weight: 650;
            letter-spacing: -.015em;
            line-height: 1.5;
            padding: .2rem 0;
        }

        .smart-panels {
            display: grid;
            gap: .75rem;
            grid-template-columns: minmax(0, 1fr);
        }

        @media (min-width: 760px) {
            .smart-panels {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .smart-panel {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: .85rem;
            padding: .85rem;
        }

        .smart-ai-panel {
            background: linear-gradient(180deg, #eef6ff, #ffffff);
            border-color: #bfdbfe;
            box-shadow: inset 0 0 0 1px rgba(59, 130, 246, .04);
        }

        .smart-ai-panel h3 {
            color: #1d4ed8;
        }

        .smart-panel h3 {
            color: #475569;
            font-size: .68rem;
            font-weight: 950;
            letter-spacing: .12em;
            margin: 0 0 .5rem;
            text-transform: uppercase;
        }

        .smart-panel-kicker {
            color: #64748b;
            font-size: .74rem;
            line-height: 1.45;
            margin: -.25rem 0 .6rem;
        }

        .smart-panel p,
        .smart-panel strong {
            color: #0f172a;
            font-size: .86rem;
            line-height: 1.45;
            margin: 0;
        }

        .smart-muted { color: #64748b !important; }

        .smart-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .smart-action {
            align-items: center;
            border: 1px solid transparent;
            border-radius: .55rem;
            display: inline-flex;
            font-size: .78rem;
            font-weight: 500;
            line-height: 1;
            padding: .58rem .78rem;
            transition: background-color .14s ease, border-color .14s ease, color .14s ease, filter .14s ease;
        }

        .smart-action:hover { filter: brightness(.98); }
        .smart-action.primary { background: #1d7afc; color: white; }
        .smart-action.success { background: #0f766e; color: white; }
        .smart-action.warning { background: #ffffff; border-color: #fed7aa; color: #b45309; }
        .smart-action.danger { background: #ffffff; border-color: #fecaca; color: #b91c1c; }
        .smart-action.muted { background: #ffffff; border-color: #e5e7eb; color: #475569; }
        .smart-action.muted:hover { background: #f9fafb; border-color: #d1d5db; color: #374151; }

        .smart-empty {
            align-items: center;
            background: #ffffff;
            border: 1px solid #e5e7eb;
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
            height: 8.25rem;
            margin-bottom: 1.1rem;
            object-fit: contain;
            width: 8.8rem;
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
            border-radius: 1.15rem;
            box-shadow: 0 24px 80px rgba(15, 23, 42, .28);
            display: grid;
            gap: 1rem;
            max-height: calc(100dvh - clamp(1.2rem, 3vh, 2rem));
            max-width: 78rem;
            overflow-y: auto;
            padding: 1.15rem;
            position: relative;
            width: min(100%, 78rem);
        }

        @supports not (height: 100dvh) {
            .smart-modal {
                max-height: calc(100vh - clamp(1.2rem, 3vh, 2rem));
            }
        }

        .smart-modal-close {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            color: #64748b;
            display: inline-flex;
            font-size: 1.05rem;
            font-weight: 850;
            height: 2.1rem;
            justify-content: center;
            line-height: 1;
            position: absolute;
            right: .85rem;
            top: .85rem;
            transition: .16s ease;
            width: 2.1rem;
        }

        .smart-modal-close:hover {
            background: #eef2f7;
            color: #0f172a;
        }

        .smart-modal-header {
            padding-right: 2.6rem;
        }

        .smart-modal h2 {
            color: #0f172a;
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0;
        }

        .smart-modal-layout {
            display: grid;
            gap: 1rem;
            grid-template-columns: minmax(0, 1fr);
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
            border-radius: .95rem;
            display: grid;
            gap: .7rem;
            padding: .9rem;
        }

        .smart-modal-section.is-soft {
            background: #f8fafc;
        }

        .smart-field-label {
            color: #000000;
            display: block;
            font-size: .72rem;
            font-weight: 800;
            margin-bottom: .28rem;
        }

        .smart-copy-field {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: .62rem;
            color: #0f172a;
            font-size: .8rem;
            line-height: 1.4;
            padding: .52rem .64rem;
            width: 100%;
            height: auto;
        }

        .smart-copy-field[readonly] {
            cursor: text;
        }

        .smart-modal-note {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: .75rem;
            color: #1e40af;
            font-size: .84rem;
            line-height: 1.5;
            padding: .75rem .85rem;
        }

        .smart-modal-warning {
            background: #fffbeb;
            border: 1px solid #fed7aa;
            border-radius: .75rem;
            color: #92400e;
            font-size: .84rem;
            line-height: 1.5;
            padding: .75rem .85rem;
        }

        .smart-preview-grid {
            display: grid;
            gap: .75rem;
            grid-template-columns: minmax(0, 1fr);
        }

        @media (min-width: 760px) {
            .smart-preview-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .smart-preview-card {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: .9rem;
            padding: .9rem;
        }

        .smart-preview-kicker {
            color: #000000;
            font-size: .68rem;
            font-weight: 900;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .smart-preview-title {
            color: #0f172a;
            font-size: 1.08rem;
            font-weight: 850;
            letter-spacing: -.025em;
            line-height: 1.15;
            margin-top: .35rem;
        }

        .smart-preview-copy {
            color: #475569;
            font-size: .82rem;
            line-height: 1.5;
            margin-top: .45rem;
        }

        .dark .smart-stepper,
        .dark .smart-copy-field {
            background: rgba(15, 23, 42, .86);
            border-color: rgba(148, 163, 184, .18);
            color: #cbd5e1;
        }

        .dark .smart-step {
            color: #94a3b8;
        }

        .dark .smart-step:not(:last-child)::after {
            background: rgba(148, 163, 184, .2);
        }

        .dark .smart-step-label {
            background: rgb(15, 23, 42);
        }

        .dark .smart-step-number {
            background: rgba(15, 23, 42, .9);
            border-color: rgba(148, 163, 184, .22);
            color: #cbd5e1;
        }

        .dark .smart-step.is-active {
            color: #93c5fd;
        }

        .dark .smart-step.is-active .smart-step-number {
            background: rgba(29, 78, 216, .24);
            border-color: rgba(96, 165, 250, .32);
            color: #93c5fd;
        }

        .dark .smart-step.is-complete {
            color: #5eead4;
        }

        .dark .smart-step.is-complete .smart-step-number {
            background: rgba(20, 184, 166, .16);
            border-color: rgba(45, 212, 191, .28);
            color: #5eead4;
        }

        .dark .smart-step.is-complete:not(:last-child)::after {
            background: rgba(45, 212, 191, .32);
        }

        .dark .smart-modal-note {
            background: rgba(29, 78, 216, .18);
            border-color: rgba(96, 165, 250, .24);
            color: #bfdbfe;
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
                    <span class="smart-filter-icon">{{ $item['icon'] }}</span>
                    <span>{{ $item['label'] }}</span>
                    <strong class="smart-filter-count">{{ $item['count'] }}</strong>
                </button>
            @endforeach
        </div>

        <div class="smart-list-column">
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
                        </div>
                    </div>

                    <div class="smart-message">"{{ $comment->comment_text }}"</div>

                    @if ($isDerived && filled($comment->tracking_token))
                        <div class="smart-token">
                            <span>Token WhatsApp:</span>
                            <strong>{{ $comment->tracking_token }}</strong>
                        </div>
                    @endif

                    <ol class="smart-stepper" aria-label="Flujo recomendado de seguimiento">
                        <li class="smart-step {{ $isDerived ? 'is-complete' : 'is-active' }}">
                            <span class="smart-step-number">1</span>
                            <span class="smart-step-label">Derivar</span>
                        </li>
                        <li class="smart-step {{ $isDerived ? 'is-active' : '' }}">
                            <span class="smart-step-number">2</span>
                            <span class="smart-step-label">Copiar texto</span>
                        </li>
                        <li class="smart-step">
                            <span class="smart-step-number">3</span>
                            <span class="smart-step-label">Responder</span>
                        </li>
                        <li class="smart-step">
                            <span class="smart-step-number">4</span>
                            <span class="smart-step-label">Seguimiento</span>
                        </li>
                    </ol>

                    <div class="smart-panels">
                        <section class="smart-panel">
                            <h3>Contexto Clinico</h3>
                            @if ($patient)
                                <p><strong>Paciente vinculado:</strong> {{ $patient->full_name }}</p>
                                <p class="smart-muted">{{ $lastActivity ? 'Ultima cita: ' . $lastActivity->activity_date?->format('d/m/Y') : 'Sin cita registrada' }}</p>
                                <p class="smart-muted">{{ $lastActivity?->doctor?->name ?: 'Doctor no registrado' }}</p>
                            @else
                                <p><strong>Nuevo lead:</strong> Sin ficha clinica</p>
                                <p class="smart-muted">Telefono: {{ $comment->socialIdentity?->phone ?: 'pendiente de capturar' }}</p>
                                <p class="smart-muted">Procedimiento: {{ $comment->suggestedProcedure?->name ?: 'sin sugerencia' }}</p>
                            @endif
                        </section>

                        <section class="smart-panel smart-ai-panel">
                            <h3>Respuesta base IA</h3>
                            <div class="smart-panel-kicker">Antes de derivar. No incluye token ni link de seguimiento.</div>
                            <p>{{ $comment->suggested_reply ?: 'Sin respuesta sugerida. Revisar contexto antes de responder.' }}</p>
                            @if ($comment->ai_reason)
                                <p class="smart-muted" style="margin-top:.45rem">Motivo: {{ $comment->ai_reason }}</p>
                            @endif
                        </section>
                    </div>

                    <div class="smart-actions">
                        @if ($isCrisis)
                            <button class="smart-action danger" type="button" wire:click="escalate({{ $comment->id }})">Escalar</button>
                        @endif

                        <button class="smart-action primary" type="button" wire:click="selectComment({{ $comment->id }})">Abrir 360</button>

                        @if ($isLead || blank($comment->tracking_token))
                            <button class="smart-action success" type="button" wire:click="routeToWhatsapp({{ $comment->id }})">
                                {{ $isDerived ? 'Ver texto de seguimiento' : 'Derivar' }}
                            </button>
                        @endif

                        @if ($patientUrl)
                            <a class="smart-action primary" href="{{ $patientUrl }}">Ver ficha</a>
                        @else
                            <a class="smart-action primary" href="{{ $detailUrl }}">Crear ficha</a>
                        @endif

                        <a class="smart-action muted" href="{{ $detailUrl }}">Detalle</a>
                        <button class="smart-action muted" type="button" wire:click="markReviewed({{ $comment->id }})">Revisado</button>
                        <button class="smart-action warning" type="button" wire:click="ignore({{ $comment->id }})">Ignorar</button>
                        <button class="smart-action danger" type="button" wire:click="markSpam({{ $comment->id }})">Spam</button>
                    </div>
                </article>
            @empty
                <div class="smart-empty">
                    <img class="smart-empty-illustration" src="{{ asset('images/social-empty-comments.svg') }}" alt="" aria-hidden="true">
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
                $engagementScore = (int) $selectedComment->recent_engagement_score;
                $displayEngagementScore = min($engagementScore, 100);
                $engagementState = match (true) {
                    $engagementScore >= 100 => ['label' => 'Alta prioridad', 'class' => 'is-max'],
                    $engagementScore >= 71 => ['label' => 'Caliente', 'class' => 'is-hot'],
                    $engagementScore >= 31 => ['label' => 'Tibio', 'class' => 'is-warm'],
                    default => ['label' => 'Frio', 'class' => 'is-cold'],
                };
            @endphp
            <div class="smart-drawer-backdrop" wire:click="closeCommentDrawer"></div>

            <aside class="smart-drawer" x-data @keydown.escape.window="$wire.closeCommentDrawer()">
                <div class="smart-drawer-header">
                    <button class="smart-drawer-close" type="button" wire:click="closeCommentDrawer" aria-label="Cerrar detalle">&times;</button>
                    <div class="smart-drawer-title">
                        {{ $selectedComment->author_username ? '@'.$selectedComment->author_username : ($selectedComment->author_name ?: 'Lead seleccionado') }}
                    </div>
                    <span class="smart-drawer-subtitle">
                        {{ $selectedComment->platform->label() }} / {{ $selectedComment->conversion_status?->label() ?? 'Sin estado' }}
                    </span>
                </div>

                <div class="smart-drawer-body">
                    <section class="smart-drawer-card">
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

                        <div class="smart-drawer-card-kicker">Hitos recientes</div>
                        @if ($selectedMilestones !== [])
                            <ul class="smart-milestones">
                                @foreach ($selectedMilestones as $milestone)
                                    <li>{{ $milestone }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="smart-drawer-card-text smart-drawer-muted">Sin hitos recientes de alta intensidad.</p>
                        @endif
                    </section>

                    {{-- Card 1: Conversacion Detalle --}}
                    <section class="smart-drawer-card is-accent">
                        <div class="smart-drawer-card-kicker">Conversacion / Detalle</div>
                        <p class="smart-drawer-card-text">"{{ $selectedComment->comment_text }}"</p>

                        <div class="smart-panels" style="margin-top:.5rem">
                            <section class="smart-panel">
                                <h3>Respuesta sugerida actual</h3>
                                <p>{{ $selectedComment->suggested_reply ?: 'Sin respuesta base. Usa la accion IA para generar una respuesta con historial.' }}</p>
                                @if ($selectedComment->ai_reason)
                                    <p class="smart-muted" style="margin-top:.4rem">Motivo: {{ $selectedComment->ai_reason }}</p>
                                @endif
                            </section>
                            <section class="smart-panel">
                                <h3>Seguimiento comercial</h3>
                                <p><strong>Estado:</strong> {{ $selectedComment->conversion_status?->label() ?? 'Sin estado' }}</p>
                                <p class="smart-muted"><strong>Pipeline:</strong> {{ $selectedComment->pipeline_stage?->label() ?? 'Sin etapa' }}</p>
                                <p class="smart-muted"><strong>Valor:</strong> ${{ number_format((float) $selectedComment->estimated_value, 2) }}</p>
                            </section>
                        </div>

                        <div class="smart-drawer-actions">
                            <button class="smart-action success" type="button" wire:click="suggestHistoricalReply({{ $selectedComment->id }})">
                                Sugerir respuesta basada en historial
                            </button>
                            <a class="smart-action muted" href="{{ \App\Filament\Resources\SocialComments\SocialCommentResource::getUrl('view', ['record' => $selectedComment]) }}">Ver caso completo</a>
                        </div>
                    </section>

                    @if ($historicalSuggestionCommentId === $selectedComment->id && filled($historicalReplySuggestion))
                        <section class="smart-drawer-card is-ai">
                            <div class="smart-drawer-card-kicker">Gemini / auditoria</div>
                            <div class="smart-drawer-card-title">Sugerencia basada en historial</div>
                            <p class="smart-drawer-card-text">{{ $historicalReplySuggestion }}</p>
                        </section>
                    @endif

                    {{-- Card 2: Mini CRM --}}
                    <section class="smart-drawer-card">
                        <div class="smart-drawer-card-kicker">Mini CRM</div>
                        <div class="smart-drawer-card-title">{{ $drawerPatient?->full_name ?? 'Lead sin ficha clinica' }}</div>
                        <p class="smart-drawer-card-text">
                            Score: {{ $selectedComment->interest_score ?? 0 }}
                            @if (filled($selectedComment->hot_lead_at))
                                / Hot lead desde {{ $selectedComment->hot_lead_at->diffForHumans() }}
                            @endif
                            @if (filled($selectedComment->reheated_at))
                                / Recalentado {{ $selectedComment->reheated_at->diffForHumans() }}
                            @endif
                        </p>
                        <div class="smart-panels" style="margin-top:.3rem">
                            <section class="smart-panel">
                                <h3>Procedimiento</h3>
                                <p>{{ $selectedComment->suggestedProcedure?->name ?: 'Sin sugerencia' }}</p>
                            </section>
                            <section class="smart-panel">
                                <h3>Alertas</h3>
                                <p>{{ $selectedComment->leadAlerts?->whereNull('resolved_at')->count() ?: 0 }} abiertas</p>
                            </section>
                        </div>
                        <div class="smart-drawer-actions">
                            @if ($drawerPatient)
                                <a class="smart-action primary" href="{{ \App\Filament\Resources\Patients\PatientResource::getUrl('edit', ['record' => $drawerPatient]) }}">Ver ficha</a>
                            @else
                                <a class="smart-action primary" href="{{ \App\Filament\Resources\SocialComments\SocialCommentResource::getUrl('view', ['record' => $selectedComment]) }}">Crear ficha</a>
                            @endif
                        </div>
                    </section>

                    {{-- Card 3: Pulso del Cliente --}}
                    <section class="smart-drawer-card">
                        <div class="smart-drawer-card-kicker">Pulso del cliente</div>
                        <div class="smart-drawer-card-title">Timeline reciente</div>
                        <div class="smart-drawer-timeline">
                            @forelse ($drawerTimeline as $event)
                                <div class="smart-drawer-timeline-item">
                                    <strong>{{ $event['label'] }}</strong>
                                    <span>{{ $event['date'] }}{{ $event['duration'] ? ' / '.$event['duration'].'s' : '' }}</span>
                                </div>
                            @empty
                                <p class="smart-drawer-muted">Sin eventos de Smart Link todavia.</p>
                            @endforelse
                        </div>
                    </section>
                </div>
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
                                    <input class="smart-copy-field" type="text" value="{{ $whatsappToken }}" readonly>
                                </label>

                                <label>
                                    <span class="smart-field-label">Link WhatsApp</span>
                                    <input class="smart-copy-field" type="text" value="{{ $whatsappLink ?: 'Configura WHATSAPP_BUSINESS_PHONE para generar link directo' }}" readonly>
                                </label>

                                <label>
                                    <span class="smart-field-label">Smart Link rastreable</span>
                                    <input class="smart-copy-field" type="text" value="{{ $smartLink ?: 'Se generara al confirmar' }}" readonly>
                                </label>
                            </section>

                            <section class="smart-modal-section">
                                <div>
                                    <div class="smart-preview-kicker">Texto final para copiar y responder</div>
                                    <p class="smart-preview-copy">Mensaje listo para copiar y responder al comentario.</p>
                                </div>

                                @if ($whatsappGenerated)
                                    <textarea class="smart-copy-field" style="height: 200px;" rows="3" readonly>{{ $whatsappReplyText }}</textarea>
                                @else
                                    <div class="smart-modal-note">
                                        El texto final aparecera despues de generar el seguimiento. El token no se crea hasta confirmar.
                                    </div>
                                @endif
                            </section>

                            <div class="smart-actions">
                                <button class="smart-action success" type="button" wire:click="confirmWhatsappRouting">
                                    {{ $whatsappGenerated ? 'Actualizar y copiar texto' : 'Generar seguimiento' }}
                                </button>
                                @if ($whatsappGenerated && $whatsappLink)
                                    <button class="smart-action success" type="button" x-data @click="navigator.clipboard?.writeText(@js($whatsappLink))">Copiar link</button>
                                @endif
                                @if ($whatsappGenerated)
                                    <button class="smart-action warning" type="button" x-data @click="navigator.clipboard?.writeText(@js($smartLink))">Copiar Smart Link</button>
                                    <button class="smart-action primary" type="button" x-data @click="navigator.clipboard?.writeText(@js($whatsappReplyText))">Copiar texto</button>
                                @endif
                            </div>
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

                            <div class="smart-preview-grid">
                                <section class="smart-preview-card">
                                    <div class="smart-preview-kicker">Preview del Smart Link</div>
                                    <div class="smart-preview-title">{{ $smartLinkPreview['title'] ?? 'Tu sonrisa merece un plan claro, humano y sin presion.' }}</div>
                                    <div class="smart-preview-copy">{{ $smartLinkPreview['subtitle'] ?? 'Mira como trabajamos y continua por WhatsApp para recibir orientacion de la clinica.' }}</div>
                                </section>

                                <section class="smart-preview-card">
                                    <div class="smart-preview-kicker">Contenido seleccionado</div>
                                    <p><strong>Procedimiento:</strong> {{ $smartLinkPreview['procedure'] ?? 'Sin definir' }}</p>
                                    <p class="smart-muted"><strong>Clave:</strong> {{ $smartLinkPreview['category'] ?? 'unknown' }}</p>
                                    <p class="smart-muted"><strong>Etiqueta:</strong> {{ $smartLinkPreview['eyebrow'] ?? 'Valoracion dental personalizada' }}</p>
                                    <p class="smart-muted"><strong>Visual:</strong> {{ $smartLinkPreview['visual_label'] ?? 'Diagnostico integral' }}</p>
                                    @if (filled($smartLinkPreview['video_url'] ?? null))
                                        <p class="smart-muted"><strong>Video:</strong> {{ $smartLinkPreview['video_url'] }}</p>
                                    @endif
                                    @if (filled($smartLinkPreview['before_video_url'] ?? null))
                                        <p class="smart-muted"><strong>Antes:</strong> {{ $smartLinkPreview['before_video_url'] }}</p>
                                    @endif
                                    @if (filled($smartLinkPreview['after_video_url'] ?? null))
                                        <p class="smart-muted"><strong>Despues:</strong> {{ $smartLinkPreview['after_video_url'] }}</p>
                                    @endif
                                </section>
                            </div>

                        </div>
                    </div>
                </section>
            </div>
        @endif
    </section>

    <script>
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
