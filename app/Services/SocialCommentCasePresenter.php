<?php

namespace App\Services;

use App\Enums\SocialCommentActionType;
use App\Enums\WhatsappMessageDirection;
use App\Filament\Resources\Patients\PatientResource;
use App\Models\ActivityRecord;
use App\Models\SocialComment;
use App\Models\WhatsappMessage;
use Illuminate\Support\Collection;

class SocialCommentCasePresenter
{
    public function build(SocialComment $comment): array
    {
        $comment->loadMissing([
            'actions',
            'convertedPatient',
            'leadAlerts',
            'replies',
            'socialAccount',
            'socialIdentity.patient',
            'socialPost',
            'suggestedProcedure',
        ]);

        $identity = $comment->socialIdentity;
        $patient = $identity?->patient ?: $comment->convertedPatient;
        $lastActivity = $patient
            ? ActivityRecord::query()
                ->with(['doctor', 'procedure'])
                ->where('patient_id', $patient->id)
                ->latest('activity_date')
                ->latest('id')
                ->first()
            : null;
        $socialHistoryCount = $identity
            ? SocialComment::query()->where('social_identity_id', $identity->id)->count()
            : 0;
        $conversation = collect($this->conversationEvents($comment));
        $linkEvents = $comment->linkEvents()->latest('created_at')->limit(12)->get();
        $activity = $this->timelineEvents($comment);

        return [
            'activity' => $activity,
            'conversation' => $conversation->all(),
            'conversation_metrics' => $this->conversationMetrics($conversation),
            'identity' => $identity,
            'last_activity' => $lastActivity,
            'patient' => $patient,
            'patient_url' => $patient ? PatientResource::getUrl('edit', ['record' => $patient]) : null,
            'post' => $comment->socialPost,
            'pulse' => $this->pulseMetrics($linkEvents),
            'social_history' => [
                'interactions' => $socialHistoryCount,
                'previous' => max(0, $socialHistoryCount - 1),
                'origin' => $comment->socialPost?->campaign_name ?: 'Sin campana asignada',
            ],
            'clinical' => [
                'last_appointment' => $lastActivity?->activity_date?->diffForHumans() ?? 'Sin citas registradas',
                'procedure' => $lastActivity?->procedure?->name ?: ($comment->suggestedProcedure?->name ?: 'Sin procedimiento registrado'),
                'doctor' => $lastActivity?->doctor?->name ?: 'Sin asignar',
                'alerts' => ($comment->leadAlerts?->whereNull('resolved_at')->count() ?: 0).' abiertas',
            ],
        ];
    }

    public function timelineEvents(SocialComment $comment): array
    {
        return $comment->linkEvents()
            ->latest('created_at')
            ->limit(12)
            ->get()
            ->map(fn ($event): array => [
                'label' => $event->event_type === 'section_click' && filled($event->metadata['label'] ?? null)
                    ? 'Exploro: '.$event->metadata['label']
                    : SocialLinkEventMapper::label($event->event_type),
                'type' => $event->event_type,
                'icon' => SocialLinkEventMapper::icon($event->event_type),
                'color' => SocialLinkEventMapper::color($event->event_type),
                'group' => SocialLinkEventMapper::group($event->event_type),
                'progress' => SocialLinkEventMapper::progress($event->event_type),
                'date' => $event->created_at?->diffForHumans(),
                'duration' => $event->duration_seconds,
            ])
            ->all();
    }

