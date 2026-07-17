<?php

namespace App\Services;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialCommentClassification;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPipelineStage;
use App\Enums\SocialPlatform;
use App\Enums\AppointmentStatus;
use App\Models\ActivityRecord;
use App\Models\Appointment;
use App\Models\SocialComment;
use App\Models\SocialIdentity;
use App\Models\SocialLinkEvent;
use App\Models\SocialPost;
use App\Support\SocialRoiPeriod;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SocialRoiService
{
    public function attributeActivity(ActivityRecord $activity): ?ActivityRecord
    {
        if ($activity->social_comment_id || ! $activity->patient_id) {
            return $activity->social_comment_id ? $activity : null;
        }

        $identity = SocialIdentity::query()
            ->where('patient_id', $activity->patient_id)
            ->latest('linked_at')
            ->latest('last_seen_at')
            ->first();

        if (! $identity) {
            return null;
        }

        $comment = SocialComment::query()
            ->where('social_identity_id', $identity->id)
            ->whereNotNull('social_post_id')
            ->latest('converted_at')
            ->latest('whatsapp_redirected_at')
            ->latest('created_at')
            ->first();

        if (! $comment) {
            return null;
        }

        $activity->update([
            'social_comment_id' => $comment->id,
            'social_identity_id' => $identity->id,
            'social_post_id' => $comment->social_post_id,
            'social_attributed_at' => now(),
        ]);

        $comment->update([
            'conversion_status' => SocialConversionStatus::Converted,
            'converted_patient_id' => $activity->patient_id,
            'converted_at' => $comment->converted_at ?: now(),
        ]);

        $identity->update([
            'status' => SocialIdentityStatus::Converted,
            'last_seen_at' => now(),
        ]);

        $this->refreshPostMetrics($comment->socialPost);

        return $activity->refresh();
    }

    public function refreshPostMetrics(?SocialPost $post): void
    {
        if (! $post) {
            return;
        }

        $metrics = ActivityRecord::query()
            ->where('social_post_id', $post->id)
            ->selectRaw('COALESCE(SUM(internal_rate_snapshot), 0) as revenue, COUNT(*) as conversions')
            ->first();

        $post->update([
            'revenue_generated' => (float) ($metrics->revenue ?? 0),
            'conversion_count' => (int) ($metrics->conversions ?? 0),
        ]);
    }

    public function summary(?array $filters = null): array
    {
        $period = SocialRoiPeriod::resolve($filters);
        $socialActivities = $this->socialActivitiesQuery($period);
        $comments = $this->commentsQuery($period);
        $commercial = $this->commercialSummary($filters);
        $appointments = $this->appointmentSummary($filters);

        $leadCount = (clone $comments)->whereNotNull('social_identity_id')->count();
        $whatsappCount = (clone $comments)
            ->where(function (Builder $query): void {
                $query->whereNotNull('whatsapp_redirected_at')
                    ->orWhere('platform', SocialPlatform::Whatsapp->value);
            })
            ->count();
        $linkedCount = (clone $comments)->whereNotNull('converted_patient_id')->count();
        $activityCount = (clone $socialActivities)->count();
        $revenue = (clone $socialActivities)->sum('internal_rate_snapshot');
        $leakageCount = $this->leakageQuery($filters)->count();

        return [
            'period_label' => $period['label'],
            'previous_period_label' => $period['previous_label'],
            'lead_count' => $leadCount,
            'whatsapp_count' => $whatsappCount,
            'linked_count' => $linkedCount,
            'activity_count' => $activityCount,
            'revenue' => (float) $revenue,
            'leakage_count' => $leakageCount,
            'orphan_attribution_count' => $this->orphanAttributionCount($filters),
            'lead_to_activity_rate' => $leadCount > 0 ? round(($activityCount / $leadCount) * 100, 1) : 0,
            ...$commercial,
            ...$appointments,
        ];
    }

    public function appointmentSummary(?array $filters = null): array
    {
        $period = SocialRoiPeriod::resolve($filters);
        $comments = $this->commentsQuery($period);
        $appointments = $this->appointmentsQuery($period);

        $leadCount = (clone $comments)->whereNotNull('social_identity_id')->count();
        $whatsappCount = (clone $comments)->whereNotNull('whatsapp_redirected_at')->count();
        $appointmentCount = (clone $appointments)->count();
        $confirmedCount = (clone $appointments)->whereIn('status', [
            AppointmentStatus::Confirmed->value,
            AppointmentStatus::Completed->value,
        ])->count();
        $completedCount = (clone $appointments)->where('status', AppointmentStatus::Completed->value)->count();
        $noShowCount = (clone $appointments)->where('status', AppointmentStatus::NoShow->value)->count();
        $appointmentLeakageCount = $this->appointmentLeakageQuery($filters)->count();

        return [
            'appointment_count' => $appointmentCount,
            'appointment_confirmed_count' => $confirmedCount,
            'appointment_completed_count' => $completedCount,
            'appointment_no_show_count' => $noShowCount,
            'appointment_leakage_count' => $appointmentLeakageCount,
            'lead_to_appointment_rate' => $leadCount > 0 ? round(($appointmentCount / $leadCount) * 100, 1) : 0,
            'whatsapp_to_appointment_rate' => $whatsappCount > 0 ? round(($appointmentCount / $whatsappCount) * 100, 1) : 0,
            'appointment_to_activity_rate' => $appointmentCount > 0 ? round(((clone $this->socialActivitiesQuery($period))->count() / $appointmentCount) * 100, 1) : 0,
        ];
    }

    public function appointmentStatusData(?array $filters = null): array
    {
        $period = SocialRoiPeriod::resolve($filters);
        $appointments = $this->appointmentsQuery($period);

        $statuses = [
            AppointmentStatus::PendingConfirmation->value => 'Pendiente',
            AppointmentStatus::Scheduled->value => 'Programada',
            AppointmentStatus::Confirmed->value => 'Confirmada',
            AppointmentStatus::Completed->value => 'Completada',
            AppointmentStatus::NoShow->value => 'No asistio',
            AppointmentStatus::Cancelled->value => 'Cancelada',
        ];

        $counts = (clone $appointments)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $values = [];

        foreach ($statuses as $status => $label) {
            $labels[] = $label;
            $values[] = (int) ($counts[$status] ?? 0);
        }

        return compact('labels', 'values');
    }

    public function appointmentPerformanceByPost(int $limit = 8, ?array $filters = null): Collection
    {
        $period = SocialRoiPeriod::resolve($filters);

        return SocialPost::query()
            ->with('socialAccount')
            ->join('appointments', 'appointments.social_post_id', '=', 'social_posts.id')
            ->leftJoin('activity_records', 'activity_records.social_post_id', '=', 'social_posts.id')
            ->whereBetween('appointments.created_at', [$period['from'], $period['until']])
            ->select('social_posts.*')
            ->selectRaw('COUNT(DISTINCT appointments.id) as appointment_count')
            ->selectRaw('COUNT(DISTINCT activity_records.id) as conversion_count')
            ->selectRaw('COALESCE(SUM(DISTINCT activity_records.internal_rate_snapshot), 0) as revenue_generated')
            ->groupBy('social_posts.id')
            ->orderByDesc(DB::raw('COUNT(DISTINCT appointments.id)'))
            ->orderByDesc(DB::raw('COALESCE(SUM(DISTINCT activity_records.internal_rate_snapshot), 0)'))
            ->limit($limit)
            ->get();
    }

    public function commercialSummary(?array $filters = null): array
    {
        $period = SocialRoiPeriod::resolve($filters);
        $comments = $this->commentsQuery($period);

        $leadCount = (clone $comments)->whereNotNull('tracking_token')->count();
        $whatsappCount = (clone $comments)->whereNotNull('whatsapp_redirected_at')->count();

        return [
            'pipeline_value' => (float) SocialComment::query()
                ->whereNotIn('pipeline_stage', [
                    SocialPipelineStage::Won->value,
                    SocialPipelineStage::Lost->value,
                ])
                ->sum('estimated_value'),
            'won_value_month' => (float) SocialComment::query()
                ->where('pipeline_stage', SocialPipelineStage::Won->value)
                ->whereBetween('updated_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('estimated_value'),
            'high_value_lost_count' => SocialComment::query()
                ->where('pipeline_stage', SocialPipelineStage::Lost->value)
                ->where('estimated_value', '>=', 1000)
                ->count(),
            'smart_link_to_whatsapp_rate' => $leadCount > 0 ? round(($whatsappCount / $leadCount) * 100, 1) : 0,
            'active_hot_leads_count' => SocialComment::query()
                ->whereNotNull('hot_lead_at')
                ->whereNull('lost_at')
                ->where(function (Builder $query): void {
                    $query->whereNull('pipeline_stage')
                        ->orWhereNotIn('pipeline_stage', [
                            SocialPipelineStage::Won->value,
                            SocialPipelineStage::Lost->value,
                        ]);
                })
                ->count(),
        ];
    }

    public function orphanAttributionCount(?array $filters = null): int
    {
        $period = SocialRoiPeriod::resolve($filters);

        return $this->commentsQuery($period)
            ->where('conversion_status', SocialConversionStatus::TokenGenerated->value)
            ->whereNull('whatsapp_redirected_at')
            ->count();
    }

    public function platformPerformanceData(?array $filters = null): array
    {
        $period = SocialRoiPeriod::resolve($filters);
        $platforms = collect(SocialPlatform::cases());

        $leadRows = $this->commentsQuery($period)
            ->leftJoin('social_identities', 'social_identities.id', '=', 'social_comments.social_identity_id')
            ->whereIn('social_comments.classification', [
                SocialCommentClassification::SalesLead->value,
                SocialCommentClassification::CommercialQuestion->value,
            ])
            ->selectRaw('social_comments.platform as platform')
            ->selectRaw('SUM(CASE WHEN social_identities.patient_id IS NULL THEN 1 ELSE 0 END) as new_leads')
            ->selectRaw('SUM(CASE WHEN social_identities.patient_id IS NOT NULL THEN 1 ELSE 0 END) as recurring_patients')
            ->groupBy('social_comments.platform')
            ->get()
            ->keyBy('platform');

        $revenueRows = ActivityRecord::query()
            ->leftJoin('social_posts', 'social_posts.id', '=', 'activity_records.social_post_id')
            ->leftJoin('social_comments', 'social_comments.id', '=', 'activity_records.social_comment_id')
            ->leftJoin('social_identities', 'social_identities.id', '=', 'activity_records.social_identity_id')
            ->where(function (Builder $query): void {
                $query->whereNotNull('activity_records.social_post_id')
                    ->orWhereNotNull('activity_records.social_comment_id')
                    ->orWhereNotNull('activity_records.social_identity_id');
            })
            ->whereBetween('activity_records.activity_date', [$period['from_date'], $period['until_date']])
            ->selectRaw('COALESCE(social_posts.platform, social_comments.platform, social_identities.platform) as platform')
            ->selectRaw('COALESCE(SUM(activity_records.internal_rate_snapshot), 0) as revenue')
            ->groupByRaw('COALESCE(social_posts.platform, social_comments.platform, social_identities.platform)')
            ->pluck('revenue', 'platform');

        return [
            'labels' => $platforms->map(fn (SocialPlatform $platform): string => $platform->label())->all(),
            'new_leads' => $platforms->map(fn (SocialPlatform $platform): int => (int) ($leadRows[$platform->value]->new_leads ?? 0))->all(),
            'recurring_patients' => $platforms->map(fn (SocialPlatform $platform): int => (int) ($leadRows[$platform->value]->recurring_patients ?? 0))->all(),
            'revenue' => $platforms->map(fn (SocialPlatform $platform): float => round((float) ($revenueRows[$platform->value] ?? 0), 2))->all(),
        ];
    }

    public function procedureConversionData(int $limit = 5, ?array $filters = null): array
    {
        $period = SocialRoiPeriod::resolve($filters);

        $rows = $this->commentsQuery($period)
            ->leftJoin('procedures', 'procedures.id', '=', 'social_comments.suggested_procedure_id')
            ->leftJoin('activity_records', function ($join) use ($period): void {
                $join->on('activity_records.social_comment_id', '=', 'social_comments.id')
                    ->whereBetween('activity_records.activity_date', [$period['from_date'], $period['until_date']]);
            })
            ->selectRaw("COALESCE(procedures.name, 'Consulta General/Otros') as label")
            ->selectRaw('COUNT(DISTINCT social_comments.id) as comments_count')
            ->selectRaw('COUNT(DISTINCT activity_records.id) as conversions_count')
            ->groupByRaw("COALESCE(procedures.name, 'Consulta General/Otros')")
            ->orderByDesc('comments_count')
            ->limit($limit)
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'comments' => $rows->map(fn ($row): int => (int) $row->comments_count)->all(),
            'conversion_rates' => $rows->map(function ($row): float {
                $comments = (int) $row->comments_count;

                if ($comments === 0) {
                    return 0;
                }

                return round(((int) $row->conversions_count / $comments) * 100, 1);
            })->all(),
        ];
    }

    public function responseTimeVsRevenueData(?array $filters = null): array
    {
        $period = SocialRoiPeriod::resolve($filters);
        $start = $period['from']->copy()->startOfWeek();
        $end = $period['until']->copy()->endOfWeek();
        $actions = [
            SocialCommentActionType::Reply->value,
            SocialCommentActionType::RedirectToWhatsapp->value,
            SocialCommentActionType::WhatsappHandshake->value,
            SocialCommentActionType::LinkIdentity->value,
            SocialCommentActionType::CreatePatientFromLead->value,
        ];
        $firstActions = DB::table('social_comment_actions')
            ->selectRaw('social_comment_id, MIN(created_at) as first_action_at')
            ->whereIn('action', $actions)
            ->groupBy('social_comment_id');

        $labels = [];
        $responseMinutes = [];
        $revenue = [];

        for ($weekStart = $start->copy(); $weekStart->lte($end); $weekStart->addWeek()) {
            $weekEnd = (clone $weekStart)->endOfWeek();
            $bucketStart = $weekStart->copy()->max($period['from']);
            $bucketEnd = $weekEnd->copy()->min($period['until']);

            $averageMinutes = DB::table('social_comments')
                ->joinSub($firstActions, 'first_actions', function ($join): void {
                    $join->on('first_actions.social_comment_id', '=', 'social_comments.id');
                })
                ->whereBetween('social_comments.created_at', [$bucketStart, $bucketEnd])
                ->selectRaw('AVG(EXTRACT(EPOCH FROM (first_actions.first_action_at::timestamp - social_comments.created_at::timestamp)) / 60) as average_minutes')
                ->value('average_minutes');

            $weekRevenue = ActivityRecord::query()
                ->whereNotNull('social_attributed_at')
                ->whereBetween('activity_date', [$bucketStart->toDateString(), $bucketEnd->toDateString()])
                ->sum('internal_rate_snapshot');

            $labels[] = 'Sem '.$weekStart->isoWeek();
            $responseMinutes[] = round((float) ($averageMinutes ?? 0), 1);
            $revenue[] = round((float) $weekRevenue, 2);
        }

        return [
            'labels' => $labels,
            'response_minutes' => $responseMinutes,
            'revenue' => $revenue,
        ];
    }

    public function topPosts(int $limit = 8, ?array $filters = null): Collection
    {
        $period = SocialRoiPeriod::resolve($filters);

        return SocialPost::query()
            ->with('socialAccount')
            ->join('activity_records', 'activity_records.social_post_id', '=', 'social_posts.id')
            ->whereBetween('activity_records.activity_date', [$period['from_date'], $period['until_date']])
            ->select('social_posts.*')
            ->selectRaw('COALESCE(SUM(activity_records.internal_rate_snapshot), 0) as revenue_generated')
            ->selectRaw('COUNT(activity_records.id) as conversion_count')
            ->groupBy('social_posts.id')
            ->orderByDesc(DB::raw('COALESCE(SUM(activity_records.internal_rate_snapshot), 0)'))
            ->orderByDesc(DB::raw('COUNT(activity_records.id)'))
            ->limit($limit)
            ->get();
    }

    public function pipelineValueByStage(?array $filters = null): array
    {
        $period = SocialRoiPeriod::resolve($filters);
        $rows = $this->commentsQuery($period)
            ->selectRaw('pipeline_stage, COUNT(*) as total_count, COALESCE(SUM(estimated_value), 0) as total_value')
            ->whereNotNull('pipeline_stage')
            ->groupBy('pipeline_stage')
            ->get()
            ->keyBy('pipeline_stage');

        $stages = collect(SocialPipelineStage::cases());

        return [
            'labels' => $stages->map(fn (SocialPipelineStage $stage): string => $stage->label())->all(),
            'values' => $stages->map(fn (SocialPipelineStage $stage): float => round((float) ($rows[$stage->value]->total_value ?? 0), 2))->all(),
            'counts' => $stages->map(fn (SocialPipelineStage $stage): int => (int) ($rows[$stage->value]->total_count ?? 0))->all(),
        ];
    }

    public function lostReasonsData(?array $filters = null): array
    {
        $period = SocialRoiPeriod::resolve($filters);
        $rows = $this->commentsQuery($period)
            ->where('pipeline_stage', SocialPipelineStage::Lost->value)
            ->selectRaw("COALESCE(NULLIF(lost_reason, ''), 'Sin motivo') as reason")
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('COALESCE(SUM(estimated_value), 0) as total_value')
            ->groupByRaw("COALESCE(NULLIF(lost_reason, ''), 'Sin motivo')")
            ->orderByDesc('total_value')
            ->orderByDesc('total_count')
            ->limit(8)
            ->get();

        return [
            'labels' => $rows->pluck('reason')->all(),
            'counts' => $rows->map(fn ($row): int => (int) $row->total_count)->all(),
            'values' => $rows->map(fn ($row): float => round((float) $row->total_value, 2))->all(),
        ];
    }

    public function weeklyLeakageReport(?CarbonInterface $weekStart = null, float $minimumValue = 1000): array
    {
        $start = ($weekStart ? $weekStart->copy() : now()->subWeek())->startOfWeek();
        $end = $start->copy()->endOfWeek();

        $leads = SocialComment::query()
            ->with(['suggestedProcedure', 'socialIdentity.patient', 'convertedPatient'])
            ->where('pipeline_stage', SocialPipelineStage::Lost->value)
            ->where('estimated_value', '>=', $minimumValue)
            ->whereBetween(DB::raw('COALESCE(lost_at, updated_at)'), [$start, $end])
            ->orderByDesc('estimated_value')
            ->get()
            ->map(function (SocialComment $comment): array {
                return [
                    'id' => $comment->id,
                    'lead_name' => $comment->socialIdentity?->patient?->full_name
                        ?? $comment->convertedPatient?->full_name
                        ?? $comment->author_name
                        ?? $comment->author_username
                        ?? 'Lead #'.$comment->id,
                    'procedure' => $comment->suggestedProcedure?->name ?? 'Sin procedimiento',
                    'estimated_value' => (float) $comment->estimated_value,
                    'lost_reason' => $comment->lost_reason ?: 'Sin motivo registrado',
                    'last_activity_at' => $this->lastLeadActivityAt($comment)?->format('d/m/Y H:i') ?? 'Sin actividad',
                    'recovery_recommendation' => $this->recoveryRecommendation($comment),
                ];
            });

        return [
            'period_start' => $start,
            'period_end' => $end,
            'minimum_value' => $minimumValue,
            'total_value' => round((float) $leads->sum('estimated_value'), 2),
            'total_leads' => $leads->count(),
            'leads' => $leads,
            'audit' => $this->lostReasonAudit([
                'period' => 'custom',
                'from' => $start->toDateString(),
                'until' => $end->toDateString(),
            ]),
        ];
    }

    public function lostReasonAudit(?array $filters = null): array
    {
        $data = $this->lostReasonsData($filters);
        $totalLost = array_sum($data['counts']);

        if ($totalLost === 0) {
            return [
                'source' => 'local',
                'top_motivos' => [],
                'recomendaciones' => ['No hay leads perdidos en el periodo seleccionado.'],
                'alertas' => [],
            ];
        }

        $rows = collect($data['labels'])->map(function (string $reason, int $index) use ($data, $totalLost): array {
            $count = (int) ($data['counts'][$index] ?? 0);

            return [
                'motivo' => $reason,
                'cantidad' => $count,
                'porcentaje' => $totalLost > 0 ? round(($count / $totalLost) * 100, 1) : 0,
                'valor_estimado' => (float) ($data['values'][$index] ?? 0),
            ];
        });

        try {
            $response = app(GeminiJsonService::class)->generate(
                'Eres un auditor comercial para una clinica dental. Responde solo JSON valido.',
                "Analiza estos motivos de perdida y devuelve top_motivos, recomendaciones y alertas: \n".$rows->toJson(JSON_UNESCAPED_UNICODE),
            );
            $decoded = json_decode($response, true);

            if (is_array($decoded)) {
                return ['source' => 'gemini', ...$decoded];
            }
        } catch (\Throwable) {
            // El reporte debe poder generarse aunque Gemini no este configurado.
        }

        return [
            'source' => 'local',
            'top_motivos' => $rows->take(5)->values()->all(),
            'recomendaciones' => [
                'Contactar primero los leads perdidos con mayor valor estimado.',
                'Crear guiones por objecion recurrente: precio, tiempo, confianza y financiacion.',
                'Revisar en reunion semanal cualquier motivo que concentre mas del 30% de perdidas.',
            ],
            'alertas' => $rows
                ->filter(fn (array $row): bool => $row['porcentaje'] >= 30 || $row['valor_estimado'] >= 3000)
                ->map(fn (array $row): string => "{$row['motivo']} concentra {$row['porcentaje']}% de perdidas por $".number_format($row['valor_estimado'], 2))
                ->values()
                ->all(),
        ];
    }

    public function leakageQuery(?array $filters = null): Builder
    {
        $period = SocialRoiPeriod::resolve($filters);

        return $this->commentsQuery($period)
            ->whereNull('converted_patient_id')
            ->whereNull('whatsapp_redirected_at')
            ->where('created_at', '<=', now()->subDay())
            ->where(function (Builder $query): void {
                $query->whereIn('classification', ['sales_lead', 'commercial_question'])
                    ->orWhereNotNull('social_identity_id');
            });
    }

    public function appointmentLeakageQuery(?array $filters = null): Builder
    {
        $period = SocialRoiPeriod::resolve($filters);

        return $this->appointmentsQuery($period)
            ->whereNotNull('appointments.social_comment_id')
            ->whereNotNull('appointments.scheduled_at')
            ->where('appointments.scheduled_at', '<=', now()->subDay())
            ->whereIn('appointments.status', [
                AppointmentStatus::PendingConfirmation->value,
                AppointmentStatus::Scheduled->value,
                AppointmentStatus::Confirmed->value,
            ])
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('activity_records')
                    ->whereColumn('activity_records.social_comment_id', 'appointments.social_comment_id');
            });
    }

    public function funnelData(?array $filters = null): array
    {
        $summary = $this->summary($filters);

        return [
            'labels' => ['Comentarios', 'WhatsApp', 'Ficha', 'Citas', 'Actividad'],
            'values' => [
                $summary['lead_count'],
                $summary['whatsapp_count'],
                $summary['linked_count'],
                $summary['appointment_count'],
                $summary['activity_count'],
            ],
        ];
    }

    public function financialHighlights(?array $filters = null): array
    {
        $period = SocialRoiPeriod::resolve($filters);

        $activityQuery = ActivityRecord::query()
            ->whereBetween('activity_date', [$period['from_date'], $period['until_date']]);

        $totalRevenue = (clone $activityQuery)->sum('internal_rate_snapshot');

        $attributedRevenue = (clone $activityQuery)
            ->where(function (Builder $query): void {
                $query->whereNotNull('social_post_id')
                    ->orWhereNotNull('social_comment_id')
                    ->orWhereNotNull('social_identity_id');
            })
            ->sum('internal_rate_snapshot');

        $wonPipeline = SocialComment::query()
            ->where('pipeline_stage', SocialPipelineStage::Won->value)
            ->whereBetween('converted_at', [$period['from'], $period['until']])
            ->sum('estimated_value');

        $nonAttributed = $totalRevenue - $attributedRevenue;

        return [
            'total_revenue' => (float) $totalRevenue,
            'attributed_revenue' => (float) $attributedRevenue,
            'non_attributed_revenue' => max(0, (float) $nonAttributed),
            'won_pipeline_value' => (float) $wonPipeline,
            'attribution_rate' => $totalRevenue > 0 ? round(($attributedRevenue / $totalRevenue) * 100, 1) : 0,
        ];
    }

    private function commentsQuery(array $period): Builder
    {
        return SocialComment::query()
            ->whereBetween('social_comments.created_at', [$period['from'], $period['until']]);
    }

    private function socialActivitiesQuery(array $period): Builder
    {
        return ActivityRecord::query()
            ->whereNotNull('social_post_id')
            ->whereBetween('activity_date', [$period['from_date'], $period['until_date']]);
    }

    private function appointmentsQuery(array $period): Builder
    {
        return Appointment::query()
            ->where(function (Builder $query): void {
                $query->whereNotNull('social_post_id')
                    ->orWhereNotNull('social_comment_id')
                    ->orWhereNotNull('social_identity_id');
            })
            ->whereBetween('created_at', [$period['from'], $period['until']]);
    }

    private function lastLeadActivityAt(SocialComment $comment): ?CarbonInterface
    {
        $lastEventAt = SocialLinkEvent::query()
            ->where('social_comment_id', $comment->id)
            ->latest('created_at')
            ->value('created_at');

        return $lastEventAt ? Carbon::parse($lastEventAt) : $comment->updated_at;
    }

    private function recoveryRecommendation(SocialComment $comment): string
    {
        $reason = str($comment->lost_reason ?? '')->ascii()->lower()->toString();

        return match (true) {
            str_contains($reason, 'precio') => 'Ofrecer alternativa de financiacion, explicar fases del tratamiento y reforzar valor clinico.',
            str_contains($reason, 'tiempo') => 'Proponer horarios flexibles y una cita corta de reactivacion por WhatsApp.',
            str_contains($reason, 'confianza') => 'Enviar caso similar, testimonio y llamada breve con el doctor.',
            str_contains($reason, 'financi') => 'Enviar plan de pago y separar valoracion sin compromiso.',
            default => 'Reactivar con mensaje personalizado, beneficio claro y una unica proxima accion.',
        };
    }
}
