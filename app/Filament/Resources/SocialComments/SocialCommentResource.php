<?php

namespace App\Filament\Resources\SocialComments;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\SocialCommentActionType;
use App\Enums\SocialCommentClassification;
use App\Enums\SocialCommentStatus;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Enums\SocialPriority;
use App\Enums\SocialReputationRisk;
use App\Enums\SocialResponseChannel;
use App\Enums\SocialSentiment;
use App\Enums\SocialSuggestedAction;
use App\Filament\Resources\SocialComments\Pages\EditSocialComment;
use App\Filament\Resources\SocialComments\Pages\ListSocialComments;
use App\Filament\Resources\SocialComments\Pages\ViewSocialComment;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\SocialComment;
use App\Models\SocialIdentity;
use App\Services\AppointmentCreationService;
use App\Services\SocialConversionService;
use App\Services\SocialCrmSettingsService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SocialCommentResource extends Resource
{
    protected static ?string $model = SocialComment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|\UnitEnum|null $navigationGroup = 'Reputacion Digital';

    protected static ?string $navigationLabel = 'Comentarios';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'comentario social';

    protected static ?string $pluralModelLabel = 'comentarios sociales';

    protected static ?int $navigationSort = 20;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['convertedPatient', 'socialAccount', 'socialIdentity.patient', 'socialPost', 'suggestedProcedure']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('status')
                ->label('Estado')
                ->options(self::enumOptions(SocialCommentStatus::cases()))
                ->required(),
            Select::make('suggested_action')
                ->label('Accion sugerida')
                ->options(self::enumOptions(SocialSuggestedAction::cases()))
                ->nullable(),
            Select::make('response_channel')
                ->label('Canal recomendado')
                ->options(self::enumOptions(SocialResponseChannel::cases()))
                ->nullable(),
            Select::make('conversion_status')
                ->label('Estado CRM')
                ->options(self::enumOptions(SocialConversionStatus::cases()))
                ->required(),
            Select::make('suggested_procedure_id')
                ->label('Procedimiento sugerido')
                ->relationship('suggestedProcedure', 'name')
                ->searchable()
                ->preload()
                ->nullable(),
            Textarea::make('suggested_reply')
                ->label('Respuesta sugerida')
                ->rows(4)
                ->columnSpanFull(),
            Textarea::make('ai_reason')
                ->label('Motivo IA')
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('15s')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('smart_alert')
                    ->label('Alerta')
                    ->state(fn (SocialComment $record): string => self::smartAlertHtml($record))
                    ->html()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('platform')
                    ->label('Red')
                    ->badge()
                    ->formatStateUsing(fn (SocialPlatform $state): string => $state->label()),
                TextColumn::make('socialAccount.account_name')
                    ->label('Cuenta')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('comment_text')
                    ->label('Comentario')
                    ->searchable()
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('socialIdentity.display_name')
                    ->label('Lead')
                    ->placeholder('Sin identidad')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('socialIdentity.patient.full_name')
                    ->label('Paciente')
                    ->placeholder('Sin ficha')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('classification')
                    ->label('Clasificacion')
                    ->badge()
                    ->formatStateUsing(fn (?SocialCommentClassification $state): string => $state?->label() ?? 'Sin clasificar'),
                TextColumn::make('sentiment')
                    ->label('Sentimiento')
                    ->badge()
                    ->formatStateUsing(fn (?SocialSentiment $state): string => $state?->label() ?? 'Sin analizar'),
                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->formatStateUsing(fn (?SocialPriority $state): string => $state?->label() ?? 'Sin prioridad')
                    ->color(fn (?SocialPriority $state): string => $state?->color() ?? 'gray'),
                TextColumn::make('reputation_risk')
                    ->label('Riesgo')
                    ->badge()
                    ->formatStateUsing(fn (?SocialReputationRisk $state): string => $state?->label() ?? 'Sin riesgo')
                    ->color(fn (?SocialReputationRisk $state): string => match ($state) {
                        SocialReputationRisk::Low => 'gray',
                        SocialReputationRisk::Medium => 'info',
                        SocialReputationRisk::High => 'warning',
                        SocialReputationRisk::Critical => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (SocialCommentStatus $state): string => $state->label())
                    ->color(fn (SocialCommentStatus $state): string => $state->color()),
                TextColumn::make('conversion_status')
                    ->label('CRM')
                    ->badge()
                    ->formatStateUsing(fn (?SocialConversionStatus $state): string => $state?->label() ?? 'Sin conversion')
                    ->color(fn (?SocialConversionStatus $state): string => match ($state) {
                        SocialConversionStatus::IdentityLinked,
                        SocialConversionStatus::AppointmentCreated,
                        SocialConversionStatus::Converted => 'success',
                        SocialConversionStatus::PendingPatientCreation,
                        SocialConversionStatus::TokenGenerated,
                        SocialConversionStatus::WhatsappStarted => 'warning',
                        SocialConversionStatus::Lost => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('tracking_token')
                    ->label('Token')
                    ->placeholder('Sin token')
                    ->copyable()
                    ->toggleable(),
                IconColumn::make('requires_human_review')
                    ->label('Revision')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('platform')
                    ->label('Red')
                    ->options(self::enumOptions(SocialPlatform::cases())),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(self::enumOptions(SocialCommentStatus::cases())),
                SelectFilter::make('classification')
                    ->label('Clasificacion')
                    ->options(self::enumOptions(SocialCommentClassification::cases())),
                SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options(self::enumOptions(SocialPriority::cases())),
                SelectFilter::make('reputation_risk')
                    ->label('Riesgo')
                    ->options(self::enumOptions(SocialReputationRisk::cases())),
                SelectFilter::make('conversion_status')
                    ->label('Estado CRM')
                    ->options(self::enumOptions(SocialConversionStatus::cases())),
                Filter::make('requires_human_review')
                    ->label('Requiere revision')
                    ->query(fn (Builder $query): Builder => $query->where('requires_human_review', true)),
            ])
            ->recordActions([
                ViewAction::make(),
                ...self::commentActions(),
                EditAction::make(),
            ]);
    }

    public static function commentActions(): array
    {
        return [
            Action::make('mark_as_reviewed')
                ->label('Marcar revisado')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (SocialComment $record): bool => in_array($record->status, [
                    SocialCommentStatus::New,
                    SocialCommentStatus::Classified,
                    SocialCommentStatus::ReviewRequired,
                    SocialCommentStatus::Escalated,
                ], true))
                ->action(fn (SocialComment $record) => self::registerAction(
                    $record,
                    SocialCommentActionType::MarkAsReviewed,
                    SocialCommentStatus::Classified,
                    'Comentario revisado manualmente.',
                )),
            Action::make('ignore')
                ->label('Ignorar')
                ->icon('heroicon-o-no-symbol')
                ->color('gray')
                ->form([
                    Textarea::make('notes')
                        ->label('Notas')
                        ->rows(3),
                ])
                ->action(fn (SocialComment $record, array $data) => self::registerAction(
                    $record,
                    SocialCommentActionType::Ignore,
                    SocialCommentStatus::Ignored,
                    $data['notes'] ?: 'Comentario ignorado manualmente.',
                )),
            Action::make('escalate')
                ->label('Escalar')
                ->icon('heroicon-o-arrow-up-circle')
                ->color('warning')
                ->form([
                    Textarea::make('notes')
                        ->label('Motivo')
                        ->required()
                        ->rows(3),
                ])
                ->action(fn (SocialComment $record, array $data) => self::registerAction(
                    $record,
                    SocialCommentActionType::Escalate,
                    SocialCommentStatus::Escalated,
                    $data['notes'],
                )),
            Action::make('mark_as_spam')
                ->label('Spam interno')
                ->icon('heroicon-o-shield-exclamation')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Esta accion solo marca el comentario como spam dentro del sistema. No modifica Meta.')
                ->action(fn (SocialComment $record) => self::registerAction(
                    $record,
                    SocialCommentActionType::MarkAsSpam,
                    SocialCommentStatus::MarkedAsSpam,
                    'Comentario marcado como spam internamente.',
                )),
            Action::make('route_to_whatsapp')
                ->label(fn (SocialComment $record): string => $record->whatsapp_redirected_at ? 'Ver texto de seguimiento' : 'Derivar')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('success')
                ->modalHeading('Texto final para copiar y responder')
                ->modalDescription('Este mensaje incluye tracking. Pegalo como respuesta al comentario en Instagram o Facebook.')
                ->modalSubmitActionLabel(fn (SocialComment $record): string => $record->whatsapp_redirected_at ? 'Actualizar seguimiento' : 'Generar seguimiento')
                ->form(fn (SocialComment $record): array => [
                    Textarea::make('final_reply')
                        ->label('Texto final')
                        ->default(fn (): string => self::whatsappReplyText($record))
                        ->rows(4)
                        ->columnSpanFull(),
                    Select::make('suggested_procedure_id')
                        ->label('Procedimiento de interes')
                        ->default(fn (): ?int => $record->suggested_procedure_id ?? $record->socialPost?->procedure_id)
                        ->options(fn (): array => Procedure::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText('Define que bloque de Smart Link vera el paciente.'),
                    TextInput::make('smart_link')
                        ->label('Smart Link')
                        ->default(fn (): string => self::smartLinkPreview($record))
                        ->readOnly(),
                    TextInput::make('whatsapp_link')
                        ->label('WhatsApp directo')
                        ->default(fn (): string => self::whatsappLinkPreview($record))
                        ->readOnly(),
                    TextInput::make('tracking_token')
                        ->label('Token')
                        ->default(fn (): string => $record->tracking_token ?: 'Se generara al confirmar')
                        ->readOnly(),
                ])
                ->action(function (SocialComment $record, array $data): void {
                    $procedureId = filled($data['suggested_procedure_id'] ?? null)
                        ? (int) $data['suggested_procedure_id']
                        : null;
                    $update = ['suggested_procedure_id' => $procedureId];

                    if ($record->estimated_value === null && $procedureId) {
                        $procedure = Procedure::find($procedureId);

                        if ($procedure?->internal_rate !== null) {
                            $update['estimated_value'] = $procedure->internal_rate;
                        }
                    }

                    $record->update($update);

                    $token = app(SocialConversionService::class)->markRedirectedToWhatsapp($record);

                    Notification::make()
                        ->title('Texto de seguimiento generado')
                        ->body("Copialo y pegalo como respuesta al comentario. Token: {$token}")
                        ->success()
                        ->send();
                }),
            Action::make('link_existing_patient')
                ->label('Vincular paciente')
                ->icon('heroicon-o-link')
                ->color('info')
                ->form(fn (SocialComment $record): array => [
                    Select::make('patient_id')
                        ->label('Paciente')
                        ->default($record->socialIdentity?->patient_id ?? $record->converted_patient_id)
                        ->options(fn (): array => Patient::query()->orderBy('full_name')->pluck('full_name', 'id')->all())
                        ->searchable()
                        ->required(),
                ])
                ->action(function (SocialComment $record, array $data): void {
                    $patient = Patient::findOrFail($data['patient_id']);
                    self::linkPatientToComment(
                        $record,
                        $patient,
                        'Paciente existente vinculado manualmente desde el inbox social.',
                        SocialCommentActionType::LinkIdentity,
                    );
                }),
            Action::make('create_patient_from_lead')
                ->label('Crear ficha')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->visible(fn (SocialComment $record): bool => blank($record->socialIdentity?->patient_id))
                ->modalHeading('Crear ficha de paciente desde lead')
                ->modalSubmitActionLabel('Crear ficha')
                ->form(fn (SocialComment $record): array => [
                    TextInput::make('full_name')
                        ->label('Nombre completo')
                        ->default($record->socialIdentity?->display_name ?: $record->author_name)
                        ->required()
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label('Telefono')
                        ->tel()
                        ->default($record->socialIdentity?->phone)
                        ->required()
                        ->maxLength(50),
                    DatePicker::make('date_of_birth')
                        ->label('Fecha de nacimiento'),
                    Textarea::make('notes')
                        ->label('Notas')
                        ->default(fn (): string => self::patientLeadNotes($record))
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->action(function (SocialComment $record, array $data): void {
                    $patient = Patient::create([
                        'full_name' => $data['full_name'],
                        'normalized_name' => self::normalizeName($data['full_name']),
                        'phone' => $data['phone'],
                        'date_of_birth' => $data['date_of_birth'] ?? null,
                        'notes' => $data['notes'] ?? null,
                    ]);

                    self::linkPatientToComment(
                        $record,
                        $patient,
                        'Ficha de paciente creada desde lead social.',
                        SocialCommentActionType::CreatePatientFromLead,
                    );
                }),
            Action::make('create_appointment')
                ->label('Crear cita')
                ->icon('heroicon-o-calendar-days')
                ->color('primary')
                ->modalHeading('Crear cita desde lead social')
                ->modalSubmitActionLabel('Crear cita')
                ->form(fn (SocialComment $record): array => [
                    Select::make('patient_id')
                        ->label('Paciente')
                        ->default($record->socialIdentity?->patient_id ?? $record->converted_patient_id)
                        ->options(fn (): array => Patient::query()->orderBy('full_name')->pluck('full_name', 'id')->all())
                        ->searchable()
                        ->nullable()
                        ->helperText('Opcional si la ficha aun no existe.'),
                    Select::make('procedure_id')
                        ->label('Procedimiento de interes')
                        ->default($record->suggested_procedure_id ?? $record->socialPost?->procedure_id)
                        ->options(fn (): array => Procedure::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    TextInput::make('scheduled_at')
                        ->label('Fecha y hora')
                        ->placeholder('YYYY-MM-DD HH:MM')
                        ->helperText('Ejemplo: '.now()->addDay()->format('Y-m-d H:i'))
                        ->required(),
                    TextInput::make('duration_minutes')
                        ->label('Duracion en minutos')
                        ->numeric()
                        ->minValue(1)
                        ->default(45),
                    Select::make('status')
                        ->label('Estado')
                        ->options(self::enumOptions(AppointmentStatus::cases()))
                        ->default(AppointmentStatus::Scheduled->value)
                        ->required(),
                    Select::make('source')
                        ->label('Origen')
                        ->options(self::enumOptions(AppointmentSource::cases()))
                        ->default(AppointmentSource::AdminManual->value)
                        ->required(),
                    Textarea::make('notes')
                        ->label('Notas')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->action(function (SocialComment $record, array $data): void {
                    app(AppointmentCreationService::class)->createFromSocialLead($record, [
                        'patient_id' => filled($data['patient_id'] ?? null) ? (int) $data['patient_id'] : null,
                        'procedure_id' => filled($data['procedure_id'] ?? null) ? (int) $data['procedure_id'] : null,
                        'scheduled_at' => $data['scheduled_at'],
                        'duration_minutes' => filled($data['duration_minutes'] ?? null) ? (int) $data['duration_minutes'] : null,
                        'status' => AppointmentStatus::from($data['status']),
                        'source' => AppointmentSource::from($data['source']),
                        'notes' => $data['notes'] ?? null,
                        'created_by' => auth()->id(),
                    ]);

                    Notification::make()
                        ->title('Cita creada')
                        ->body('La cita quedo asociada al lead social y registrada en el pipeline.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSocialComments::route('/'),
            'view' => ViewSocialComment::route('/{record}'),
            'edit' => EditSocialComment::route('/{record}/edit'),
        ];
    }

    public static function registerAction(
        SocialComment $record,
        SocialCommentActionType $action,
        SocialCommentStatus $status,
        string $notes,
    ): void {
        $updates = [
            'status' => $status,
            'requires_human_review' => false,
        ];

        if ($action === SocialCommentActionType::MarkAsReviewed
            && app(SocialCrmSettingsService::class)->archiveOnReview()
        ) {
            $updates['is_hidden'] = true;
        }

        $record->update($updates);

        $record->actions()->create([
            'action' => $action,
            'performed_by' => auth()->id(),
            'notes' => $notes,
        ]);

        Notification::make()
            ->title('Accion registrada')
            ->body('El comentario fue actualizado y auditado en el historial.')
            ->success()
            ->send();
    }

    private static function enumOptions(array $cases): array
    {
        return collect($cases)
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->all();
    }

    private static function linkPatientToComment(
        SocialComment $record,
        Patient $patient,
        string $notes,
        SocialCommentActionType $action,
    ): void {
        $identity = $record->socialIdentity;

        if (! $identity) {
            $identity = SocialIdentity::firstOrCreate(
                [
                    'platform' => $record->platform->value,
                    'platform_user_id' => $record->author_external_id ?: 'unknown-comment-'.$record->id,
                ],
                [
                    'username' => $record->author_username,
                    'display_name' => $record->author_name,
                    'first_seen_at' => $record->published_at ?: now(),
                    'last_seen_at' => now(),
                    'metadata' => ['source' => 'filament_manual_patient_link'],
                ],
            );

            $record->update(['social_identity_id' => $identity->id]);
        }

        $identity->update([
            'patient_id' => $patient->id,
            'phone' => $identity->phone ?: $patient->phone,
            'normalized_phone' => $identity->normalized_phone ?: self::normalizePhone((string) $patient->phone),
            'status' => SocialIdentityStatus::LinkedPatient,
            'linked_at' => now(),
            'last_seen_at' => now(),
        ]);

        $record->update([
            'conversion_status' => SocialConversionStatus::IdentityLinked,
            'converted_patient_id' => $patient->id,
            'converted_at' => $record->converted_at ?: now(),
        ]);

        $record->actions()->create([
            'action' => $action,
            'performed_by' => auth()->id(),
            'notes' => $notes,
            'external_response' => ['patient_id' => $patient->id],
        ]);

        Notification::make()
            ->title('Paciente vinculado')
            ->body('La identidad social quedo asociada a la ficha clinica.')
            ->success()
            ->send();
    }

    private static function whatsappReplyText(SocialComment $record): string
    {
        return app(SocialConversionService::class)->instagramReplyText($record);
    }

    private static function smartLinkPreview(SocialComment $record): string
    {
        return app(SocialConversionService::class)->smartLink($record);
    }

    private static function whatsappLinkPreview(SocialComment $record): string
    {
        return app(SocialConversionService::class)->whatsappLink($record)
            ?? 'Configura WHATSAPP_BUSINESS_PHONE para generar link directo';
    }

    private static function patientLeadNotes(SocialComment $record): string
    {
        return "Ficha creada desde lead social. Red: {$record->platform->label()}. Comentario ID: {$record->id}. Usuario: "
            .($record->author_username ?: $record->author_name ?: 'N/A').'.';
    }

    private static function normalizeName(string $name): string
    {
        return Str::of($name)->lower()->ascii()->squish()->toString();
    }

    private static function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? $phone;
    }

    private static function smartAlertHtml(SocialComment $record): string
    {
        $risk = $record->reputation_risk;
        $classification = $record->classification;

        if ($risk === SocialReputationRisk::Critical) {
            return '<span style="display:inline-flex;align-items:center;gap:.35rem;border-radius:999px;background:#7f1d1d;color:#fff;padding:.32rem .58rem;font-size:.72rem;font-weight:900;box-shadow:0 0 0 3px rgba(239,68,68,.22),0 0 18px rgba(239,68,68,.55);animation:socialPulse 1.1s infinite;">! CRITICO</span><style>@keyframes socialPulse{0%,100%{transform:scale(1);filter:saturate(1)}50%{transform:scale(1.04);filter:saturate(1.4)}}</style>';
        }

        if ($risk === SocialReputationRisk::High
            || in_array($classification, [
                SocialCommentClassification::Complaint,
                SocialCommentClassification::NegativeOpinion,
                SocialCommentClassification::LegalSensitive,
            ], true)
        ) {
            return '<span style="display:inline-flex;align-items:center;gap:.35rem;border-radius:999px;background:#fef2f2;color:#b91c1c;padding:.32rem .58rem;font-size:.72rem;font-weight:850;border:1px solid #fecaca;">Crisis</span>';
        }

        if ($classification === SocialCommentClassification::SalesLead
            || $classification === SocialCommentClassification::CommercialQuestion
        ) {
            return '<span style="display:inline-flex;align-items:center;gap:.35rem;border-radius:999px;background:#ecfdf5;color:#047857;padding:.32rem .58rem;font-size:.72rem;font-weight:850;border:1px solid #bbf7d0;">Lead</span>';
        }

        if ($classification === SocialCommentClassification::MedicalSensitive) {
            return '<span style="display:inline-flex;align-items:center;gap:.35rem;border-radius:999px;background:#fffbeb;color:#b45309;padding:.32rem .58rem;font-size:.72rem;font-weight:850;border:1px solid #fed7aa;">Medico</span>';
        }

        return '<span style="display:inline-flex;border-radius:999px;background:#f8fafc;color:#64748b;padding:.32rem .58rem;font-size:.72rem;font-weight:800;border:1px solid #e2e8f0;">Normal</span>';
    }
}
