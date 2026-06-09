<?php

namespace App\Filament\Resources\SocialComments;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialCommentClassification;
use App\Enums\SocialCommentStatus;
use App\Enums\SocialPlatform;
use App\Enums\SocialPriority;
use App\Enums\SocialReputationRisk;
use App\Enums\SocialResponseChannel;
use App\Enums\SocialSentiment;
use App\Enums\SocialSuggestedAction;
use App\Filament\Resources\SocialComments\Pages\EditSocialComment;
use App\Filament\Resources\SocialComments\Pages\ListSocialComments;
use App\Filament\Resources\SocialComments\Pages\ViewSocialComment;
use App\Models\SocialComment;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SocialCommentResource extends Resource
{
    protected static ?string $model = SocialComment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string | \UnitEnum | null $navigationGroup = 'Reputacion Digital';

    protected static ?string $navigationLabel = 'Comentarios';

    protected static ?string $modelLabel = 'comentario social';

    protected static ?string $pluralModelLabel = 'comentarios sociales';

    protected static ?int $navigationSort = 20;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['socialAccount', 'socialPost']);
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
        $record->update([
            'status' => $status,
            'requires_human_review' => false,
        ]);

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
}
