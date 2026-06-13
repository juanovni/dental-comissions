<?php

namespace App\Http\Controllers;

use App\Enums\SocialCommentActionType;
use App\Models\SocialComment;
use App\Models\SocialLinkEvent;
use App\Services\SocialConversionService;
use App\Services\SocialCrmSettingsService;
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
            ->with(['suggestedProcedure', 'socialAccount'])
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
}
