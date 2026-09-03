<?php

namespace App\Filament\Resources\InformedConsentTemplates;

use App\Filament\Resources\InformedConsentTemplates\Pages\CreateInformedConsentTemplate;
use App\Filament\Resources\InformedConsentTemplates\Pages\EditInformedConsentTemplate;
use App\Filament\Resources\InformedConsentTemplates\Pages\ListInformedConsentTemplates;
use App\Models\InformedConsentTemplate;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Resources\Resource\Concerns\BelongsToTenant;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InformedConsentTemplateResource extends Resource
{
    use BelongsToTenant;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRolePermission('informed_consent_templates.view') ?? false;
    }

    protected static ?string $model = InformedConsentTemplate::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Consentimientos informados';

    protected static ?string $navigationLabel = 'Plantillas de consentimiento';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'plantilla de consentimiento';

    protected static ?string $pluralModelLabel = 'plantillas de consentimiento';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->label('Descripcion')
                ->rows(3)
                ->columnSpanFull(),
            Select::make('type')
                ->label('Tipo')
                ->options([
                    'general' => 'General',
                    'procedure' => 'Procedimiento',
                    'surgery' => 'Cirugia',
                    'anesthesia' => 'Anestesia',
                    'sedation' => 'Sedacion',
                    'implant' => 'Implante',
                    'orthodontics' => 'Ortodoncia',
                    'endodontics' => 'Endodoncia',
                    'periodontics' => 'Periodoncia',
                    'prosthodontics' => 'Prostodoncia',
                    'cosmetic' => 'Estetica',
                    'radiology' => 'Radiologia',
                    'laboratory' => 'Laboratorio',
                    'custom' => 'Personalizado',
                ])
                ->default('general')
                ->required(),
            \Filament\Forms\Components\Toggle::make('is_active')
                ->label('Activo')
                ->default(true),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'general' => 'General',
                        'procedure' => 'Procedimiento',
                        'surgery' => 'Cirugia',
                        'anesthesia' => 'Anestesia',
                        'sedation' => 'Sedacion',
                        'implant' => 'Implante',
                        'orthodontics' => 'Ortodoncia',
                        'endodontics' => 'Endodoncia',
                        'periodontics' => 'Periodoncia',
                        'prosthodontics' => 'Prostodoncia',
                        'cosmetic' => 'Estetica',
                        'radiology' => 'Radiologia',
                        'laboratory' => 'Laboratorio',
                        'custom' => 'Personalizado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'general' => 'gray',
                        'surgery' => 'danger',
                        'anesthesia' => 'danger',
                        'sedation' => 'danger',
                        'implant' => 'info',
                        'orthodontics' => 'success',
                        default => 'primary',
                    }),
                TextColumn::make('currentVersion.version_number')
                    ->label('Version actual')
                    ->sortable(),
                TextColumn::make('currentVersion.status')
                    ->label('Estado version')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Borrador',
                        'active' => 'Activo',
                        'archived' => 'Archivado',
                        default => $state,
                    }),
                TextColumn::make('requirements_count')
                    ->label('Requisitos')
                    ->counts('requirements')
                    ->sortable(),
                TextColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
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
            'index' => ListInformedConsentTemplates::route('/'),
            'create' => CreateInformedConsentTemplate::route('/create'),
            'edit' => EditInformedConsentTemplate::route('/{record}/edit'),
        ];
    }
}
