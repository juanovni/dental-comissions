<?php

namespace App\Http\Controllers;

use App\Enums\SocialCommentActionType;
use App\Models\SocialComment;
use App\Models\SocialLinkEvent;
use App\Services\SocialConversionService;
use App\Services\SocialCrmSettingsService;
use App\Services\SocialLeadAlertService;
use App\Services\SocialLeadScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocialSmartLinkController extends Controller
{
    public function show(string $trackingToken, Request $request): View
    {
        $comment = $this->findComment($trackingToken);
        $settings = app(SocialCrmSettingsService::class);
        $content = $this->contentFor($comment, $settings->smartLinkContentBlocks());

        return view('social.smart-link', [
            'comment' => $comment,
            'content' => $content,
            'hero' => $this->heroFor($comment, $content),
            'preview' => $this->previewFor($comment),
            'trackingToken' => $comment->tracking_token,
            'trackUrl' => route('social-smart-link.track', ['trackingToken' => $comment->tracking_token]),
            'csrfToken' => csrf_token(),
            'durationThreshold' => $settings->smartLinkDurationThresholdSeconds(),
            'pingSeconds' => $settings->smartLinkPingSeconds(),
            'whatsappLink' => app(SocialConversionService::class)->whatsappLink($comment),
        ]);
    }

    public function track(string $trackingToken, Request $request): JsonResponse
    {
        $comment = $this->findComment($trackingToken);
        $data = $request->validate([
            'event_type' => ['required', 'string', 'in:view,revisit,engagement_ping,duration_threshold'],
            'session_id' => ['nullable', 'string', 'max:80'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'metadata' => ['nullable', 'array'],
        ]);

        $event = SocialLinkEvent::create([
            'social_comment_id' => $comment->id,
            'event_type' => $data['event_type'],
            'session_id' => $data['session_id'] ?? null,
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'metadata' => $data['metadata'] ?? [],
        ]);

        $this->applyTrackingEffects($comment->refresh(), $event);

        return response()->json([
            'status' => 'ok',
            'event_id' => $event->id,
            'interest_score' => $comment->refresh()->interest_score,
            'hot_lead' => filled($comment->hot_lead_at),
        ]);
    }

    private function findComment(string $trackingToken): SocialComment
    {
        return SocialComment::query()
            ->with(['suggestedProcedure', 'socialAccount', 'socialIdentity'])
            ->where('tracking_token', strtoupper($trackingToken))
            ->firstOrFail();
    }

    private function applyTrackingEffects(SocialComment $comment, SocialLinkEvent $event): void
    {
        $scoring = app(SocialLeadScoringService::class);

        if (in_array($event->event_type, ['view', 'revisit'], true)) {
            $scoring->scoreSmartLinkVisit($comment);

            return;
        }

        if ($event->event_type !== 'duration_threshold') {
            return;
        }

        $alreadyRecorded = $comment->actions()
            ->where('action', SocialCommentActionType::LeadScoreUpdated->value)
            ->where('notes', app(SocialCrmSettingsService::class)->smartLinkDurationAlert())
            ->exists();

        if ($alreadyRecorded) {
            return;
        }

        $settings = app(SocialCrmSettingsService::class);

        $scoring->addScore(
            $comment,
            $settings->smartLinkDurationScore(),
            $settings->smartLinkDurationAlert(),
            [
                'event' => 'duration_threshold',
                'duration_seconds' => $event->duration_seconds,
                'threshold_seconds' => $settings->smartLinkDurationThresholdSeconds(),
                'social_link_event_id' => $event->id,
            ],
        );

        app(SocialLeadAlertService::class)->createAlert($comment->refresh(), 'high_duration', 'warning', [
            'duration_seconds' => $event->duration_seconds,
            'threshold_seconds' => $settings->smartLinkDurationThresholdSeconds(),
            'social_link_event_id' => $event->id,
        ]);
    }

    private function contentFor(SocialComment $comment, array $blocks): array
    {
        $category = strtolower((string) ($comment->suggestedProcedure?->category ?: $comment->suggestedProcedure?->code ?: 'unknown'));
        $normalizedCategory = str($category)->ascii()->lower()->replace([' ', '-'], '_')->toString();

        return $blocks[$normalizedCategory]
            ?? $blocks[$category]
            ?? $blocks['unknown']
            ?? [
                'eyebrow' => 'Valoracion dental personalizada',
                'title' => 'Tu sonrisa merece un plan claro, humano y sin presion.',
                'subtitle' => 'Mira como trabajamos y continua por WhatsApp para recibir orientacion de la clinica.',
                'visual_label' => 'Diagnostico integral',
                'video_url' => '',
            ];
    }

    private function heroFor(SocialComment $comment, array $content): array
    {
        [$titleStatic, $titleTyped] = $this->splitHeroTitle(
            (string) ($content['title'] ?? 'Tu sonrisa merece un plan claro, humano y sin presion.'),
        );

        $subtitle = (string) ($content['subtitle'] ?? 'Mira como trabajamos y continua por WhatsApp para recibir orientacion de la clinica.');
        $visitorName = $this->visitorName($comment);

        if ($visitorName) {
            $subtitle = "Hola, {$visitorName}. {$subtitle}";
        }

        return [
            'title_static' => $titleStatic,
            'title_typed' => $titleTyped,
            'subtitle' => $subtitle,
            'visual_label' => (string) ($content['visual_label'] ?? 'Diagnostico integral'),
            'visual_image_url' => (string) ($content['visual_image_url'] ?? ''),
            'before_image_url' => (string) ($content['before_image_url'] ?? ''),
            'before_video_url' => (string) ($content['before_video_url'] ?? ''),
            'after_image_url' => (string) ($content['after_image_url'] ?? ''),
            'after_video_url' => (string) ($content['after_video_url'] ?? ''),
        ];
    }

    private function splitHeroTitle(string $title): array
    {
        $title = str($title)->squish()->toString();

        if ($title === '') {
            return ['Tu nueva sonrisa,', 'planificada a medida.'];
        }

        $commaPosition = strrpos($title, ',');

        if ($commaPosition !== false) {
            return [
                trim(substr($title, 0, $commaPosition + 1)),
                trim(substr($title, $commaPosition + 1)),
            ];
        }

        $words = preg_split('/\s+/', $title) ?: [];

        if (count($words) <= 4) {
            return [$title, ''];
        }

        $splitAt = max(2, (int) floor(count($words) * 0.58));

        return [
            implode(' ', array_slice($words, 0, $splitAt)),
            implode(' ', array_slice($words, $splitAt)),
        ];
    }

    private function visitorName(SocialComment $comment): ?string
    {
        $name = trim((string) ($comment->socialIdentity?->display_name ?: $comment->author_name));

        if ($name === '' || str_starts_with($name, '@') || preg_match('/[0-9_]/', $name)) {
            return null;
        }

        $firstName = str($name)->squish()->explode(' ')->first();

        return is_string($firstName) && strlen($firstName) >= 2 ? $firstName : null;
    }

    private function previewFor(SocialComment $comment): array
    {
        $procedureText = str($comment->suggestedProcedure?->name.' '.$comment->suggestedProcedure?->category.' '.$comment->suggestedProcedure?->code)
            ->ascii()
            ->lower()
            ->toString();

        if (str_contains($procedureText, 'implante')) {
            return [
                'procedure' => 'Implantes dentales',
                'duration' => 'Segun evaluacion',
                'complexity' => 'Personalizada',
                'title' => 'Analisis para una solucion permanente',
                'text' => "Hemos reservado contexto para tu codigo {$comment->tracking_token}. En la valoracion revisaremos estabilidad, salud osea y opciones claras antes de cualquier decision.",
                'steps' => [
                    ['label' => 'Revision inicial', 'text' => 'Escuchamos que necesitas recuperar seguridad al morder o sonreir.'],
                    ['label' => 'Evaluacion clinica', 'text' => 'El equipo valida si necesitas imagenes, densidad osea o alternativas.'],
                    ['label' => 'Plan transparente', 'text' => 'Recibes tiempos, opciones y presupuesto sin presion.'],
                ],
            ];
        }

        if (str_contains($procedureText, 'sonrisa') || str_contains($procedureText, 'estetica') || str_contains($procedureText, 'diseno')) {
            return [
                'procedure' => 'Diseno de sonrisa',
                'duration' => 'Plan por fases',
                'complexity' => 'Media',
                'title' => 'Valoracion estetica con vision facial',
                'text' => "Tu codigo {$comment->tracking_token} indica interes en estetica dental. En la primera sesion revisamos simetria, color y proporciones para construir un plan realista.",
                'steps' => [
                    ['label' => 'Fotos y contexto', 'text' => 'Entendemos que deseas cambiar y que resultado te gustaria lograr.'],
                    ['label' => 'Analisis estetico', 'text' => 'Revisamos rostro, sonrisa, color y linea dental.'],
                    ['label' => 'Ruta visual', 'text' => 'Te explicamos que se puede lograr, en que orden y con que alternativas.'],
                ],
            ];
        }

        if (str_contains($procedureText, 'ortodoncia') || str_contains($procedureText, 'invisalign') || str_contains($procedureText, 'alineador')) {
            return [
                'procedure' => 'Ortodoncia invisible',
                'duration' => '12 meses',
                'complexity' => 'Media',
                'title' => 'Camino claro para alinear tu sonrisa',
                'text' => "Con tu codigo {$comment->tracking_token}, el equipo sabra que buscas ortodoncia o alineadores. La cita se enfoca en revisar viabilidad, tiempos y comodidad.",
                'steps' => [
                    ['label' => 'Revision de mordida', 'text' => 'Evaluamos alineacion, espacio y objetivos.'],
                    ['label' => 'Simulacion o plan', 'text' => 'Te mostramos el camino probable antes de iniciar.'],
                    ['label' => 'Opciones claras', 'text' => 'Comparas alternativas, tiempos y costos sin sorpresas.'],
                ],
            ];
        }

        return [
            'procedure' => $comment->suggestedProcedure?->name ?: 'Diagnostico integral',
            'duration' => 'Primera cita',
            'complexity' => 'Sin presion',
            'title' => 'Diagnostico integral sin compromiso',
            'text' => "Este espacio es para que veas como eliminamos el estres de ir al dentista. Tu codigo {$comment->tracking_token} garantiza una valoracion guiada y sin presion.",
            'steps' => [
                ['label' => 'Escucha primero', 'text' => 'Nos cuentas que te preocupa y que resultado esperas.'],
                ['label' => 'Revision clara', 'text' => 'Revisamos tu caso con criterio clinico y lenguaje simple.'],
                ['label' => 'Plan humano', 'text' => 'Te explicamos opciones, tiempos y presupuesto antes de decidir.'],
            ],
        ];
    }
}
