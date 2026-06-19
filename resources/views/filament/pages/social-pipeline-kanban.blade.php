<x-filament-panels::page>
    @php
        $columns = $this->columns();
        $stageCounts = $this->stageCounts();
        $stageTotals = $this->stageTotals();
        $currency = 'USD';
    @endphp

    <style>
        .pipeline-kanban {
            --pk-accent: #1d7afc;
            --pk-ink: #0f172a;
            --pk-muted: #64748b;
            --pk-card-bg: #ffffff;
            --pk-column-bg: #ffffff;
            --pk-border: #e5e7eb;
            color: var(--pk-ink);
            margin-top: -.25rem;
        }

        .kanban-toolbar {
            align-items: center;
            display: flex;
            gap: .75rem;
            justify-content: flex-end;
            margin-bottom: .9rem;
        }

        @media (min-width: 900px) {
            .kanban-toolbar {
                margin-top: -4.05rem;
            }
        }

        .kanban-search {
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

        .kanban-board {
            display: grid;
            gap: .75rem;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            min-height: 70vh;
            overflow-x: auto;
            padding-bottom: .75rem;
        }

        @media (max-width: 1400px) {
            .kanban-board {
                grid-template-columns: repeat(3, minmax(280px, 1fr));
            }
        }

        @media (max-width: 900px) {
            .kanban-board {
                grid-template-columns: repeat(2, minmax(260px, 1fr));
            }
        }

        @media (max-width: 600px) {
            .kanban-board {
                grid-template-columns: minmax(260px, 1fr);
            }
        }

        .kanban-column {
            background: var(--pk-column-bg);
            border: 1px solid var(--pk-border);
            border-radius: .7rem;
            display: flex;
            flex-direction: column;
            gap: 0;
            max-height: calc(100dvh - 14rem);
            overflow: hidden;
        }

        .kanban-column-header {
            align-items: center;
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            border-radius: .7rem .7rem 0 0;
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            padding: .78rem .8rem;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .kanban-column-label {
            color: var(--pk-ink);
            font-size: .86rem;
            font-weight: 600;
            letter-spacing: 0;
        }

        .kanban-column-count {
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
        }

        .kanban-column-total {
            color: #000000;
            font-size: .72rem;
            font-weight: 500;
            margin-left: auto;
            white-space: nowrap;
        }

        .kanban-cards {
            display: flex;
            flex-direction: column;
            gap: .6rem;
            overflow-y: auto;
            padding: .65rem;
            flex: 1;
            min-height: 4rem;
        }

        .kanban-cards.is-empty {
            align-items: center;
            justify-content: center;
        }

        .kanban-empty-column {
            color: var(--pk-muted);
            font-size: .75rem;
            font-weight: 500;
            padding: 1rem;
            text-align: center;
        }

        .kanban-card {
            background: var(--pk-card-bg);
            border: 1px solid var(--pk-border);
            border-radius: .58rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .035);
            cursor: grab;
            display: grid;
            gap: .58rem;
            padding: .78rem;
            transition: box-shadow .16s ease, border-color .16s ease;
            user-select: none;
        }

        .kanban-card:hover {
            border-color: #d1d5db;
            box-shadow: 0 2px 6px rgba(15, 23, 42, .045);
        }

        .kanban-card.is-dragging {
            opacity: .5;
            transform: rotate(2deg);
        }

        .kanban-card.drag-over {
            border-color: var(--pk-accent);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, .24);
        }

        .kanban-card.temp-cold { border-top: 2px solid #3b82f6; }
        .kanban-card.temp-warm { border-top: 2px solid #f59e0b; }
        .kanban-card.temp-hot,
        .kanban-card.temp-max { border-top: 2px solid #ef4444; }

        .kanban-card-top {
            align-items: start;
            display: flex;
            gap: .5rem;
            justify-content: space-between;
        }

        .kanban-card-name {
            color: var(--pk-ink);
            font-size: .86rem;
            font-weight: 600;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .kanban-card-meta {
            color: var(--pk-muted);
            font-size: .7rem;
            font-weight: 400;
            margin-top: .2rem;
        }

        .kanban-badges {
            display: flex;
            flex-wrap: wrap;
            gap: .3rem;
        }

        .kanban-badge {
            align-items: center;
            border: 1px solid #e5e7eb;
            border-radius: .35rem;
            display: inline-flex;
            font-size: .68rem;
            font-weight: 500;
            gap: .28rem;
            padding: .22rem .42rem;
        }

        .kanban-badge::before,
        .kanban-temperature::before {
            background: #94a3b8;
            border-radius: 999px;
            content: '';
            height: .42rem;
            width: .42rem;
        }

        .kanban-badge-hot { background: #fff7ed; border-color: #fed7aa; color: #c2410c; }
        .kanban-badge-cold { background: #f8fafc; color: #475569; }
        .kanban-badge-followup { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
        .kanban-badge-score { background: #ecfdf5; border-color: #bbf7d0; color: #047857; }

        .kanban-badge-live { background: #ecfdf5; border-color: #bbf7d0; color: #047857; }
        .kanban-badge-recent { background: #fff7ed; border-color: #fed7aa; color: #c2410c; }
        .kanban-badge-live::before { background: #22c55e; }
        .kanban-badge-recent::before { background: #f59e0b; }

        .kanban-score-panel {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 0;
            display: flex;
            justify-content: space-between;
            gap: .5rem;
            padding: 0;
        }

        .kanban-score-value {
            color: #000000;
            font-size: 1rem;
            font-weight: 600;
            line-height: 1;
        }

        .kanban-temperature {
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

        .kanban-temperature.temp-cold { color: #1d4ed8; }
        .kanban-temperature.temp-cold::before { background: #3b82f6; }
        .kanban-temperature.temp-warm { color: #c2410c; }
        .kanban-temperature.temp-warm::before { background: #f59e0b; }
        .kanban-temperature.temp-hot,
        .kanban-temperature.temp-max { color: #b91c1c; }
        .kanban-temperature.temp-hot::before,
        .kanban-temperature.temp-max::before { background: #ef4444; }

        .kanban-score-bar {
            background: #e5e7eb;
            border-radius: 999px;
            display: block;
            height: .25rem;
            overflow: hidden;
            width: 100%;
        }

        .kanban-score-fill {
            background: var(--pk-accent);
            border-radius: inherit;
            height: 100%;
            transition: width .25s ease;
        }

        .kanban-score-fill.temp-cold { background: #2563eb; }
        .kanban-score-fill.temp-warm { background: #f97316; }
        .kanban-score-fill.temp-hot,
        .kanban-score-fill.temp-max { background: #dc2626; }

        .kanban-card-comment {
            color: #334155;
            font-size: .78rem;
            line-height: 1.4;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .kanban-card-footer {
            align-items: center;
            display: flex;
            gap: .5rem;
            justify-content: space-between;
        }

        .kanban-estimated {
            align-items: center;
            display: flex;
            gap: .3rem;
        }

        .kanban-estimated-input {
            background: transparent;
            border: 1px solid transparent;
            border-radius: .35rem;
            color: #000000;
            font-size: .74rem;
            font-weight: 500;
            height: 1.75rem;
            outline: none;
            padding: .18rem .32rem;
            transition: .14s ease;
            width: 4.75rem;
        }

        .kanban-estimated-input:hover,
        .kanban-estimated-input:focus {
            background: #f8fafc;
            border-color: var(--pk-border);
        }

        .kanban-card-actions {
            display: flex;
            gap: .25rem;
        }

        .kanban-card-action {
            align-items: center;
            background: transparent;
            border: 1px solid transparent;
            border-radius: .35rem;
            color: var(--pk-muted);
            cursor: pointer;
            display: inline-flex;
            font-size: .72rem;
            font-weight: 500;
            padding: .28rem .42rem;
            text-decoration: none;
            transition: .12s ease;
        }

        .kanban-card-action:hover {
            background: #f9fafb;
            border-color: #e5e7eb;
            color: var(--pk-ink);
        }

        .kanban-drop-zone {
            border: 2px dashed transparent;
            border-radius: .5rem;
            transition: border-color .16s ease, background .16s ease;
        }

        .kanban-drop-zone.drag-over {
            background: rgba(59, 130, 246, .06);
            border-color: var(--pk-accent);
        }

        .kanban-archive-bar {
            bottom: 1rem;
            display: none;
            gap: .75rem;
            left: 50%;
            max-width: 52rem;
            position: fixed;
            transform: translateX(-50%);
            width: calc(100% - 2rem);
            z-index: 55;
        }

        .pipeline-kanban.is-dragging .kanban-archive-bar {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .kanban-archive-target {
            background: #ffffff;
            border: 1px dashed var(--pk-border);
            border-radius: .65rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            color: #334155;
            display: grid;
            gap: .15rem;
            min-height: 3.85rem;
            padding: .75rem 1rem;
            place-items: center;
            text-align: center;
        }

        .kanban-archive-target strong {
            color: #000000;
            font-size: .84rem;
            font-weight: 600;
        }

        .kanban-archive-target span {
            color: var(--pk-muted);
            font-size: .74rem;
        }

        .kanban-archive-target.archive-lost.drag-over {
            background: #fef2f2;
            border-color: #fecaca;
        }

        .kanban-archive-target.archive-won.drag-over {
            background: #ecfdf5;
            border-color: #bbf7d0;
        }

        /* Lost modal */
        .kanban-modal-backdrop {
            align-items: center;
            background: rgba(15, 23, 42, .48);
            display: flex;
            inset: 0;
            justify-content: center;
            padding: clamp(.6rem, 1.5vh, 1rem);
            position: fixed;
            z-index: 60;
        }

        .kanban-modal {
            background: #ffffff;
            border-radius: 1.15rem;
            box-shadow: 0 24px 80px rgba(15, 23, 42, .28);
            display: grid;
            gap: 1rem;
            max-width: 28rem;
            padding: 1.25rem;
            width: min(100%, 28rem);
        }

        .kanban-modal h3 {
            color: #b91c1c;
            font-size: 1rem;
            font-weight: 600;
            margin: 0;
        }

        .kanban-modal p {
            color: #475569;
            font-size: .86rem;
            line-height: 1.5;
            margin: 0;
        }

        .kanban-modal-actions {
            display: flex;
            gap: .5rem;
            justify-content: flex-end;
        }

        .kanban-modal-btn {
            border: 1px solid var(--pk-border);
            border-radius: .55rem;
            font-size: .82rem;
            font-weight: 500;
            line-height: 1;
            padding: .55rem 1rem;
            transition: .14s ease;
        }

        .kanban-modal-btn-primary {
            background: #dc2626;
            border-color: #dc2626;
            color: #ffffff;
        }

        .kanban-modal-btn-primary:hover {
            background: #b91c1c;
        }

        .kanban-modal-btn-soft {
            background: #ffffff;
            color: #475569;
        }

        .kanban-modal-btn-soft:hover {
            background: #f8fafc;
        }

        .dark .pipeline-kanban {
            --pk-column-bg: rgba(15, 23, 42, .72);
            --pk-card-bg: rgba(15, 23, 42, .86);
            --pk-border: rgba(148, 163, 184, .16);
            --pk-ink: #e5e7eb;
            --pk-muted: #94a3b8;
        }

        .dark .kanban-column-header {
            background: rgba(15, 23, 42, .9);
        }

        .dark .kanban-column-count {
            background: rgba(148, 163, 184, .2);
            color: #cbd5e1;
        }

        .dark .kanban-card-comment {
            color: #94a3b8;
        }

        .dark .kanban-card-action:hover {
            background: rgba(148, 163, 184, .12);
        }

        .dark .kanban-score-panel,
        .dark .kanban-archive-target {
            border-color: var(--pk-border);
        }

        .dark .kanban-archive-target,
        .dark .kanban-temperature {
            background: rgba(15, 23, 42, .86);
        }

        .dark .kanban-score-value,
        .dark .kanban-archive-target strong {
            color: #e5e7eb;
        }

        .dark .kanban-search {
            background: rgba(15, 23, 42, .86);
            border-color: var(--pk-border);
            color: #e5e7eb;
        }

        .dark .kanban-modal {
            background: #1e293b;
            border-color: var(--pk-border);
        }

        .dark .kanban-modal p {
            color: #94a3b8;
        }

        .dark .kanban-modal-btn-soft {
            background: transparent;
            color: #cbd5e1;
        }

        .dark .kanban-estimated-input {
            color: #e5e7eb;
        }

        .dark .kanban-estimated-input:hover,
        .dark .kanban-estimated-input:focus {
            background: rgba(15, 23, 42, .72);
            border-color: rgba(148, 163, 184, .2);
        }
    </style>

    <section class="pipeline-kanban">
        <div class="kanban-toolbar">
            <input
                class="kanban-search"
                type="search"
                wire:model.live.debounce.350ms="search"
                placeholder="Buscar leads por nombre o comentario..."
            />
        </div>

        <div class="kanban-board">
            @foreach ($columns as $stageValue => $stageLabel)
                @php
                    $count = $stageCounts[$stageValue] ?? 0;
                    $total = $stageTotals[$stageValue] ?? 0;
                    $cards = $this->cards($stageValue);
                @endphp

                <div class="kanban-column" data-stage="{{ $stageValue }}">
                    <div class="kanban-column-header">
                        <span class="kanban-column-label">{{ $stageLabel }}</span>
                        <span class="kanban-column-count">{{ $count }}</span>
                        @if ($total > 0)
                            <span class="kanban-column-total">{{ number_format($total, 2) }} {{ $currency }}</span>
                        @endif
                    </div>

                    <div
                        class="kanban-cards @if ($cards->isEmpty()) is-empty @endif"
                        data-stage="{{ $stageValue }}"
                        ondragover="event.preventDefault(); this.closest('.kanban-column').classList.add('drag-over');"
                        ondragleave="this.closest('.kanban-column').classList.remove('drag-over');"
                        ondrop="handleCardDrop(event, '{{ $stageValue }}')"
                    >
                        @if ($cards->isNotEmpty())
                            @foreach ($cards as $comment)
                                @php
                                    $patient = $comment->socialIdentity?->patient ?: $comment->convertedPatient;
                                    $detailUrl = \App\Filament\Resources\SocialComments\SocialCommentResource::getUrl('view', ['record' => $comment]);
                                    $patientUrl = $patient ? \App\Filament\Resources\Patients\PatientResource::getUrl('edit', ['record' => $patient]) : null;
                                    $realScore = (int) $comment->recent_engagement_score;
                                    $displayScore = min(100, max(0, $realScore));
                                    $temperature = match (true) {
                                        $realScore >= 100 => ['label' => 'MAX', 'class' => 'temp-max'],
                                        $realScore >= 71 => ['label' => 'Hot', 'class' => 'temp-hot'],
                                        $realScore >= 31 => ['label' => 'Tibio', 'class' => 'temp-warm'],
                                        default => ['label' => 'Frio', 'class' => 'temp-cold'],
                                    };
                                    $leadName = $comment->author_username ? '@' . $comment->author_username : ($comment->author_name ?: 'Lead social');
                                    $lastEngagementMinutes = $comment->last_engagement_at?->diffInMinutes(now());
                                    $isLive = $lastEngagementMinutes !== null && $lastEngagementMinutes <= 2;
                                    $isRecent = $lastEngagementMinutes !== null && $lastEngagementMinutes > 2 && $lastEngagementMinutes <= 10;
                                    $isFollowUpToday = filled($comment->follow_up_at) && $comment->follow_up_at->isToday();
                                @endphp

                                <div
                                    class="kanban-card stage-{{ $stageValue }} {{ $temperature['class'] }}"
                                    draggable="true"
                                    data-comment-id="{{ $comment->id }}"
                                    data-stage="{{ $stageValue }}"
                                    ondragstart="handleDragStart(event)"
                                    ondragend="handleDragEnd(event)"
                                >
                                    <div class="kanban-card-top">
                                        <div>
                                            <div class="kanban-card-name">{{ $leadName }}</div>
                                            <div class="kanban-card-meta">
                                                {{ $comment->platform?->label() ?? 'Social' }} · {{ $comment->created_at?->diffForHumans() }}
                                            </div>
                                        </div>

                                        <div class="kanban-badges" style="flex-wrap:nowrap">
                                            @if ($isLive)
                                                <span class="kanban-badge kanban-badge-live">En vivo</span>
                                            @elseif ($isRecent)
                                                <span class="kanban-badge kanban-badge-recent">Reciente</span>
                                            @endif
                                            @if ($isFollowUpToday)
                                                <span class="kanban-badge kanban-badge-followup">Hoy</span>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($comment->suggestedProcedure?->name)
                                        <div style="font-size:.72rem;color:var(--pk-muted);font-weight:600">
                                            {{ $comment->suggestedProcedure->name }}
                                        </div>
                                    @endif

                                    <div class="kanban-score-panel">
                                        <div>
                                            <div class="kanban-score-value">{{ $realScore }}</div>
                                            <div style="font-size:.68rem;color:var(--pk-muted);margin-top:.12rem">Score</div>
                                        </div>
                                        <span class="kanban-temperature {{ $temperature['class'] }}">{{ $temperature['label'] }}</span>
                                    </div>

                                    <div>
                                        <span class="kanban-score-bar">
                                            <span class="kanban-score-fill {{ $temperature['class'] }}" style="width:{{ $displayScore }}%"></span>
                                        </span>
                                        @if ($comment->last_engagement_at)
                                            <div style="font-size:.68rem;color:var(--pk-muted);margin-top:.25rem">Actividad {{ $comment->last_engagement_at->diffForHumans() }}</div>
                                        @endif
                                    </div>

                                    <div class="kanban-card-comment">{{ $comment->comment_text }}</div>

                                    <div class="kanban-card-footer">
                                        <div class="kanban-estimated">
                                            <span style="font-size:.7rem;color:var(--pk-muted);font-weight:500">$</span>
                                            <input
                                                class="kanban-estimated-input"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                value="{{ $comment->estimated_value ? number_format((float) $comment->estimated_value, 2, '.', '') : '' }}"
                                                placeholder="0.00"
                                                wire:change="updateEstimatedValue({{ $comment->id }}, $event.target.value ? parseFloat($event.target.value) : null)"
                                            />
                                        </div>

                                        <div class="kanban-card-actions">
                                            <a class="kanban-card-action" href="{{ $detailUrl }}" title="Ver detalle">Detalle</a>
                                            @if ($patientUrl)
                                                <a class="kanban-card-action" href="{{ $patientUrl }}" title="Ver ficha del paciente">Paciente</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="kanban-empty-column">
                                Arrastra leads aqui
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="kanban-archive-bar" aria-hidden="true">
            <div
                class="kanban-archive-target archive-lost"
                ondragover="handleArchiveDragOver(event)"
                ondragleave="handleArchiveDragLeave(event)"
                ondrop="handleArchiveDrop(event, 'lost')"
            >
                <strong>Descartar / Perdido</strong>
                <span>Solicita motivo de perdida</span>
            </div>
            <div
                class="kanban-archive-target archive-won"
                ondragover="handleArchiveDragOver(event)"
                ondragleave="handleArchiveDragLeave(event)"
                ondrop="handleArchiveDrop(event, 'won')"
            >
                <strong>Cita confirmada / Ganado</strong>
                <span>Archiva directo como convertido</span>
            </div>
        </div>
    </section>

    @if ($lostModalCommentId)
        <div class="kanban-modal-backdrop" wire:key="lost-reason-modal">
            <section class="kanban-modal" role="dialog" aria-modal="true" aria-labelledby="lost-modal-title">
                <h3 id="lost-modal-title">Marcar lead como perdido</h3>
                <p>Registra el motivo por el cual este lead se pierde. Esto ayuda a medir fuga de oportunidades.</p>

                <label style="display:grid;gap:.35rem">
                    <span style="font-size:.78rem;font-weight:500;color:var(--pk-ink)">Motivo</span>
                    <input
                        class="kanban-search"
                        type="text"
                        wire:model="lostReason"
                        placeholder="ej. Sin respuesta, Presupuesto rechazado, Ya es paciente"
                        style="width:100%"
                        autofocus
                    />
                </label>

                <div class="kanban-modal-actions">
                    <button class="kanban-modal-btn kanban-modal-btn-soft" type="button" wire:click="closeLostModal">Cancelar</button>
                    <button class="kanban-modal-btn kanban-modal-btn-primary" type="button" wire:click="confirmLost">
                        Marcar como perdido
                    </button>
                </div>
            </section>
        </div>
    @endif

    <script>
        let draggedCard = null;

        function handleDragStart(event) {
            draggedCard = event.target.closest('.kanban-card');
            if (! draggedCard) return;

            draggedCard.classList.add('is-dragging');
            document.querySelector('.pipeline-kanban')?.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', draggedCard.dataset.commentId);
        }

        function handleDragEnd(event) {
            const card = event.target.closest('.kanban-card');
            if (card) card.classList.remove('is-dragging');

            document.querySelectorAll('.kanban-column.drag-over').forEach(col => {
                col.classList.remove('drag-over');
            });
            document.querySelectorAll('.kanban-archive-target.drag-over').forEach(zone => {
                zone.classList.remove('drag-over');
            });
            document.querySelector('.pipeline-kanban')?.classList.remove('is-dragging');

            draggedCard = null;
        }

        function handleArchiveDragOver(event) {
            event.preventDefault();
            event.currentTarget.classList.add('drag-over');
        }

        function handleArchiveDragLeave(event) {
            event.currentTarget.classList.remove('drag-over');
        }

        function handleArchiveDrop(event, toStage) {
            event.preventDefault();
            event.currentTarget.classList.remove('drag-over');
            document.querySelector('.pipeline-kanban')?.classList.remove('is-dragging');

            const commentId = event.dataTransfer.getData('text/plain');
            if (! commentId) return;

            Livewire.dispatch('move-card', { commentId: parseInt(commentId, 10), toStage: toStage });
        }

        function handleCardDrop(event, toStage) {
            event.preventDefault();

            document.querySelectorAll('.kanban-column.drag-over').forEach(col => {
                col.classList.remove('drag-over');
            });

            const commentId = event.dataTransfer.getData('text/plain');
            if (! commentId) return;

            const fromStage = draggedCard?.dataset?.stage;
            if (fromStage === toStage) return;

            Livewire.dispatch('move-card', { commentId: parseInt(commentId, 10), toStage: toStage });

        }

    </script>
</x-filament-panels::page>
