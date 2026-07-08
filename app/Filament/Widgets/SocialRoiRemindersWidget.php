<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Enums\SocialPipelineStage;
use App\Models\Appointment;
use App\Models\SocialComment;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class SocialRoiRemindersWidget extends Widget
{
    protected static ?int $sort = 31;

    protected int|string|array $columnSpan = ['md' => 1, 'xl' => 2];

    protected string $view = 'filament.widgets.social-roi-reminders-widget';

    protected ?string $heading = 'Recordatorios';

    public function getHeading(): ?string
    {
        return $this->heading;
    }

    protected function getReminders(): Collection
    {
        $appointmentLeakage = Appointment::query()
            ->whereNotNull('social_comment_id')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now()->subDay())
            ->whereIn('status', [
                AppointmentStatus::PendingConfirmation->value,
                AppointmentStatus::Scheduled->value,
                AppointmentStatus::Confirmed->value,
            ])
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('activity_records')
                    ->whereColumn('activity_records.social_comment_id', 'appointments.social_comment_id');
            })
            ->count();

        $hotLeads = SocialComment::query()
            ->whereNull('lost_at')
            ->where(function ($query): void {
                $query->whereNull('pipeline_stage')
                    ->orWhereNotIn('pipeline_stage', [
                        SocialPipelineStage::Won->value,
                        SocialPipelineStage::Lost->value,
                    ]);
            })
            ->where('created_at', '<=', now()->subDays(3))
            ->whereNotNull('tracking_token')
            ->count();

        $orphanLeads = SocialComment::query()
            ->whereNull('converted_patient_id')
            ->whereNull('whatsapp_redirected_at')
            ->where('created_at', '<=', now()->subDays(2))
            ->whereIn('classification', ['sales_lead', 'commercial_question'])
            ->count();

        $highValueLost = SocialComment::query()
            ->where('pipeline_stage', SocialPipelineStage::Lost->value)
            ->where('estimated_value', '>=', 1000)
            ->whereDate('lost_at', today())
            ->count();

        return collect([
            [
                'label' => 'Citas vencidas sin actividad',
                'value' => $appointmentLeakage,
                'description' => $appointmentLeakage > 0
                    ? 'Requieren seguimiento para evitar perdida del paciente'
                    : 'Sin citas pendientes de actividad',
                'priority' => $appointmentLeakage > 3 ? 'danger' : ($appointmentLeakage > 0 ? 'warning' : 'success'),
                'icon' => 'heroicon-o-calendar-days',
            ],
            [
                'label' => 'Leads calientes sin cierre',
                'value' => $hotLeads,
                'description' => $hotLeads > 0
                    ? 'Contacto virtual sin avanzar a cita mas de 3 dias'
                    : 'Todos los leads calientes estan siendo gestionados',
                'priority' => $hotLeads > 5 ? 'danger' : ($hotLeads > 0 ? 'warning' : 'success'),
                'icon' => 'heroicon-o-fire',
            ],
            [
                'label' => 'Leads sin seguimiento',
                'value' => $orphanLeads,
                'description' => $orphanLeads > 0
                    ? 'Comentarios clasificados sin redireccion a WhatsApp'
                    : 'Sin leads sin atender',
                'priority' => $orphanLeads > 5 ? 'danger' : ($orphanLeads > 0 ? 'warning' : 'success'),
                'icon' => 'heroicon-o-chat-bubble-left-right',
            ],
            [
                'label' => 'Perdidos de alto valor hoy',
                'value' => $highValueLost,
                'description' => $highValueLost > 0
                    ? 'Perdidas superiores a $1,000 registradas hoy'
                    : 'Sin perdidas de alto valor hoy',
                'priority' => $highValueLost > 0 ? 'danger' : 'success',
                'icon' => 'heroicon-o-exclamation-triangle',
            ],
        ]);
    }

    public function getRemindersData(): array
    {
        return $this->getReminders()->all();
    }
}
