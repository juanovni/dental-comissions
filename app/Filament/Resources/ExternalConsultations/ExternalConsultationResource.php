<?php

namespace App\Filament\Resources\ExternalConsultations;

use App\Filament\Resources\ExternalConsultations\Pages\CreateExternalConsultation;
use App\Filament\Resources\ExternalConsultations\Pages\EditExternalConsultation;
use App\Filament\Resources\ExternalConsultations\Pages\ListExternalConsultations;
use App\Models\ExternalMedicalConsultation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Resources\Resource\Concerns\BelongsToTenant;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExternalConsultationResource extends Resource
{
    use BelongsToTenant;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRolePermission('external_consultations.view') ?? false;
    }

    protected static ?string $model = ExternalMedicalConsultation::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Consultas externas y autorizaciones';

    protected static ?string $navigationLabel = 'Consultas externas';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-top-right-on-square';

    protected static ?int $navigationSort = 11;

    protected static ?string $modelLabel = 'consulta externa';

    protected static ?string $pluralModelLabel = 'consultas externas';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('patient_id')
                ->label('Paciente')
                ->relationship('patient', 'full_name')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('requesting_professional_id')
                ->label('Profesional solicitante')
                ->relationship('requestingProfessional', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('specialty_id')
                ->label('Especialidad requerida')
                ->relationship('specialty', 'name')
                ->searchable()
                ->preload()
                ->nullable(),
            TextInput::make('specialty_name')
                ->label('Nombre especialidad (si no esta en catalogo)')
                ->maxLength(255),
            TextInput::make('recipient_name')
                ->label('Destinatario')
                ->maxLength(255),
            TextInput::make('recipient_institution')
                ->label('Institucion')
                ->maxLength(255),
            TextInput::make('recipient_phone')
                ->label('Telefono destinatario')
                ->maxLength(50),
            TextInput::make('recipient_email')
                ->label('Email destinatario')
                ->email()
                ->maxLength(255),
            Select::make('status')
                ->label('Estado')
                ->options([
                    'draft' => 'Borrador',
                    'requested' => 'Solicitada',
                    'sent' => 'Enviada',
                    'received' => 'Recibida',
                    'under_review' => 'En revision',
                    'closed' => 'Cerrada',
                    'cancelled' => 'Cancelada',
                ])
                ->default('draft')
                ->required(),
            Textarea::make('reason')
                ->label('Motivo de consulta')
                ->rows(3)
                ->required()
                ->columnSpanFull(),
            Textarea::make('clinical_context')
                ->label('Contexto clinico')
                ->rows(3)
                ->columnSpanFull(),
            Repeater::make('questions')
                ->label('Preguntas al especialista')
                ->schema([
                    TextInput::make('question')
                        ->label('Pregunta')
                        ->required()
                        ->maxLength(500),
                ])
                ->columns(1)
                ->addActionLabel('Agregar pregunta')
                ->defaultItems(0)
                ->collapsible()
                ->columnSpanFull(),
            DatePicker::make('requested_at')
                ->label('Solicitado el'),
            DatePicker::make('sent_at')
                ->label('Enviado el'),
            DatePicker::make('received_at')
                ->label('Recibido el'),
            DatePicker::make('closed_at')
                ->label('Cerrado el'),
        ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.full_name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('requestingProfessional.name')
                    ->label('Solicitante')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('specialty.name')
                    ->label('Especialidad')
                    ->sortable(),
                TextColumn::make('recipient_name')
                    ->label('Destinatario')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Borrador',
                        'requested' => 'Solicitada',
                        'sent' => 'Enviada',
                        'received' => 'Recibida',
                        'under_review' => 'En revision',
                        'closed' => 'Cerrada',
                        'cancelled' => 'Cancelada',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'requested' => 'warning',
                        'sent' => 'info',
                        'received' => 'success',
                        'under_review' => 'info',
                        'closed' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('sent_at')
                    ->label('Enviado')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExternalConsultations::route('/'),
            'create' => CreateExternalConsultation::route('/create'),
            'edit' => EditExternalConsultation::route('/{record}/edit'),
        ];
    }
}