    public function conversationEvents(SocialComment $comment): array
    {
        $events = [];

        if ($comment->platform?->value !== 'whatsapp') {
            $events[] = [
                'platform' => $comment->platform?->value ?? 'comment',
                'channel' => $comment->platform?->value ?? 'social',
                'color' => match ($comment->platform?->value) {
                    'instagram' => 'indigo',
                    default => 'blue',
                },
                'channel_label' => $comment->platform?->label() ?? 'Social',
                'channel_class' => match ($comment->platform?->value) {
                    'instagram' => 'hot',
                    default => 'info',
                },
                'author' => $comment->author_name ?: $comment->author_username ?: 'Anonimo',
                'kind_label' => 'Comentario en publicacion',
                'message' => $comment->comment_text,
                'date' => $this->formatConversationDate($comment->created_at),
                'time' => $this->formatConversationTime($comment->created_at),
                'short_date' => $this->formatConversationShortDate($comment->created_at),
                'is_automated' => false,
                'rule_label' => null,
                'created_at' => $comment->created_at,
            ];
        }

        foreach ($comment->replies as $reply) {
            $events[] = [
                'platform' => $reply->platform?->value ?? 'comment',
                'channel' => $reply->platform?->value ?? 'social',
                'color' => match ($reply->platform?->value) {
                    'instagram' => 'indigo',
                    'whatsapp' => 'green',
                    default => 'blue',
                },
                'channel_label' => $reply->platform?->label() ?? 'Social',
                'channel_class' => match ($reply->platform?->value) {
                    'instagram' => 'hot',
                    'whatsapp' => 'success',
                    default => 'info',
                },
                'author' => $reply->author_name ?: $reply->author_username ?: 'Anonimo',
                'kind_label' => 'Respuesta en publicacion',
                'message' => $reply->comment_text,
                'date' => $this->formatConversationDate($reply->created_at),
                'time' => $this->formatConversationTime($reply->created_at),
                'short_date' => $this->formatConversationShortDate($reply->created_at),
                'is_automated' => false,
                'rule_label' => null,
                'created_at' => $reply->created_at,
            ];
        }

        WhatsappMessage::query()
            ->where(function ($query) use ($comment): void {
                $query->where('social_comment_id', $comment->id)
                    ->when($comment->tracking_token, function ($query) use ($comment): void {
                        $query->orWhere('message_body', 'like', '%'.$comment->tracking_token.'%');
                    });
            })
            ->orderBy('created_at')
            ->get()
            ->each(function (WhatsappMessage $msg) use (&$events): void {
                $events[] = [
                    'platform' => 'whatsapp',
                    'channel' => 'whatsapp',
                    'color' => 'green',
                    'channel_label' => 'WhatsApp',
                    'channel_class' => 'success',
                    'author' => $msg->direction === WhatsappMessageDirection::Incoming ? 'Cliente' : 'Clinica',
                    'kind_label' => 'Mensaje directo',
                    'message' => $msg->message_body,
                    'date' => $this->formatConversationDate($msg->created_at),
                    'time' => $this->formatConversationTime($msg->created_at),
                    'short_date' => $this->formatConversationShortDate($msg->created_at),
                    'is_automated' => false,
                    'rule_label' => null,
                    'created_at' => $msg->created_at,
                ];
            });

        $comment->actions()
            ->whereIn('action', [
                SocialCommentActionType::AutoReplySent->value,
                SocialCommentActionType::WhatsappSalesAgent->value,
                SocialCommentActionType::WhatsappClickFollowUpSent->value,
            ])
            ->oldest('created_at')
            ->get()
            ->each(function ($action) use (&$events, $comment): void {
                if ($action->action === SocialCommentActionType::WhatsappSalesAgent && $this->hasOutgoingWhatsappMessageForAction($comment, $action)) {
                    return;
                }

                $isWhatsappAgent = $action->action === SocialCommentActionType::WhatsappSalesAgent;
                $message = $action->response_text
                    ?: ($isWhatsappAgent ? ($action->external_response['reply'] ?? null) : null)
                    ?: $action->notes
                    ?: '';

                $events[] = [
                    'platform' => 'action',
                    'channel' => $isWhatsappAgent ? 'whatsapp' : ($comment->platform?->value ?? 'social'),
                    'color' => $isWhatsappAgent ? 'green' : 'orange',
                    'channel_label' => $isWhatsappAgent ? 'WhatsApp' : ($comment->platform?->label() ?? 'Social'),
                    'channel_class' => $isWhatsappAgent ? 'success' : match ($comment->platform?->value) {
                        'instagram' => 'hot',
                        'whatsapp' => 'success',
                        default => 'info',
                    },
                    'author' => 'Asistente IA',
                    'kind_label' => $isWhatsappAgent ? 'Respuesta automatica · WhatsApp' : 'Respuesta automatica · '.($comment->platform?->label() ?? 'Social'),
                    'message' => $message,
                    'date' => $this->formatConversationDate($action->created_at),
                    'time' => $this->formatConversationTime($action->created_at),
                    'short_date' => $this->formatConversationShortDate($action->created_at),
                    'is_automated' => true,
                    'rule_label' => $action->notes,
                    'created_at' => $action->created_at,
                ];
            });

        usort($events, fn ($a, $b): int => ($a['created_at']?->timestamp ?? 0) <=> ($b['created_at']?->timestamp ?? 0));

        return $events;
    }

