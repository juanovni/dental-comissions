<x-filament-panels::page>
    @php
        $stats = $this->stats();
        $messages = $this->messages();
        $filters = [
            'needs_review' => ['label' => 'Requiere revision', 'count' => $stats['needs_review']],
            'failed' => ['label' => 'Con error', 'count' => $stats['failed']],
            'received' => ['label' => 'Recibidos', 'count' => $stats['received']],
            'parsed' => ['label' => 'Parseados', 'count' => $stats['parsed']],
            'confirmed' => ['label' => 'Confirmados', 'count' => $stats['confirmed']],
            'processed' => ['label' => 'Procesados', 'count' => $stats['processed']],
            'all' => ['label' => 'Todos', 'count' => null],
        ];
    @endphp

    <style>
        .whatsapp-shell {
            background:
                radial-gradient(circle at 8% 0%, rgba(37, 211, 102, .10), transparent 30rem),
                linear-gradient(180deg, rgba(248, 250, 252, .94), rgba(255, 255, 255, .98));
            border: 1px solid rgba(15, 23, 42, .06);
            border-radius: 2rem;
            padding: clamp(1rem, 2vw, 1.5rem);
        }

        .whatsapp-hero {
            margin-bottom: 1.25rem;
        }

        .whatsapp-search {
            background: rgba(255, 255, 255, .78);
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 999px;
            box-shadow: 0 22px 70px -54px rgba(15, 23, 42, .8);
            color: rgb(15, 23, 42);
            outline: none;
            padding: .95rem 1.15rem;
            width: 100%;
        }

        .whatsapp-stats {
            display: grid;
            gap: .75rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-block: 1rem 1.25rem;
        }

        @media (min-width: 768px) {
            .whatsapp-stats {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .whatsapp-stat {
            background: rgba(255, 255, 255, .86);
            border: 1px solid rgba(15, 23, 42, .07);
            border-radius: 1.35rem;
            padding: 1rem;
            text-align: left;
        }

        .whatsapp-stat strong {
            color: rgb(15, 23, 42);
            display: block;
            font-size: 1.8rem;
            letter-spacing: -.06em;
            line-height: 1;
        }

        .whatsapp-stat span {
            color: rgb(100, 116, 139);
            display: block;
            font-size: .72rem;
            font-weight: 750;
            letter-spacing: .08em;
            margin-top: .45rem;
            text-transform: uppercase;
        }

        .whatsapp-stat.is-danger strong { color: rgb(239, 68, 68); }
        .whatsapp-stat.is-warning strong { color: rgb(245, 158, 11); }
        .whatsapp-stat.is-success strong { color: rgb(20, 184, 166); }
        .whatsapp-stat.is-info strong { color: rgb(14, 165, 233); }

        .whatsapp-filters {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            margin-bottom: 1rem;
        }

        .whatsapp-filter {
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 999px;
            color: rgb(51, 65, 85);
            font-size: .82rem;
            font-weight: 700;
            padding: .62rem .85rem;
            transition: all .18s ease;
        }

        .whatsapp-filter:hover,
        .whatsapp-filter.is-active {
            background: rgb(15, 23, 42);
            border-color: rgb(15, 23, 42);
            color: white;
            transform: translateY(-1px);
        }

        .message-grid {
            display: grid;
            gap: .75rem;
        }

        .message-card {
            background: rgba(255, 255, 255, .94);
            border: 1px solid rgba(15, 23, 42, .07);
            border-radius: 1.35rem;
            box-shadow: 0 24px 90px -70px rgba(15, 23, 42, .95);
            display: grid;
            gap: .75rem;
            grid-template-columns: minmax(0, 1fr);
            overflow: hidden;
            padding: 1rem 1.25rem;
            position: relative;
        }

        @media (min-width: 768px) {
            .message-card {
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: start;
            }
        }

        .message-card::before {
            content: '';
            inset: 0 auto 0 0;
            position: absolute;
            width: 4px;
            background: rgb(37, 211, 102);
        }

        .message-card.status-failed::before { background: rgb(239, 68, 68); }
        .message-card.status-needs_review::before { background: rgb(245, 158, 11); }
        .message-card.status-received::before { background: rgb(251, 191, 36); }
        .message-card.status-parsed::before { background: rgb(14, 165, 233); }
        .message-card.status-confirmed::before,
        .message-card.status-processed::before { background: rgb(20, 184, 166); }

        .message-header {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            align-items: center;
        }

        .message-chip {
            background: rgb(248, 250, 252);
            border: 1px solid rgba(15, 23, 42, .07);
            border-radius: 999px;
            color: rgb(51, 65, 85);
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .06em;
            padding: .36rem .55rem;
            text-transform: uppercase;
        }

        .message-chip.danger { background: rgb(254, 242, 242); border-color: rgb(254, 202, 202); color: rgb(185, 28, 28); }
        .message-chip.warning { background: rgb(255, 251, 235); border-color: rgb(254, 243, 199); color: rgb(180, 83, 9); }
        .message-chip.success { background: rgb(240, 253, 250); border-color: rgb(153, 246, 228); color: rgb(15, 118, 110); }
        .message-chip.info { background: rgb(240, 249, 255); border-color: rgb(186, 230, 253); color: rgb(3, 105, 161); }

        .message-body {
            color: rgb(15, 23, 42);
            font-size: .92rem;
            line-height: 1.5;
            margin-top: .35rem;
        }

        .message-meta {
            color: rgb(100, 116, 139);
            font-size: .78rem;
            margin-top: .3rem;
        }

        .message-aside {
            text-align: right;
        }

        .message-time {
            color: rgb(100, 116, 139);
            font-size: .78rem;
            font-weight: 600;
        }

        .message-doctor {
            color: rgb(15, 23, 42);
            font-size: .85rem;
            font-weight: 700;
            margin-top: .25rem;
        }

        .message-error {
            background: rgb(254, 242, 242);
            border: 1px solid rgb(254, 202, 202);
            border-radius: .75rem;
            color: rgb(185, 28, 28);
            font-size: .82rem;
            margin-top: .5rem;
            padding: .5rem .75rem;
        }

        .empty-state {
            background: rgba(255, 255, 255, .86);
            border: 1px dashed rgba(15, 23, 42, .15);
            border-radius: 1.6rem;
            color: rgb(71, 85, 105);
            padding: 2rem;
            text-align: center;
        }
    </style>

    <section class="whatsapp-shell">
        <div class="whatsapp-hero">
            <input
                class="whatsapp-search"
                type="search"
                wire:model.live.debounce.350ms="search"
                placeholder="Buscar por mensaje, telefono o doctor"
            />
        </div>

        <div class="whatsapp-stats">
            <button class="whatsapp-stat is-danger" type="button" wire:click="setFilter('needs_review')">
                <strong>{{ $stats['needs_review'] }}</strong>
                <span>Requiere revision</span>
            </button>
            <button class="whatsapp-stat is-warning" type="button" wire:click="setFilter('failed')">
                <strong>{{ $stats['failed'] }}</strong>
                <span>Con error</span>
            </button>
            <button class="whatsapp-stat" type="button" wire:click="setFilter('received')">
                <strong>{{ $stats['received'] }}</strong>
                <span>Recibidos</span>
            </button>
            <button class="whatsapp-stat is-success" type="button" wire:click="setFilter('processed')">
                <strong>{{ $stats['processed'] }}</strong>
                <span>Procesados</span>
            </button>
        </div>

        <div class="whatsapp-filters">
            @foreach ($filters as $key => $item)
                <button
                    type="button"
                    wire:click="setFilter('{{ $key }}')"
                    @class(['whatsapp-filter', 'is-active' => $filter === $key])
                >
                    {{ $item['label'] }}
                    @if (! is_null($item['count']))
                        · {{ $item['count'] }}
                    @endif
                </button>
            @endforeach
        </div>

        <div class="message-grid">
            @forelse ($messages as $msg)
                @php
                    $statusClass = $msg->status->value;
                @endphp

                <article class="message-card status-{{ $statusClass }}">
                    <div>
                        <div class="message-header">
                            <span class="message-chip info">{{ $msg->from_phone ?: 'Sin telefono' }}</span>
                            <span @class([
                                'message-chip',
                                'danger' => in_array($msg->status, [\App\Enums\WhatsappMessageStatus::Failed, \App\Enums\WhatsappMessageStatus::NeedsReview], true),
                                'warning' => $msg->status === \App\Enums\WhatsappMessageStatus::Received,
                                'success' => in_array($msg->status, [\App\Enums\WhatsappMessageStatus::Confirmed, \App\Enums\WhatsappMessageStatus::Processed], true),
                                'info' => $msg->status === \App\Enums\WhatsappMessageStatus::Parsed,
                            ])>
                                {{ $msg->status->label() }}
                            </span>
                        </div>

                        <p class="message-body">{{ $msg->message_body ?: 'Sin contenido' }}</p>

                        @if ($msg->ai_response)
                            <p class="message-meta">IA: {{ is_array($msg->ai_response) ? json_encode($msg->ai_response, JSON_UNESCAPED_UNICODE) : $msg->ai_response }}</p>
                        @endif

                        @if ($msg->error_message)
                            <div class="message-error">{{ $msg->error_message }}</div>
                        @endif
                    </div>

                    <aside class="message-aside">
                        <div class="message-time">{{ $msg->created_at->diffForHumans() }}</div>
                        <div class="message-doctor">{{ $msg->professional?->name ?? 'Sin doctor' }}</div>
                        <a
                            class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-primary-600 hover:text-primary-500"
                            href="{{ route('filament.admin.resources.whatsapp-messages.index') }}"
                        >
                            Detalle
                        </a>
                    </aside>
                </article>
            @empty
                <div class="empty-state">
                    No hay mensajes WhatsApp para este filtro. Los mensajes aparecen cuando los doctores envian actividad por WhatsApp.
                </div>
            @endforelse
        </div>

        <div class="mt-5">
            {{ $messages->links() }}
        </div>
    </section>
</x-filament-panels::page>
