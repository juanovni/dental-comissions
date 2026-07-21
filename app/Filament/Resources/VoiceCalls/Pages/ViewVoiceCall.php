<?php

namespace App\Filament\Resources\VoiceCalls\Pages;

use App\Enums\VoiceCallStatus;
use App\Enums\VoiceChannelType;
use App\Enums\VoiceEventType;
use App\Filament\Resources\VoiceCalls\VoiceCallResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Timeline;
use Filament\Schemas\Components\Timeline\TimelineEntry;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;

class ViewVoiceCall extends ViewRecord
{
    protected static string $resource = VoiceCallResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Detalles de la llamada')
                ->columns(3)
                ->schema([
                    TextEntry::make('from_phone')
                        ->label('De'),
                    TextEntry::make('to_phone')
                        ->label('Para')
                        ->placeholder('-'),
                    TextEntry::make('channel')
                        ->label('Canal')
                        ->formatStateUsing(fn (VoiceChannelType $state): string => $state->label()),
                    TextEntry::make('provider')
                        ->label('Proveedor')
                        ->placeholder('-')
                        ->badge()
                        ->color('gray'),
                    TextEntry::make('status')
                        ->label('Estado')
                        ->badge()
                        ->formatStateUsing(fn (VoiceCallStatus $state): string => $state->label())
                        ->color(fn (VoiceCallStatus $state): string => match ($state) {
                            VoiceCallStatus::Started => 'warning',
                            VoiceCallStatus::InProgress => 'info',
                            VoiceCallStatus::AppointmentScheduled => 'success',
                            VoiceCallStatus::HandoffRequired => 'danger',
                            VoiceCallStatus::Completed => 'gray',
                            VoiceCallStatus::Failed => 'danger',
                            VoiceCallStatus::Cancelled => 'gray',
                        }),
                    TextEntry::make('handoff_reason')
                        ->label('Motivo de transferencia')
                        ->badge()
                        ->formatStateUsing(fn (mixed $state): ?string => $state?->label())
                        ->placeholder('No aplica')
                        ->color('warning'),
                    TextEntry::make('started_at')
                        ->label('Inicio')
                        ->dateTime('d/m/Y H:i:s'),
                    TextEntry::make('ended_at')
                        ->label('Fin')
                        ->placeholder('En curso')
                        ->dateTime('d/m/Y H:i:s'),
                    TextEntry::make('duration_seconds')
                        ->label('Duracion')
                        ->formatStateUsing(fn (?int $state): string => $state ? gmdate('i:s', $state) : '-'),
                ]),
            Section::make('Paciente')
                ->columns(2)
                ->visible(fn ($record): bool => $record->patient_id !== null)
                ->schema([
                    TextEntry::make('patient.full_name')
                        ->label('Nombre')
                        ->url(fn ($record): ?string => $record->patient_id
                            ? route('filament.admin.resources.patients.edit', $record->patient_id)
                            : null),
                    TextEntry::make('patient.phone')
                        ->label('Telefono'),
                ]),
            Section::make('Cita relacionada')
                ->columns(2)
                ->visible(fn ($record): bool => $record->appointment_id !== null)
                ->schema([
                    TextEntry::make('appointment.patient.full_name')
                        ->label('Paciente'),
                    TextEntry::make('appointment.procedure.name')
                        ->label('Procedimiento'),
                    TextEntry::make('appointment.scheduled_at')
                        ->label('Fecha agendada')
                        ->dateTime('d/m/Y H:i'),
                    TextEntry::make('appointment.status')
                        ->label('Estado')
                        ->badge()
                        ->formatStateUsing(fn (mixed $state): ?string => $state?->label())
                        ->color(fn (mixed $state): ?string => $state?->color()),
                    TextEntry::make('appointment.id')
                        ->label('Ver cita')
                        ->url(fn ($record): ?string => $record->appointment_id
                            ? route('filament.admin.resources.appointments.view', $record->appointment_id)
                            : null),
                ]),
            Section::make('Transcript')
                ->collapsible()
                ->collapsed()
                ->visible(fn ($record): bool => filled($record->transcript))
                ->schema([
                    TextEntry::make('transcript')
                        ->label('')
                        ->markdown()
                        ->prose()
                        ->columnSpanFull(),
                ]),
            Section::make('Resumen IA')
                ->collapsible()
                ->collapsed()
                ->visible(fn ($record): bool => filled($record->ai_summary))
                ->schema([
                    TextEntry::make('ai_summary')
                        ->label('')
                        ->columnSpanFull(),
                ]),
            Section::make('Errores')
                ->collapsible()
                ->collapsed()
                ->visible(fn ($record): bool => filled($record->last_error))
                ->schema([
                    TextEntry::make('last_error')
                        ->label('Ultimo error')
                        ->color('danger'),
                ]),
            Section::make('Eventos de la llamada')
                ->collapsible()
                ->schema(function ($record): array {
                    $events = $record->events()->orderBy('id')->get();

                    if ($events->isEmpty()) {
                        return [
                            TextEntry::make('events_placeholder')
                                ->label('')
                                ->default('Sin eventos registrados.'),
                        ];
                    }

                    return [
                        Timeline::make()
                            ->entries($events->map(fn ($event) => TimelineEntry::make()
                                ->label(match (true) {
                                    $event->type === VoiceEventType::CallEvent => $event->payload['telnyx_event'] ?? 'Evento',
                                    $event->type === VoiceEventType::UserMessage => 'Usuario',
                                    $event->type === VoiceEventType::AssistantMessage => $event->payload['kind'] ?? 'Pity',
                                    $event->type === VoiceEventType::ToolCalled => 'Tool: ' . ($event->payload['tool'] ?? ''),
                                    $event->type === VoiceEventType::ToolResult => 'Resultado: ' . ($event->payload['tool'] ?? ''),
                                    $event->type === VoiceEventType::AppointmentCreated => 'Cita creada',
                                    $event->type === VoiceEventType::HandoffRequested => 'Transferencia solicitada',
                                    $event->type === VoiceEventType::SessionStarted => 'Sesion iniciada',
                                    $event->type === VoiceEventType::SessionEnded => 'Sesion finalizada',
                                    $event->type === VoiceEventType::Error => 'Error',
                                    default => $event->type?->label() ?? $event->type,
                                })
                                ->color(match (true) {
                                    $event->type === VoiceEventType::UserMessage => 'info',
                                    $event->type === VoiceEventType::AssistantMessage => 'success',
                                    $event->type === VoiceEventType::Error => 'danger',
                                    $event->type === VoiceEventType::CallEvent && str_contains($event->payload['telnyx_event'] ?? '', 'hangup') => 'gray',
                                    $event->type === VoiceEventType::CallEvent && str_contains($event->payload['telnyx_event'] ?? '', 'transcription') => 'warning',
                                    default => 'gray',
                                })
                                ->icon(match (true) {
                                    $event->type === VoiceEventType::UserMessage => 'heroicon-m-user',
                                    $event->type === VoiceEventType::AssistantMessage => 'heroicon-m-robot',
                                    $event->type === VoiceEventType::ToolCalled => 'heroicon-m-wrench',
                                    $event->type === VoiceEventType::ToolResult => 'heroicon-m-check-circle',
                                    $event->type === VoiceEventType::CallEvent && str_contains($event->payload['telnyx_event'] ?? '', 'hangup') => 'heroicon-m-phone-x-mark',
                                    $event->type === VoiceEventType::CallEvent && str_contains($event->payload['telnyx_event'] ?? '', 'initiated') => 'heroicon-m-phone-arrow-up-right',
                                    $event->type === VoiceEventType::CallEvent && str_contains($event->payload['telnyx_event'] ?? '', 'answered') => 'heroicon-m-phone',
                                    $event->type === VoiceEventType::CallEvent && str_contains($event->payload['telnyx_event'] ?? '', 'transcription') => 'heroicon-m-speaker-wave',
                                    $event->type === VoiceEventType::CallEvent && str_contains($event->payload['telnyx_event'] ?? '', 'speak') => 'heroicon-m-megaphone',
                                    $event->type === VoiceEventType::Error => 'heroicon-m-exclamation-triangle',
                                    default => 'heroicon-m-circle',
                                })
                                ->simpleContent(
                                    $event->type === VoiceEventType::UserMessage || $event->type === VoiceEventType::AssistantMessage
                                        ? ($event->payload['message'] ?? '')
                                        : ($event->payload['telnyx_event'] ?? $event->type?->label() ?? '')
                                )
                                ->timestamp($event->created_at),
                            )->toArray()),
                    ];
                }),
        ]);
    }
}