    private function conversationMetrics(Collection $conversation): array
    {
        $messageCount = $conversation->count();
        $automatedCount = $conversation->where('is_automated', true)->count();
        $firstHumanEvent = $conversation->first(fn ($event): bool => ! ($event['is_automated'] ?? false) && filled($event['created_at'] ?? null));
        $firstAutoEvent = $conversation->first(fn ($event): bool => ($event['is_automated'] ?? false) && filled($event['created_at'] ?? null) && (! $firstHumanEvent || $event['created_at']->greaterThanOrEqualTo($firstHumanEvent['created_at'])));
        $responseTime = 'N/D';

        if ($firstHumanEvent && $firstAutoEvent) {
            $responseSeconds = (int) $firstHumanEvent['created_at']->diffInSeconds($firstAutoEvent['created_at']);
            $responseTime = $responseSeconds < 60 ? $responseSeconds.' seg' : floor($responseSeconds / 60).' min';
        }

        return [
            'automation_rate' => $messageCount > 0 ? (int) round(($automatedCount / $messageCount) * 100) : 0,
            'message_count' => $messageCount,
            'response_time' => $responseTime,
        ];
    }

    private function pulseMetrics(Collection $events): array
    {
        $videoEvents = $events->filter(fn ($event): bool => SocialLinkEventMapper::group($event->event_type) === 'video');
        $visitEvents = $events->filter(fn ($event): bool => SocialLinkEventMapper::group($event->event_type) === 'navigation');
        $whatsappClicks = $events->filter(fn ($event): bool => str_contains($event->event_type, 'whatsapp'))->count();
        $duration = (int) $events->sum('duration_seconds');

        return [
            ['label' => 'Eventos', 'value' => $events->count()],
            ['label' => 'Sesiones', 'value' => $events->isNotEmpty() ? max(1, $events->pluck('session_id')->filter()->unique()->count()) : 0],
            ['label' => 'Video', 'value' => ($videoEvents->max(fn ($event) => SocialLinkEventMapper::progress($event->event_type)) ?: 0).'%'],
            ['label' => 'Permanencia', 'value' => $duration > 0 ? $duration.'s' : '0s'],
            ['label' => 'Visitas', 'value' => $visitEvents->count()],
            ['label' => 'WA clics', 'value' => $whatsappClicks],
        ];
    }

    private function hasOutgoingWhatsappMessageForAction(SocialComment $comment, $action): bool
    {
        if (! $action->created_at) {
            return false;
        }

        return WhatsappMessage::query()
            ->where('social_comment_id', $comment->id)
            ->where('direction', WhatsappMessageDirection::Outgoing)
            ->whereBetween('created_at', [
                $action->created_at->copy()->subMinutes(2),
                $action->created_at->copy()->addMinutes(2),
            ])
            ->exists();
    }

    private function formatConversationDate($date): string
    {
        return $date ? $date->diffForHumans().', '.$date->format('g:i A') : 'Fecha no registrada';
    }

    private function formatConversationTime($date): string
    {
        return $date ? $date->format('H:i') : 'Sin hora';
    }

    private function formatConversationShortDate($date): string
    {
        if (! $date) {
            return 'Fecha no registrada';
        }

        return $date->isToday() ? 'Hoy, '.$date->format('H:i') : $date->format('d M, H:i');
    }
}
