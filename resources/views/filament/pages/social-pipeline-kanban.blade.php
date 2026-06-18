<x-filament-panels::page>
    @php
        $columns = $this->columns();
        $stageCounts = $this->stageCounts();
        $stageTotals = $this->stageTotals();
        $currency = 'USD';
    @endphp

    <style>
        .pipeline-kanban {
            --pk-accent: oklch(0.59 0.2 259.81);
            --pk-ink: #0f172a;
            --pk-muted: #64748b;
            --pk-card-bg: #ffffff;
            --pk-column-bg: #f1f5f9;
            --pk-border: #e5e7eb;
            color: var(--pk-ink);
            margin-top: -.25rem;
        }

        .kanban-toolbar {
            align-items: center;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-bottom: 1rem;
        }

        @media (min-width: 900px) {
            .kanban-toolbar {
                margin-top: -4.05rem;
            }
        }

        .kanban-search {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: .85rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            color: var(--pk-ink);
            outline: none;
            padding: .82rem 1rem;
            width: min(100%, 24rem);
        }

        .kanban-board {
            display: grid;
            gap: .75rem;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            min-height: 70vh;
            overflow-x: auto;
            padding-bottom: 1rem;
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
            border-radius: .85rem;
            display: flex;
            flex-direction: column;
            gap: 0;
            max-height: calc(100dvh - 14rem);
            overflow: hidden;
        }

        .kanban-column-header {
            align-items: center;
            background: #ffffff;
            border-bottom: 1px solid var(--pk-border);
            border-radius: .85rem .85rem 0 0;
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            padding: .7rem .8rem;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .kanban-column-label {
            color: var(--pk-ink);
            font-size: .8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .kanban-column-count {
            align-items: center;
            background: #e2e8f0;
            border-radius: 999px;
            color: #334155;
            display: inline-flex;
            font-size: .7rem;
            font-weight: 800;
            height: 1.3rem;
            justify-content: center;
            min-width: 1.3rem;
            padding: 0 .35rem;
        }

        .kanban-column-total {
            color: #0f766e;
            font-size: .72rem;
            font-weight: 800;
            margin-left: auto;
            white-space: nowrap;
        }

        .kanban-cards {
            display: flex;
            flex-direction: column;
            gap: .55rem;
            overflow-y: auto;
            padding: .65rem .6rem;
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
            border-left: 3px solid var(--pk-accent);
            border-radius: .7rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            cursor: grab;
            display: grid;
            gap: .55rem;
            padding: .75rem;
            transition: box-shadow .16s ease, transform .16s ease;
            user-select: none;
        }

        .kanban-card:hover {
            box-shadow: 0 3px 8px rgba(15, 23, 42, .07);
            transform: translateY(-1px);
        }

        .kanban-card.is-dragging {
            opacity: .5;
            transform: rotate(2deg);
        }

        .kanban-card.drag-over {
            border-color: var(--pk-accent);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, .24);
        }

        .kanban-card.stage-won {
            border-left-color: #16a34a;
        }

        .kanban-card.stage-lost {
            border-left-color: #dc2626;
        }

        .kanban-card-top {
            align-items: start;
            display: flex;
            gap: .5rem;
            justify-content: space-between;
        }

        .kanban-card-name {
            color: var(--pk-ink);
            font-size: .84rem;
            font-weight: 700;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .kanban-card-meta {
            color: var(--pk-muted);
            font-size: .7rem;
            margin-top: .2rem;
        }

        .kanban-badges {
            display: flex;
            flex-wrap: wrap;
            gap: .3rem;
        }

        .kanban-badge {
            border-radius: 999px;
            font-size: .62rem;
            font-weight: 800;
            padding: .22rem .4rem;
            text-transform: uppercase;
        }

        .kanban-badge-hot { background: #fff7ed; color: #c2410c; }
        .kanban-badge-cold { background: #f1f5f9; color: #475569; }
        .kanban-badge-followup { background: #eff6ff; color: #1d4ed8; }
        .kanban-badge-score { background: #ecfdf5; color: #047857; }

        .kanban-score-bar {
            background: #e5e7eb;
            border-radius: 999px;
            display: block;
            height: .2rem;
            overflow: hidden;
            width: 100%;
        }

        .kanban-score-fill {
            background: var(--pk-accent);
            border-radius: inherit;
            height: 100%;
            transition: width .25s ease;
        }

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
            color: #0f766e;
            font-size: .78rem;
            font-weight: 750;
            outline: none;
            padding: .15rem .3rem;
            transition: .14s ease;
            width: 5.5rem;
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
            border: 0;
            border-radius: .35rem;
            color: var(--pk-muted);
            cursor: pointer;
            display: inline-flex;
            font-size: .72rem;
            font-weight: 700;
            padding: .25rem .4rem;
            text-decoration: none;
            transition: .12s ease;
        }

        .kanban-card-action:hover {
            background: #f1f5f9;
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

        /* Confetti for Won column */
        .confetti-container {
            pointer-events: none;
            position: fixed;
            inset: 0;
            z-index: 999;
        }

        .confetti-piece {
            animation: confettiFall var(--duration, 2s) ease-out forwards;
            height: 10px;
            position: absolute;
            width: 10px;
        }

        @keyframes confettiFall {
            0% { opacity: 1; transform: translateY(0) rotate(0deg) scale(1); }
            100% { opacity: 0; transform: translateY(100vh) rotate(720deg) scale(.3); }
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
            font-weight: 700;
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
            font-weight: 700;
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
            color: #5eead4;
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
            @foreach ($columns as $stage)
                @php
                    $stageValue = $stage->value;
                    $count = $stageCounts[$stageValue] ?? 0;
                    $total = $stageTotals[$stageValue] ?? 0;
                    $cards = $this->cards($stage);
                @endphp

                <div class="kanban-column" data-stage="{{ $stageValue }}">
                    <div class="kanban-column-header">
                        <span class="kanban-column-label">{{ $stage->label() }}</span>
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
                                    $score = min(100, max(0, (int) $comment->interest_score));
                                    $leadName = $comment->author_username ? '@' . $comment->author_username : ($comment->author_name ?: 'Lead social');
                                    $isHot = filled($comment->last_smart_link_visited_at) && $comment->last_smart_link_visited_at >= now()->subMinutes(10);
                                    $isCold = blank($comment->last_smart_link_visited_at) || $comment->last_smart_link_visited_at < now()->subHours(48);
                                    $isFollowUpToday = filled($comment->follow_up_at) && $comment->follow_up_at->isToday();
                                @endphp

                                <div
                                    class="kanban-card stage-{{ $stageValue }}"
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
                                            @if ($isHot)
                                                <span class="kanban-badge kanban-badge-hot">Caliente</span>
                                            @elseif ($isCold && $stageValue !== 'lost' && $stageValue !== 'won')
                                                <span class="kanban-badge kanban-badge-cold">Frio</span>
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

                                    <div>
                                        <div style="display:flex;justify-content:space-between;font-size:.68rem;font-weight:700;color:var(--pk-muted);margin-bottom:.2rem">
                                            <span>Score {{ $score }}</span>
                                        </div>
                                        <span class="kanban-score-bar">
                                            <span class="kanban-score-fill" style="width:{{ $score }}%"></span>
                                        </span>
                                    </div>

                                    <div class="kanban-card-comment">{{ $comment->comment_text }}</div>

                                    <div class="kanban-card-footer">
                                        <div class="kanban-estimated">
                                            <span style="font-size:.7rem;color:var(--pk-muted);font-weight:700">$</span>
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
    </section>

    @if ($lostModalCommentId)
        <div class="kanban-modal-backdrop" wire:key="lost-reason-modal">
            <section class="kanban-modal" role="dialog" aria-modal="true" aria-labelledby="lost-modal-title">
                <h3 id="lost-modal-title">Marcar lead como perdido</h3>
                <p>Registra el motivo por el cual este lead se pierde. Esto ayuda a medir fuga de oportunidades.</p>

                <label style="display:grid;gap:.35rem">
                    <span style="font-size:.78rem;font-weight:700;color:var(--pk-ink)">Motivo</span>
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
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', draggedCard.dataset.commentId);
        }

        function handleDragEnd(event) {
            const card = event.target.closest('.kanban-card');
            if (card) card.classList.remove('is-dragging');

            document.querySelectorAll('.kanban-column.drag-over').forEach(col => {
                col.classList.remove('drag-over');
            });

            draggedCard = null;
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

            if (toStage === 'won') {
                triggerConfetti(event.clientX, event.clientY);
            }
        }

        function triggerConfetti(x, y) {
            const container = document.createElement('div');
            container.className = 'confetti-container';
            document.body.appendChild(container);

            const colors = ['#16a34a', '#1d4ed8', '#f59e0b', '#dc2626', '#8b5cf6', '#ec4899'];
            const count = 40;

            for (let i = 0; i < count; i++) {
                const piece = document.createElement('div');
                piece.className = 'confetti-piece';
                piece.style.left = (x + (Math.random() - .5) * 240) + 'px';
                piece.style.top = (y + (Math.random() - .5) * 120) + 'px';
                piece.style.background = colors[Math.floor(Math.random() * colors.length)];
                piece.style.borderRadius = Math.random() > .5 ? '50%' : '0';
                piece.style.width = (6 + Math.random() * 8) + 'px';
                piece.style.height = (6 + Math.random() * 8) + 'px';
                piece.style.setProperty('--duration', (1.5 + Math.random() * 1.5) + 's');
                piece.style.animationDelay = (Math.random() * .3) + 's';
                container.appendChild(piece);
            }

            setTimeout(() => container.remove(), 3000);
        }
    </script>
</x-filament-panels::page>
