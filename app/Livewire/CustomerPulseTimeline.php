<?php

namespace App\Livewire;

use App\Models\SocialComment;
use App\Models\SocialLinkEvent;
use App\Services\GeminiJsonService;
use App\Services\SocialLinkEventMapper;
use Livewire\Attributes\On;
use Livewire\Component;

class CustomerPulseTimeline extends Component
{
    public int $commentId;

    public bool $showAllPings = false;

    public ?string $aiInsight = null;

    public bool $analyzing = false;

    public string $sortOrder = 'newest';

    public function mount(int $commentId): void
    {
        $this->commentId = $commentId;
    }

    public function comment(): ?SocialComment
    {
        return SocialComment::with(['socialIdentity.patient', 'suggestedProcedure', 'convertedPatient'])
            ->find($this->commentId);
    }

    public function events(): \Illuminate\Support\Collection
    {
        $query = SocialLinkEvent::where('social_comment_id', $this->commentId)
            ->orderBy('created_at', $this->sortOrder === 'newest' ? 'desc' : 'asc');

        $events = $query->get();

        if (! $this->showAllPings) {
            $events = $this->groupPings($events);
        }

        return $events;
    }

    public function stats(): array
    {
        $allEvents = SocialLinkEvent::where('social_comment_id', $this->commentId)->get();

        return [
            'total' => $allEvents->count(),
            'sessions' => $allEvents->pluck('session_id')->filter()->unique()->count(),
            'video_progress' => $this->maxVideoProgress($allEvents),
            'duration' => $allEvents->sum('duration_seconds'),
            'revisits' => $allEvents->where('event_type', 'revisit')->count(),
            'whatsapp_clicks' => $allEvents->where('event_type', 'whatsapp_click')->count(),
        ];
    }

    public function togglePings(): void
    {
        $this->showAllPings = ! $this->showAllPings;
    }

    public function toggleSort(): void
    {
        $this->sortOrder = $this->sortOrder === 'newest' ? 'oldest' : 'newest';
    }

    #[On('analyze-customer-pulse')]
    public function analyzeBehavior(): void
    {
        $this->analyzing = true;
        $this->aiInsight = null;

        try {
            $comment = $this->comment();
            $events = SocialLinkEvent::where('social_comment_id', $this->commentId)
                ->orderBy('created_at')
                ->get();

            if ($events->isEmpty()) {
                $this->aiInsight = 'No hay eventos de comportamiento para analizar.';
                $this->analyzing = false;

                return;
            }

            $eventList = $events->map(fn (SocialLinkEvent $e) => [
                'tipo' => SocialLinkEventMapper::label($e->event_type),
                'fecha' => $e->created_at?->format('Y-m-d H:i:s'),
                'duracion_seg' => $e->duration_seconds,
                'metadata' => $e->metadata,
            ])->toArray();

            $systemPrompt = <<<'PROMPT'
Eres un analista de comportamiento de pacientes dentales.
Analiza el siguiente historial de eventos de un lead que interactuo con un Smart Link dental.
Debes responder en formato JSON con esta estructura:
{
  "resumen": "Resumen en lenguaje comercial, 2-3 oraciones",
  "intencion": "bajo|medio|alto",
  "objecion_probable": "Posible objecion del paciente",
  "proxima_accion": "Accion recomendada para el equipo comercial"
}
Responde SOLO con el JSON, sin texto adicional.
PROMPT;

            $eventSummary = collect($eventList)->map(fn (array $e) =>
                "- {$e['tipo']} el {$e['fecha']}" .
                ($e['duracion_seg'] ? " ({$e['duracion_seg']}s)" : '') .
                ($e['metadata'] ? ' ' . json_encode($e['metadata']) : '')
            )->implode("\n");

            $videoProgress = $this->maxVideoProgress($events);
            $totalDuration = $events->sum('duration_seconds');
            $revisitCount = $events->where('event_type', 'revisit')->count();
            $whatsappClicks = $events->where('event_type', 'whatsapp_click')->count();

            $procedure = $comment?->suggestedProcedure?->name ?? 'Sin procedimiento';
            $userName = $comment?->author_username ?: $comment?->author_name ?: 'Sin nombre';
            $interestScore = $comment?->interest_score ?? 0;

            $userPrompt = <<<PROMPT
Lead: {$userName}
Procedimiento de interes: {$procedure}
Score de interes: {$interestScore}/100
Repeticiones de visita: {$revisitCount}
Progreso maximo de video: {$videoProgress}%
Duracion total de permanencia: {$totalDuration}s
Clics en WhatsApp: {$whatsappClicks}

Historial de eventos:
{$eventSummary}
PROMPT;

            $gemini = app(GeminiJsonService::class);
            $response = $gemini->generate($systemPrompt, $userPrompt);
            $data = json_decode($response, true);

            $this->aiInsight = is_array($data) ? json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $response;
        } catch (\Throwable $e) {
            $this->aiInsight = 'Error al analizar: ' . $e->getMessage();
        }

        $this->analyzing = false;
    }

    public function render()
    {
        return view('livewire.customer-pulse-timeline');
    }

    private function groupPings(\Illuminate\Support\Collection $events): \Illuminate\Support\Collection
    {
        $grouped = collect();
        $lastPing = null;
        $pingCount = 0;

        foreach ($events as $event) {
            if ($event->event_type === 'engagement_ping') {
                $pingCount++;
                $lastPing = $event;

                continue;
            }

            if ($pingCount > 0 && $lastPing) {
                $grouped->push((object) [
                    'id' => "ping-group-{$lastPing->id}",
                    'event_type' => 'engagement_ping_group',
                    'created_at' => $lastPing->created_at,
                    'duration_seconds' => null,
                    'metadata' => ['count' => $pingCount, 'first_at' => $lastPing->created_at?->toISOString()],
                    'is_group' => true,
                ]);
                $pingCount = 0;
                $lastPing = null;
            }

            $grouped->push($event);
        }

        if ($pingCount > 0 && $lastPing) {
            $grouped->push((object) [
                'id' => "ping-group-{$lastPing->id}",
                'event_type' => 'engagement_ping_group',
                'created_at' => $lastPing->created_at,
                'duration_seconds' => null,
                'metadata' => ['count' => $pingCount, 'first_at' => $lastPing->created_at?->toISOString()],
                'is_group' => true,
            ]);
        }

        return $grouped;
    }

    private function maxVideoProgress(\Illuminate\Support\Collection $events): int
    {
        $videoEvents = ['video_start' => 0, 'video_25' => 25, 'video_50' => 50, 'video_75' => 75, 'video_complete' => 100];
        $max = 0;

        foreach ($events as $event) {
            $progress = $videoEvents[$event->event_type] ?? null;
            if ($progress !== null) {
                $max = max($max, $progress);
            }
        }

        return $max;
    }
}
